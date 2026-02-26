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
        $this->apiKey = config('translation.translation.api_key');
        $this->model = config('translation.translation.model', 'gpt-4o-mini');
        $this->systemPrompt = config('translation.translation.system_prompt', 'Translate to {language}');
        $this->languages = config('translation.languages', []);
        $this->logProcess = config('translation.log_process', false);
    }

    /**
     * Translate a single string to the target locale.
     * Returns null if rate limited, not configured, or API error.
     */
    public function translate(string $text, string $targetLocale): ?string
    {
        if (!$this->apiKey) {
            Log::warning('OpenAI API key not configured. Skipping translation.');
            return null;
        }

        $perMinute = config('translation.translation.rate_limit_per_minute');

        return RateLimiter::attempt(
            'openai-translations',
            $perMinute,
            function () use ($text, $targetLocale) {
                return $this->callOpenAI($text, $targetLocale);
            },
            60
        );
    }

    /**
     * Call OpenAI Chat Completions API.
     */
    protected function callOpenAI(string $text, string $targetLocale): ?string
    {
        try {
            $languageName = $this->getLanguageName($targetLocale);
            $prompt = str_replace('{language}', $languageName, $this->systemPrompt);

            if ($this->logProcess) {
                Log::info("Translating to {$targetLocale}", ['text' => $text]);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => $text],
                ],
                'temperature' => 0.3,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $translated = trim($data['choices'][0]['message']['content'] ?? '');

            if ($this->logProcess) {
                Log::info("Translation successful", [
                    'locale' => $targetLocale,
                    'original' => $text,
                    'translated' => $translated,
                ]);
            }

            return $translated;

        } catch (\Exception $e) {
            Log::error("Translation failed for '{$text}' to {$targetLocale}", [
                'error' => $e->getMessage(),
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