<?php

namespace App\Livewire\Admin\Terms;

use App\Models\Term;
use Illuminate\Support\Str;
use Livewire\Component;

class Form extends Component
{
    public ?Term $term = null;
    public string $title = '';
    public string $slug = '';
    public string $content = '';
    public bool $is_active = true;

    protected $listeners = ['contentUpdated'];

    public function mount($id = null)
    {
        if ($id) {
            $this->term = Term::findOrFail($id);
            $this->title = $this->term->title;
            $this->slug = $this->term->slug;
            $this->content = $this->term->content ?? '';
            $this->is_active = $this->term->is_active;
        }
    }

    public function updatedTitle($value)
    {
        if (!$this->term) {
            $this->slug = Str::slug($value);
        }
    }

    public function contentUpdated($content)
    {
        $this->content = $content;
    }

    public function save()
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:terms,slug,' . ($this->term?->id ?? 'NULL'),
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($this->term) {
            $this->term->update($validated);
            session()->flash('message', 'Term updated successfully.');
        } else {
            Term::create($validated);
            session()->flash('message', 'Term created successfully.');
        }

        return redirect()->route('admin.terms.index');
    }

    public function render()
    {
        return view('livewire.admin.terms.form')
            ->layout('components.layouts.admin');
    }
}
