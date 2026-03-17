<?php

namespace App\Livewire\User\Notifications;

use App\Models\Notification;
use Livewire\Component;

class Index extends Component
{
    public function markAsRead(int $id)
    {
        $notification = Notification::where('user_id', auth()->id())->find($id);

        if ($notification) {
            $notification->markAsRead();

            $url = $notification->data['url'] ?? null;
            if ($url) {
                $this->redirect($url, navigate: true);
            }
        }
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadCount = $notifications->whereNull('read_at')->count();

        return view('livewire.user.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ])->layout('components.layouts.app');
    }
}
