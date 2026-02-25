<?php

namespace App\Services\Translate;

use App\Models\Translate\TranslationUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class URLCollector
{
    protected $logProcess = false;

    public function __construct()
    {
        $this->logProcess = config('translation.log_process', false);
    }

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

            $response = Http::timeout(30)->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();

                if (is_array($data)) {
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
                } else {
                    Log::warning("API response is not an array", ['endpoint' => $endpoint]);
                }
            } else {
                Log::error("API request failed", [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to collect URLs from {$endpoint}", [
                'error' => $e->getMessage(),
            ]);
        }

        return $added;
    }

    public function collectFromApiEndpoints(array $endpoints): int
    {
        $totalAdded = 0;
        foreach ($endpoints as $endpoint) {
            $totalAdded += $this->collectFromApiEndpoint($endpoint);
        }
        return $totalAdded;
    }

    public function refreshAllApiEndpoints(): int
    {
        $endpoints = TranslationUrl::apiEndpoints()->active()->pluck('url')->toArray();
        $totalAdded = 0;

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(30)->get($endpoint);

                if ($response->successful()) {
                    $data = $response->json();
                    if (is_array($data)) {
                        foreach ($data as $url) {
                            if (is_string($url) && !empty(trim($url))) {
                                if ($this->addUrl(trim($url))) {
                                    $totalAdded++;
                                }
                            }
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

    public function getExtractableUrls(): array
    {
        return TranslationUrl::extractable()->pluck('url')->toArray();
    }

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
}