<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    public const POSITIONS = [
        'top_sticky' => 'Top Sticky',
        'bottom_sticky' => 'Bottom Sticky',
        'top' => 'Top',
        'bottom' => 'Bottom',
        'within_page' => 'Within Page',
        'random' => 'Random',
        'popup' => 'Popup',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'scheduled' => 'Scheduled',
    ];

    public const LOCATIONS = [
        'home' => 'Home',
        'players' => 'Players',
        'matches' => 'Matches',
        'clubs' => 'Clubs',
        'bubbler' => 'Bubbler',
        'profile' => 'Profile',
        'settings' => 'Settings',
    ];

    protected $fillable = [
        'name',
        'image',
        'position',
        'views',
        'clicks',
        'locations',
        'link',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'locations' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'views' => 'integer',
        'clicks' => 'integer',
    ];

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    public function getClickThroughRateAttribute(): float
    {
        if ($this->views === 0) {
            return 0;
        }

        return round(($this->clicks / $this->views) * 100, 2);
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return '';
        }

        // Check if it's a public asset path (starts with 'assets/')
        if (str_starts_with($this->image, 'assets/')) {
            return asset($this->image);
        }

        // Otherwise use storage URL
        return \Storage::url($this->image);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now()->toDateString();

        if ($this->start_date && $this->start_date->toDateString() > $now) {
            return false;
        }

        if ($this->end_date && $this->end_date->toDateString() < $now) {
            return false;
        }

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeForLocation($query, string $location)
    {
        return $query->where(function ($q) use ($location) {
            $q->whereNull('locations')
                ->orWhereRaw('JSON_LENGTH(locations) = 0')
                ->orWhereJsonContains('locations', $location);
        });
    }
}
