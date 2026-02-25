<?php

namespace App\Models\Translate;

use Illuminate\Database\Eloquent\Model;

class TranslationProgress extends Model
{
    protected $table = 'translation_progress';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'locale',
        'total',
        'completed',
        'failed',
        'started_at',
        'updated_at',
        'completed_at',
    ];

    protected $casts = [
        'total' => 'integer',
        'completed' => 'integer',
        'failed' => 'integer',
        'started_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scopeStringExtraction($query)
    {
        return $query->where('type', 'string_extraction')->whereNull('locale');
    }

    public function scopeTranslation($query)
    {
        return $query->where('type', 'translation');
    }

    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function getPercentageAttribute(): float
    {
        if ($this->total <= 0) return 0;
        return round(($this->completed / $this->total) * 100, 1);
    }

    public function getStatusAttribute(): string
    {
        if ($this->total == 0) return 'idle';
        if ($this->completed >= $this->total) return 'completed';
        if ($this->completed > 0) return 'running';
        return 'idle';
    }
}