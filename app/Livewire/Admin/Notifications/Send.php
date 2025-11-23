<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Services\Firebase\NotificationService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class Send extends Component
{
    use WithFileUploads;

    public string $title = '';
    public string $body = '';
    public string $url = '';
    public $icon = null;
    public bool $useDefaultIcon = true;
    public array $userIds = [];
    public Collection $users;
    public bool $sending = false;

    protected $rules = [
        'title' => 'required|string|max:100',
        'body' => 'required|string|max:500',
        'url' => 'nullable|string|max:255',
        'icon' => 'nullable|image|max:1024',
    ];

    public function mount()
    {
        $userIdsParam = request()->query('users', '');
        $this->userIds = array_filter(explode(',', $userIdsParam));

        if (empty($this->userIds)) {
            session()->flash('error', 'No users selected.');
            return redirect()->route('admin.users.index');
        }

        $this->users = User::whereIn('id', $this->userIds)->get();

        if ($this->users->isEmpty()) {
            session()->flash('error', 'No valid users found.');
            return redirect()->route('admin.users.index');
        }
    }

    public function send(NotificationService $notificationService)
    {
        $this->validate();

        $this->sending = true;

        $data = [];
        if ($this->url) {
            $data['url'] = $this->url;
        }

        // Handle icon upload
        $iconPath = null;
        if ($this->icon && !$this->useDefaultIcon) {
            $iconPath = $this->icon->store('notification-icons', 'public');
        } elseif ($this->useDefaultIcon) {
            $iconPath = 'assets/images/icon.png';
        }

        // Save notification to database for all users
        $notificationsCreated = 0;
        foreach ($this->users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'admin_broadcast',
                'title' => $this->title,
                'message' => $this->body,
                'data' => !empty($data) ? $data : null,
                'icon' => $iconPath,
            ]);
            $notificationsCreated++;
        }

        // Send push notifications to users with FCM tokens
        $usersWithTokens = $this->users->filter(fn($user) => $user->fcm_token);
        $pushResult = ['success' => 0, 'failure' => 0];

        if ($usersWithTokens->isNotEmpty()) {
            $pushResult = $notificationService->sendToUsers(
                $usersWithTokens,
                $this->title,
                $this->body,
                $data
            );
        }

        $this->sending = false;

        $message = "Notification saved for {$notificationsCreated} users.";
        if ($pushResult['success'] > 0) {
            $message .= " Push sent to {$pushResult['success']} devices.";
        }
        if ($usersWithTokens->isEmpty()) {
            $message .= " No users have push notifications enabled.";
        }

        session()->flash('message', $message);

        return redirect()->route('admin.users.index');
    }

    public function cancel()
    {
        return redirect()->route('admin.users.index');
    }

    public function render()
    {
        $usersWithToken = $this->users->filter(fn($user) => $user->fcm_token)->count();
        $usersWithoutToken = $this->users->count() - $usersWithToken;

        return view('livewire.admin.notifications.send', [
            'usersWithToken' => $usersWithToken,
            'usersWithoutToken' => $usersWithoutToken,
        ])->layout('components.layouts.admin');
    }
}
