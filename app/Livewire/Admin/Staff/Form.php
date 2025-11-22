<?php

namespace App\Livewire\Admin\Staff;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    public ?User $user = null;
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public ?string $phone_number = '';
    public string $password = '';
    public string $password_confirmation = '';
    public array $selectedRoles = [];
    public bool $visible_in_players = false;

    public function mount($id = null)
    {
        if ($id) {
            $this->user = User::findOrFail($id);
            $this->first_name = $this->user->first_name;
            $this->last_name = $this->user->last_name;
            $this->email = $this->user->email;
            $this->phone_number = $this->user->phone_number ?? '';
            $this->selectedRoles = $this->user->roles->pluck('name')->toArray();
            $this->visible_in_players = $this->user->visible_in_players ?? false;
        } else {
            // Default roles for new staff
            $this->selectedRoles = ['Manager'];
        }
    }

    public function save()
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user?->id),
            ],
            'phone_number' => 'nullable|string|max:20',
            'selectedRoles' => 'required|array|min:1',
            'visible_in_players' => 'boolean',
        ];

        if (!$this->user || $this->password) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $validated = $this->validate($rules);

        $userData = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number ?: null,
            'visible_in_players' => $this->visible_in_players,
        ];

        if ($this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        if ($this->user) {
            $this->user->update($userData);
            $this->user->syncRoles($this->selectedRoles);
            session()->flash('message', 'Staff member updated successfully.');
        } else {
            $userData['email_verified_at'] = now();
            $user = User::create($userData);
            $user->syncRoles($this->selectedRoles);
            session()->flash('message', 'Staff member created successfully.');
        }

        return redirect()->route('admin.staff.index');
    }

    public function render()
    {
        $roles = Role::whereIn('name', ['Admin', 'Manager'])->get();

        return view('livewire.admin.staff.form', [
            'roles' => $roles,
        ])->layout('components.layouts.admin');
    }
}
