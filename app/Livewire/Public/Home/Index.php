<?php

namespace App\Livewire\Public\Home;

use App\Mail\Admin\NewContact;
use App\Models\Contact;
use App\Models\Term;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Index extends Component
{
    public string $name = '';
    public string $email = '';
    public string $message = '';

    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email',
        'message' => 'required|min:10',
    ];

    public function mount()
    {
        // The Flutter app never shows the public landing page — redirect it to
        // the login screen. Detection lives in the Request::isWebviewApp() macro
        // (see AppServiceProvider) so it stays consistent across the app.
        if (request()->isWebviewApp()) {
            return redirect()->to('/login');
        }
    }

    public function submitContact()
    {
        $this->validate();

        // Save contact to database
        $contact = Contact::create([
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
        ]);

        // Send notification email to admin
        $adminEmail = config('mail.admin_address', config('mail.from.address'));
        Mail::to($adminEmail)->send(new NewContact($contact));

        // Reset form
        $this->reset(['name', 'email', 'message']);

        session()->flash('contact_success', 'Tack för ditt meddelande! Vi återkommer så snart som möjligt.');
    }

    public function render()
    {
        return view('livewire.public.home.index', [
            'terms' => Term::where('is_active', true)->get(),
        ])->layout('components.layouts.public.landing');
    }
}
