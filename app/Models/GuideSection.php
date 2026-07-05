<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class GuideSection extends Model
{
    use HasTranslations;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public $translatable = ['title', 'content'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Active sections in display order.
     */
    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }
}
