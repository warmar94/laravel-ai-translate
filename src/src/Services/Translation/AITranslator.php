<?php
// app/Services/Translation/AITranslator.php

namespace Warmar\LaravelAiTranslate\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

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
            function() use ($text, $targetLocale) {
                return $this->callOpenAI($text, $targetLocale);
            },
            60 // decay seconds
        );
    }
    
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
                    [
                        'role' => 'system',
                        'content' => $prompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
                'temperature' => 0.3, // More consistent translations
            ]);
            
            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
            
            $data = $response->json();
            $translated = trim($data['choices'][0]['message']['content'] ?? '');
            
            if ($this->logProcess) {
                Log::info("Translation successful", [
                    'locale' => $targetLocale,
                    'original' => $text,
                    'translated' => $translated
                ]);
            }
            
            return $translated;
            
        } catch (\Exception $e) {
            Log::error("Translation failed for '{$text}' to {$targetLocale}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    protected function getLanguageName(string $locale): string
    {
        return $this->languages[$locale] ?? $locale;
    }
    
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
    
    public function getAvailableLanguages(): array
    {
        return $this->languages;
    }
    
    public function getTargetLocales(): array
    {
        return config('translation.target_locales', []);
    }
    
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}