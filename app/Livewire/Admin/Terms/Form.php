<?php

namespace App\Livewire\Admin\Terms;

use App\Models\Term;
use Illuminate\Support\Str;
use Livewire\Component;

class Form extends Component
{
    public ?Term $term = null;
    public array $title = ['en' => '', 'sv' => ''];
    public string $slug = '';
    public array $content = ['en' => '', 'sv' => ''];
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
            $this->term = Term::findOrFail($id);

            // Load translations for each locale
            foreach (array_keys($this->availableLocales) as $locale) {
                $this->title[$locale] = $this->term->getTranslation('title', $locale, false) ?? '';
                $this->content[$locale] = $this->term->getTranslation('content', $locale, false) ?? '';
            }

            $this->slug = $this->term->slug;
            $this->is_active = $this->term->is_active;
        }
    }

    public function updatedTitle($value)
    {
        // Auto-generate slug from English title when creating new term
        if (!$this->term && isset($this->title['en'])) {
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
            'slug' => 'required|string|max:255|unique:terms,slug,' . ($this->term?->id ?? 'NULL'),
            'content.en' => 'required|string',
            'content.sv' => 'nullable|string',
            'is_active' => 'boolean',
        ], [
            'title.en.required' => __('The English title is required.'),
            'content.en.required' => __('The English content is required.'),
        ]);

        $data = [
            'title' => array_filter($this->title),
            'slug' => $this->slug,
            'content' => array_filter($this->content),
            'is_active' => $this->is_active,
        ];

        if ($this->term) {
            $this->term->update($data);
            session()->flash('message', __('admin-terms.term_updated'));
        } else {
            Term::create($data);
            session()->flash('message', __('admin-terms.term_created'));
        }

        return redirect()->route('admin.terms.index');
    }

    public function render()
    {
        return view('livewire.admin.terms.form')
            ->layout('components.layouts.admin');
    }
}
