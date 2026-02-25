<?php

namespace App\Models\Translate;

use Illuminate\Database\Eloquent\Model;

class TranslationUrl extends Model
{
    protected $table = 'translation_urls';

    protected $fillable = [
        'url',
        'active',
        'is_api',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_api' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeRegularUrls($query)
    {
        return $query->where('is_api', 0);
    }

    public function scopeApiEndpoints($query)
    {
        return $query->where('is_api', 1);
    }

    /**
     * Only active non-API URLs (for string extraction).
     */
    public function scopeExtractable($query)
    {
        return $query->where('active', true)->where('is_api', 0);
    }
}