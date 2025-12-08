<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class RankingBadge extends Component
{
    public function __construct(
        public ?int $position = null,
        public string $category = 'men', // men, women, clubs
        public string $size = 'md', // sm, md, lg
    ) {}

    public function shouldRender(): bool
    {
        return $this->position !== null && $this->position >= 1 && $this->position <= 3;
    }

    public function badgeColor(): string
    {
        return match ($this->position) {
            1 => 'bg-yellow-400 text-yellow-900', // Gold
            2 => 'bg-gray-300 text-gray-700',     // Silver
            3 => 'bg-amber-600 text-amber-100',   // Bronze
            default => '',
        };
    }

    public function badgeIcon(): string
    {
        return match ($this->position) {
            1 => 'trophy',
            2 => 'medal',
            3 => 'award',
            default => '',
        };
    }

    public function sizeClasses(): string
    {
        return match ($this->size) {
            'sm' => 'w-5 h-5 text-xs',
            'md' => 'w-6 h-6 text-sm',
            'lg' => 'w-8 h-8 text-base',
            default => 'w-6 h-6 text-sm',
        };
    }

    public function render(): View
    {
        return view('components.ranking-badge');
    }
}
