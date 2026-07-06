<?php

namespace App\Livewire\Admin\Guide;

use App\Models\GuideSection;
use App\Traits\HasSearchableQueries;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasSearchableQueries;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleActive($id)
    {
        $section = GuideSection::findOrFail($id);
        $section->update(['is_active' => !$section->is_active]);
        session()->flash('message', __('admin-guide.visibility_updated'));
    }

    public function delete($id)
    {
        GuideSection::findOrFail($id)->delete();
        session()->flash('message', __('admin-guide.deleted'));
    }

    public function render()
    {
        $sections = GuideSection::query()
            ->when($this->search, function ($query) {
                $this->applySearch($query, $this->search, ['title', 'slug']);
            })
            ->orderBy('order')
            ->paginate(20);

        return view('livewire.admin.guide.index', [
            'sections' => $sections,
        ])->layout('components.layouts.admin');
    }
}
