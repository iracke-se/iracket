<?php

namespace App\Livewire\Admin\Contacts;

use App\Models\Contact;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();
        session()->flash('message', __('admin-contacts.deleted_success'));
    }

    public function render()
    {
        $contacts = Contact::query()
            ->with('repliedBy')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('message', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Stats
        $totalContacts = Contact::count();
        $pendingContacts = Contact::pending()->count();
        $repliedContacts = Contact::replied()->count();
        $contactsThisMonth = Contact::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return view('livewire.admin.contacts.index', [
            'contacts' => $contacts,
            'totalContacts' => $totalContacts,
            'pendingContacts' => $pendingContacts,
            'repliedContacts' => $repliedContacts,
            'contactsThisMonth' => $contactsThisMonth,
        ])->layout('components.layouts.admin');
    }
}
