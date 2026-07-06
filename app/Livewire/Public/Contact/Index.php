<?php

namespace App\Livewire\Public\Contact;

use App\Mail\Admin\NewContact;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Index extends Component
{
    public string $name = '';
    public string $email = '';
    public string $message = '';
    public bool $sent = false;

    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email',
        'message' => 'required|min:10',
    ];

    public function mount()
    {
        // Pre-fill for signed-in users.
        if ($user = auth()->user()) {
            $this->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? '');
            $this->email = $user->email ?? '';
        }
    }

    public function submit()
    {
        $this->validate();

        $contact = Contact::create([
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
        ]);

        // Notify the admin inbox.
        $adminEmail = config('mail.admin_address', config('mail.from.address'));
        Mail::to($adminEmail)->send(new NewContact($contact));

        $this->reset('message');
        $this->sent = true;
    }

    public function render()
    {
        $layout = auth()->check()
            ? 'components.layouts.app'
            : 'components.layouts.auth.wide';

        return view('livewire.public.contact.index')
            ->layout($layout);
    }
}
