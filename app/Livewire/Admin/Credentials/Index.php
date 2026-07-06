<?php

namespace App\Livewire\Admin\Credentials;

use App\Models\Credential;
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

    public function delete($id)
    {
        Credential::findOrFail($id)->delete();
        session()->flash('message', __('admin-credentials.deleted'));
    }

    public function render()
    {
        $credentials = Credential::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $this->applySearch($q, $this->search, ['title', 'url']);
                });
            })
            ->orderBy('title')
            ->paginate(15);

        return view('livewire.admin.credentials.index', [
            'credentials' => $credentials,
        ])->layout('components.layouts.admin');
    }
}
