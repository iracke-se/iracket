<?php

namespace App\Livewire\Admin\Contacts;

use App\Mail\Admin\ContactReply;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Respond extends Component
{
    public Contact $contact;
    public string $replyMessage = '';

    protected $rules = [
        'replyMessage' => 'required|min:10',
    ];

    public function mount($id)
    {
        $this->contact = Contact::findOrFail($id);

        // Pre-fill with existing reply if already replied
        if ($this->contact->reply_message) {
            $this->replyMessage = $this->contact->reply_message;
        }
    }

    public function sendReply()
    {
        $this->validate();

        // Mark contact as replied
        $this->contact->markAsReplied(auth()->user(), $this->replyMessage);

        // Send email to the contact
        Mail::to($this->contact->email)->send(new ContactReply($this->contact));

        session()->flash('message', __('admin-contacts.reply_sent'));

        return redirect()->route('admin.contacts.index');
    }

    public function render()
    {
        return view('livewire.admin.contacts.respond')
            ->layout('components.layouts.admin');
    }
}
