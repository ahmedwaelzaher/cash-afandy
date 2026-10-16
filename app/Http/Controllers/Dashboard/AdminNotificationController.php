<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\NotificationLevel;
use App\Models\Admin;
use App\Notifications\DashboardNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Enum;
use Redot\Http\Controllers\Controller;

class AdminNotificationController extends Controller
{
    /**
     * Show the form for sending a notification to administrators.
     */
    public function create()
    {
        $admins = Admin::active();

        return view('dashboard.admin-notifications.create', [
            'admins' => $admins,
            'levels' => NotificationLevel::values(),
        ]);
    }

    /**
     * Send a custom notification to the selected administrators.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'admins' => ['required', 'array'],
            'admins.*' => ['exists:admins,id'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'level' => ['required', new Enum(NotificationLevel::class)],
        ]);

        $admins = Admin::whereIn('id', $validated['admins'])->get();

        Notification::send($admins, DashboardNotification::make(
            title: $validated['title'],
            url: $validated['url'] ?? null,
            level: $validated['level'],
            lightbox: $request->boolean('lightbox'),
        ));

        return $this->success(__('Notification has been sent.'), 'dashboard.admin-notifications.create');
    }
}
