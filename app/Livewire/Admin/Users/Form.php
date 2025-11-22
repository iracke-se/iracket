<?php

namespace App\Livewire\Admin\Users;

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
    public ?string $gender = '';
    public ?int $age = null;
    public string $password = '';
    public string $password_confirmation = '';
    public array $selectedRoles = [];
    public bool $visible_in_players = true;

    public function mount($id = null)
    {
        if ($id) {
            $this->user = User::findOrFail($id);
            $this->first_name = $this->user->first_name;
            $this->last_name = $this->user->last_name;
            $this->email = $this->user->email;
            $this->phone_number = $this->user->phone_number ?? '';
            $this->gender = $this->user->gender ?? '';
            $this->age = $this->user->age;
            $this->selectedRoles = $this->user->roles->pluck('name')->toArray();
            $this->visible_in_players = $this->user->visible_in_players ?? true;
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
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'age' => 'nullable|integer|min:1|max:120',
            'selectedRoles' => 'array',
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
            'gender' => $this->gender ?: null,
            'age' => $this->age,
            'visible_in_players' => $this->visible_in_players,
        ];

        if ($this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        if ($this->user) {
            $this->user->update($userData);
            $this->user->syncRoles($this->selectedRoles);
            session()->flash('message', 'User updated successfully.');
        } else {
            $userData['email_verified_at'] = now();
            $user = User::create($userData);
            $user->syncRoles($this->selectedRoles);
            session()->flash('message', 'User created successfully.');
        }

        return redirect()->route('admin.users.index');
    }

    public function render()
    {
        return view('livewire.admin.users.form', [
            'roles' => Role::all(),
        ])->layout('components.layouts.admin');
    }
}
