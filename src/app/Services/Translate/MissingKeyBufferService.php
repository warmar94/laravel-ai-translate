<?php

namespace App\Services\Translate;

/**
 * Request-scoped buffer for missing translation keys.
 *
 * Registered as a singleton — accumulates keys in memory during a
 * single request. The TranslationServiceProvider dispatches all
 * buffered keys as one ProcessMissingKeysJob after the response is sent.
 *
 * Source keys → written to en.json by the job
 * Target keys → inserted into missing_translations table by the job
 */
class MissingKeyBufferService
{
    /** @var array<string, true> Deduplicated source locale keys */
    protected array $sourceKeys = [];

    /** @var array<int, array{key: string, locale: string}> Target locale keys */
    protected array $targetKeys = [];

    /**
     * Buffer a source locale key (e.g. English).
     * Uses associative array for automatic deduplication.
     */
    public function addSourceKey(string $key): void
    {
        $this->sourceKeys[$key] = true;
    }

    /**
     * Buffer a target locale key (e.g. Arabic, Spanish).
     * Stored with locale for per-language tracking.
     */
    public function addTargetKey(string $key, string $locale): void
    {
        $this->targetKeys[] = ['key' => $key, 'locale' => $locale];
    }

    /**
     * Get all buffered source keys as a flat array.
     */
    public function getSourceKeys(): array
    {
        return array_keys($this->sourceKeys);
    }

    /**
     * Get all buffered target keys with their locales.
     */
    public function getTargetKeys(): array
    {
        return $this->targetKeys;
    }

    /**
     * Check if any keys have been buffered.
     */
    public function hasKeys(): bool
    {
        return !empty($this->sourceKeys) || !empty($this->targetKeys);
    }

    /**
     * Clear all buffered keys after dispatch.
     */
    public function flush(): void
    {
        $this->sourceKeys = [];
        $this->targetKeys = [];
    }
}