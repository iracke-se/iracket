<?php

namespace App\Livewire\Admin\Guide;

use App\Models\GuideSection;
use Illuminate\Support\Str;
use Livewire\Component;

class Form extends Component
{
    public ?GuideSection $section = null;
    public array $title = ['en' => '', 'sv' => ''];
    public string $slug = '';
    public array $content = ['en' => '', 'sv' => ''];
    public string $icon = '';
    public int $order = 0;
    public bool $is_active = true;
    public string $activeLocale = 'en';

    public array $availableLocales = [
        'en' => 'English',
        'sv' => 'Svenska',
    ];

    protected $listeners = ['contentUpdated'];

    public function mount($id = null)
    {
        if ($id) {
            $this->section = GuideSection::findOrFail($id);

            foreach (array_keys($this->availableLocales) as $locale) {
                $this->title[$locale] = $this->section->getTranslation('title', $locale, false) ?? '';
                $this->content[$locale] = $this->section->getTranslation('content', $locale, false) ?? '';
            }

            $this->slug = $this->section->slug;
            $this->icon = $this->section->icon ?? '';
            $this->order = $this->section->order;
            $this->is_active = $this->section->is_active;
        } else {
            $this->order = (int) (GuideSection::max('order') ?? 0) + 1;
        }
    }

    public function updatedTitle($value)
    {
        if (!$this->section && isset($this->title['en'])) {
            $this->slug = Str::slug($this->title['en']);
        }
    }

    public function setActiveLocale($locale)
    {
        $this->activeLocale = $locale;
        $this->dispatch('localeChanged', locale: $locale, content: $this->content[$locale] ?? '');
    }

    public function contentUpdated($content)
    {
        $this->content[$this->activeLocale] = $content;
    }

    public function save()
    {
        $this->validate([
            'title.en' => 'required|string|max:255',
            'title.sv' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255|unique:guide_sections,slug,' . ($this->section?->id ?? 'NULL'),
            'content.en' => 'required|string',
            'content.sv' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
        ], [
            'title.en.required' => __('The English title is required.'),
            'content.en.required' => __('The English content is required.'),
        ]);

        $data = [
            'title' => array_filter($this->title),
            'slug' => $this->slug,
            'content' => array_filter($this->content),
            'icon' => $this->icon ?: null,
            'order' => $this->order,
            'is_active' => $this->is_active,
        ];

        if ($this->section) {
            $this->section->update($data);
            session()->flash('message', __('admin-guide.updated'));
        } else {
            GuideSection::create($data);
            session()->flash('message', __('admin-guide.created'));
        }

        return redirect()->route('admin.guide.index');
    }

    public function render()
    {
        return view('livewire.admin.guide.form')
            ->layout('components.layouts.admin');
    }
}
