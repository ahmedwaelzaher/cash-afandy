<?php

namespace App\View\Layouts;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Redot\Models\Language;

class Scaffold extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $direction = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        $this->direction ??= Language::current()->direction;

        if ($this->title) {
            $this->title = $this->title . ' | ' . app_name();
        } else {
            $this->title = app_name();
        }

        $firebase = config('redot.notifications.firebase', []);
        $pushEnabled = config('redot.notifications.push_enabled', false)
            && filled(config('redot.notifications.vapid_key'))
            && collect(['apiKey', 'projectId', 'messagingSenderId', 'appId'])->every(fn ($key) => filled($firebase[$key] ?? null));
        $cacheEnabled = app()->environment('production') && setting('service_worker_enabled');
        $parameters = ['cache' => (int) $cacheEnabled];
        if ($pushEnabled) {
            $parameters['firebase'] = json_encode($firebase);
            $parameters['sdk'] = config('redot.notifications.sdk_version');
        }

        return view('layouts.scaffold', [
            'pushConfig' => [
                'enabled' => (bool) $pushEnabled,
                'firebase' => $firebase,
                'vapidKey' => config('redot.notifications.vapid_key'),
                'sdkVersion' => config('redot.notifications.sdk_version'),
            ],
            'serviceWorkerEnabled' => $pushEnabled || $cacheEnabled,
            'serviceWorkerUrl' => '/service-worker.js?' . http_build_query($parameters),
        ]);
    }
}
