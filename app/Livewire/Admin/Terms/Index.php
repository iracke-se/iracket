<?php

namespace App\Livewire\Admin\Terms;

use App\Models\Term;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        Term::findOrFail($id)->delete();
        session()->flash('message', 'Term deleted successfully.');
    }

    public function render()
    {
        $terms = Term::query()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('slug', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Stats
        $totalTerms = Term::count();
        $activeTerms = Term::where('is_active', true)->count();
        $inactiveTerms = Term::where('is_active', false)->count();

        return view('livewire.admin.terms.index', [
            'terms' => $terms,
            'totalTerms' => $totalTerms,
            'activeTerms' => $activeTerms,
            'inactiveTerms' => $inactiveTerms,
        ])->layout('components.layouts.admin');
    }
}
