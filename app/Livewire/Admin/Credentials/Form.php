<?php

namespace App\Livewire\Admin\Credentials;

use App\Models\Credential;
use Livewire\Component;

class Form extends Component
{
    public ?Credential $credential = null;

    public string $title = '';
    public string $url = '';
    public string $username = '';
    public string $password = '';
    public string $notes = '';

    public function mount($id = null)
    {
        if ($id) {
            $this->credential = Credential::findOrFail($id);
            $this->title = $this->credential->title;
            $this->url = $this->credential->url ?? '';
            $this->username = $this->credential->username ?? '';
            $this->password = $this->credential->password ?? '';
            $this->notes = $this->credential->notes ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'url' => 'nullable|url|max:2048',
            'username' => 'nullable|string|max:1000',
            'password' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:5000',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        $data = [
            'title' => $this->title,
            'url' => $this->url ?: null,
            'username' => $this->username ?: null,
            'password' => $this->password ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->credential) {
            $this->credential->update($data);
            session()->flash('message', __('admin-credentials.updated'));
        } else {
            $data['created_by'] = auth()->id();
            Credential::create($data);
            session()->flash('message', __('admin-credentials.created'));
        }

        return redirect()->route('admin.credentials.index');
    }

    public function render()
    {
        return view('livewire.admin.credentials.form')
            ->layout('components.layouts.admin');
    }
}
