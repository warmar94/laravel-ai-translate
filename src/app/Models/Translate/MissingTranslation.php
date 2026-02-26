<?php

namespace App\Models\Translate;

use Illuminate\Database\Eloquent\Model;
class MissingTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'translation_missing';

    protected $fillable = [
        'key',
        'locale',
        'occurrences',
        'first_seen',
        'last_seen',
    ];

    protected $casts = [
        'occurrences' => 'integer',
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
    ];

    // ── Scopes ──────────────────────────────

    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeSourceLocale($query)
    {
        return $query->where('locale', config('translation.source_locale', 'en'));
    }

    public function scopeTargetLocales($query)
    {
        return $query->where('locale', '!=', config('translation.source_locale', 'en'));
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('last_seen');
    }

    public function scopeMostFrequent($query)
    {
        return $query->orderByDesc('occurrences');
    }

    public function scopeSince($query, $datetime)
    {
        return $query->where('first_seen', '>=', $datetime);
    }

    // ── Methods ─────────────────────────────

    /**
     * Record a missing translation key. Increments occurrences if exists.
     */
    public static function record(string $key, string $locale): void
    {
        $now = now();

        $existing = static::where('key', $key)->where('locale', $locale)->first();

        if ($existing) {
            $existing->occurrences++;
            $existing->last_seen = $now;
            $existing->save();
        } else {
            static::create([
                'key' => $key,
                'locale' => $locale,
                'occurrences' => 1,
                'first_seen' => $now,
                'last_seen' => $now,
            ]);
        }
    }

    /**
     * Get all missing keys for a locale as a flat array.
     */
    public static function keysForLocale(string $locale): array
    {
        return static::forLocale($locale)->pluck('key')->toArray();
    }

    /**
     * Clear all entries for a given locale.
     */
    public static function clearLocale(string $locale): int
    {
        return static::forLocale($locale)->delete();
    }

    /**
     * Clear all entries.
     */
    public static function clearAll(): int
    {
        return static::query()->delete();
    }
}