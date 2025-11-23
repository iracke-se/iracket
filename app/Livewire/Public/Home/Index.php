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
        // Check if request is from Flutter app webview
        if ($this->isFromFlutterApp()) {
            return redirect()->to('/login');
        }
    }

    /**
     * Detect if request is coming from Flutter app webview
     */
    protected function isFromFlutterApp(): bool
    {
        $request = request();

        // Method 1: Check for custom query parameter (Flutter app should add ?source=app)
        if ($request->query('source') === 'app') {
            return true;
        }

        // Method 2: Check for custom header (Flutter app can set X-App-Source: flutter)
        if ($request->header('X-App-Source') === 'flutter') {
            return true;
        }

        // Method 3: Check User-Agent for webview indicators
        $userAgent = $request->userAgent() ?? '';

        // Common webview indicators
        $webviewIndicators = [
            'wv',           // Android WebView
            'WebView',      // Generic WebView
            'Flutter',      // Flutter specific
            '; wv)',        // Android WebView pattern
            'iPhone.*Mobile.*Safari.*wv', // iOS WebView
        ];

        foreach ($webviewIndicators as $indicator) {
            if (stripos($userAgent, $indicator) !== false) {
                return true;
            }
        }

        // Check for Android WebView specific pattern
        if (preg_match('/; wv\)/', $userAgent)) {
            return true;
        }

        // Check for iOS WKWebView (no Safari in UA but has iPhone/iPad)
        if (preg_match('/iPhone|iPad/', $userAgent) && !preg_match('/Safari/', $userAgent)) {
            return true;
        }

        return false;
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
