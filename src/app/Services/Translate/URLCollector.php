<?php

namespace App\Services\Translate;

use App\Models\Translate\TranslationUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manages the translation_urls table — adding, removing, and
 * fetching URLs for string extraction.
 *
 * Two types of URLs:
 *   is_api = 0 → Regular URLs that get scanned for __() strings
 *   is_api = 1 → API endpoints that return JSON arrays of URLs
 *
 * API endpoints are never scanned directly — they're fetched to
 * discover regular URLs which are then added to the table.
 *
 * Local API endpoints (localhost, 127.0.0.1) can be fetched via
 * internal Laravel requests to avoid php artisan serve deadlock.
 */
class URLCollector
{
    protected $logProcess = false;

    public function __construct()
    {
        $this->logProcess = config('translation.log_process', false);
    }

    /**
     * Add a single regular URL. Returns null if duplicate or empty.
     */
    public function addUrl(string $url): ?TranslationUrl
    {
        $url = trim($url);
        if (empty($url)) {
            return null;
        }

        $existing = TranslationUrl::where('url', $url)->first();
        if ($existing) {
            return null;
        }

        return TranslationUrl::create([
            'url' => $url,
            'active' => true,
            'is_api' => 0,
        ]);
    }

    /**
     * Add multiple URLs at once. Returns count of new URLs added.
     */
    public function addBulk(array $urls): int
    {
        $added = 0;
        foreach ($urls as $url) {
            if ($this->addUrl($url)) {
                $added++;
            }
        }

        if ($this->logProcess) {
            Log::info("Bulk added {$added} URLs");
        }

        return $added;
    }

    /**
     * Save an API endpoint (is_api = 1). Returns null if duplicate.
     */
    public function addApiEndpoint(string $url): ?TranslationUrl
    {
        $url = trim($url);
        if (empty($url)) {
            return null;
        }

        $existing = TranslationUrl::where('url', $url)->first();
        if ($existing) {
            return null;
        }

        return TranslationUrl::create([
            'url' => $url,
            'active' => true,
            'is_api' => 1,
        ]);
    }

    /**
     * Fetch a single API endpoint and import its URLs.
     * Saves the endpoint itself (is_api=1) and all returned URLs (is_api=0).
     */
    public function collectFromApiEndpoint(string $endpoint): int
    {
        $endpoint = trim($endpoint);
        if (empty($endpoint)) {
            return 0;
        }

        $this->addApiEndpoint($endpoint);

        $added = 0;

        try {
            if ($this->logProcess) {
                Log::info("Fetching URLs from API: {$endpoint}");
            }

            $data = $this->fetchUrlsFromEndpoint($endpoint);

            if (!empty($data)) {
                foreach ($data as $url) {
                    if (is_string($url) && !empty(trim($url))) {
                        if ($this->addUrl(trim($url))) {
                            $added++;
                        }
                    }
                }

                if ($this->logProcess) {
                    Log::info("Collected {$added} new URLs from {$endpoint}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to collect URLs from {$endpoint}", [
                'error' => $e->getMessage(),
            ]);
        }

        return $added;
    }

    /**
     * Fetch multiple API endpoints and import all their URLs.
     */
    public function collectFromApiEndpoints(array $endpoints): int
    {
        $totalAdded = 0;
        foreach ($endpoints as $endpoint) {
            $totalAdded += $this->collectFromApiEndpoint($endpoint);
        }
        return $totalAdded;
    }

    /**
     * Re-fetch all saved API endpoints to discover new content.
     */
    public function refreshAllApiEndpoints(): int
    {
        $endpoints = TranslationUrl::apiEndpoints()->active()->pluck('url')->toArray();
        $totalAdded = 0;

        foreach ($endpoints as $endpoint) {
            try {
                $data = $this->fetchUrlsFromEndpoint($endpoint);

                foreach ($data as $url) {
                    if (is_string($url) && !empty(trim($url))) {
                        if ($this->addUrl(trim($url))) {
                            $totalAdded++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to refresh from {$endpoint}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->logProcess) {
            Log::info("Refreshed API endpoints, added {$totalAdded} new URLs");
        }

        return $totalAdded;
    }

    /**
     * Get all extractable URLs (active, non-API).
     */
    public function getExtractableUrls(): array
    {
        return TranslationUrl::extractable()->pluck('url')->toArray();
    }

    /**
     * Get count of extractable URLs.
     */
    public function getExtractableCount(): int
    {
        return TranslationUrl::extractable()->count();
    }

    public function removeById(int $id): bool
    {
        return (bool) TranslationUrl::destroy($id);
    }

    public function toggleActive(int $id): bool
    {
        $record = TranslationUrl::find($id);
        if (!$record) return false;

        $record->active = !$record->active;
        $record->save();
        return true;
    }

    public function clearRegularUrls(): void
    {
        TranslationUrl::regularUrls()->delete();
    }

    public function clearApiEndpoints(): void
    {
        TranslationUrl::apiEndpoints()->delete();
    }

    public function clearAll(): void
    {
        TranslationUrl::truncate();
    }

    /**
     * Fetch URLs from an API endpoint.
     *
     * When api_scan_internal is enabled and the endpoint is local,
     * uses app()->handle() instead of HTTP to avoid the single-threaded
     * php artisan serve deadlock.
     */
    private function fetchUrlsFromEndpoint(string $url): array
    {
        if (config('translation.urls.api_scan_internal') && $this->isLocalUrl($url)) {
            if ($this->logProcess) {
                Log::info("Using internal request for local endpoint: {$url}");
            }

            $path = parse_url($url, PHP_URL_PATH);
            $query = parse_url($url, PHP_URL_QUERY);
            $uri = $query ? "$path?$query" : $path;

            $response = app()->handle(
                \Illuminate\Http\Request::create($uri, 'GET')
            );

            $data = json_decode($response->getContent(), true);

            return is_array($data) ? $data : [];
        }

        // External endpoint — use HTTP client
        $response = Http::timeout(config('translation.urls.timeout', 20))->get($url);

        if (!$response->successful()) {
            Log::error("API request failed", [
                'endpoint' => $url,
                'status' => $response->status(),
            ]);
            return [];
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * Check if a URL points to the local dev server.
     */
    private function isLocalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return in_array($host, ['127.0.0.1', 'localhost', '::1']);
    }
}