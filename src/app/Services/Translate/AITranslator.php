<?php

namespace App\Services\Translate;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

/**
 * Translates strings via OpenAI's Chat Completions API.
 *
 * Features:
 *   - Per-minute rate limiting via Laravel's RateLimiter
 *   - Configurable model, prompt, and API key
 *   - Single and batch translation methods
 *   - Language name resolution for natural AI prompts
 *   - Exponential backoff on 429 rate limit responses (up to 4 attempts)
 */
class AITranslator
{
    protected ?string $apiKey;
    protected string $model;
    protected string $systemPrompt;
    protected array $languages;
    protected $logProcess = false;

    public function __construct()
    {
        $this->apiKey       = config('translation.translation.api_key');
        $this->model        = config('translation.translation.model', 'gpt-4.1-nano');
        $this->systemPrompt = config('translation.translation.system_prompt', 'Translate to {language}');
        $this->languages    = config('translation.languages', []);
        $this->logProcess   = config('translation.log_process', false);
    }

    /**
     * Translate a single string to the target locale.
     * Returns null if not configured or API error.
     */
    public function translate(string $text, string $targetLocale): ?string
    {
        if (!$this->apiKey) {
            Log::warning('OpenAI API key not configured. Skipping translation.');
            return null;
        }

        $perMinute = config('translation.translation.rate_limit_per_minute');

        while (!RateLimiter::attempt('openai-translations', $perMinute, function () {}, 60)) {
            $seconds = RateLimiter::availableIn('openai-translations');
            Log::info("Laravel rate limit hit, waiting {$seconds}s", ['locale' => $targetLocale]);
            sleep(max(1, $seconds));
        }

        return $this->callOpenAI($text, $targetLocale);
    }

    /**
     * Call OpenAI Chat Completions API with exponential backoff on 429.
     */
    protected function callOpenAI(string $text, string $targetLocale, int $attempt = 1): ?string
    {
        try {
            $languageName = $this->getLanguageName($targetLocale);
            $prompt = str_replace('{language}', $languageName, $this->systemPrompt);

            if ($this->logProcess) {
                Log::info("Translating to {$targetLocale}", ['text' => $text]);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(200)->post('https://api.openai.com/v1/chat/completions', [
                'model'    => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user',   'content' => "Translate this text: {$text}"],
                ],

            ]);

            // Exponential backoff on rate limit: 2s, 4s, 8s, then give up
            if ($response->status() === 429) {
                if ($attempt >= 4) {
                    Log::error('OpenAI rate limit exceeded after retries', [
                        'locale' => $targetLocale,
                        'text'   => substr($text, 0, 100),
                    ]);
                    return null;
                }

                $delay = (2 ** $attempt) * 1000000; // 2s → 4s → 8s
                Log::warning("OpenAI rate limited, retrying in " . ($delay / 1000000) . "s (attempt {$attempt})", [
                    'locale' => $targetLocale,
                ]);
                usleep($delay);

                return $this->callOpenAI($text, $targetLocale, $attempt + 1);
            }

            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data       = $response->json();
            $translated = trim($data['choices'][0]['message']['content'] ?? '');

            if (!$translated || (is_numeric($translated) && !is_numeric(trim($text)))) {
                Log::warning('OpenAI returned invalid translation, discarding', [
                    'locale' => $targetLocale,
                    'raw'    => $translated,
                    'text'   => substr($text, 0, 100),
                ]);
                return null; // source isn't numeric but got numeric back — bad response, skip
            }

            if (is_numeric($translated) && is_numeric(trim($text))) {
                return $text; // source is numeric, untranslatable, keep original
            }

            if ($this->logProcess) {
                Log::info("Translation successful", [
                    'locale'     => $targetLocale,
                    'original'   => $text,
                    'translated' => $translated,
                ]);
            }

            return $translated;

        } catch (\Exception $e) {
            Log::error("Translation failed for locale {$targetLocale}", [
                'error' => $e->getMessage(),
                'text'  => substr($text, 0, 100),
            ]);
            return null;
        }
    }

    /**
     * Resolve locale code to full language name for the AI prompt.
     */
    protected function getLanguageName(string $locale): string
    {
        return $this->languages[$locale] ?? $locale;
    }

    /**
     * Translate multiple strings. Returns only successful translations.
     */
    public function translateBatch(array $texts, string $targetLocale): array
    {
        $translations = [];

        foreach ($texts as $key => $text) {
            $translated = $this->translate($text, $targetLocale);

            if ($translated) {
                $translations[$key] = $translated;
            }
        }

        return $translations;
    }

    /**
     * Get all configured languages.
     */
    public function getAvailableLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Get target locale codes (excludes source language).
     */
    public function getTargetLocales(): array
    {
        return config('translation.target_locales', []);
    }

    /**
     * Check if the OpenAI API key is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}