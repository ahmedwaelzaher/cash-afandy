<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Redot\Http\Controllers\Controller;
use Redot\Models\Setting;
use Redot\Traits\CanUploadFile;

class SettingController extends Controller
{
    use CanUploadFile;

    /**
     * Get the settings sections.
     */
    public function sections()
    {
        return [
            'application-information' => [
                'title' => __('Information'),
                'icon' => 'fa fa-info-circle',
            ],
            'theme-customizations' => [
                'title' => __('Customizations'),
                'icon' => 'fa fa-palette',
            ],
            '3rd-party-services' => [
                'title' => __('Integrations'),
                'icon' => 'fa fa-plug',
            ],
            'social-login' => [
                'title' => __('Social Login Integrations'),
                'icon' => 'fa fa-right-to-bracket',
            ],
            'social-media' => [
                'title' => __('Social Media'),
                'icon' => 'fa fa-share-alt',
            ],
            'custom-code' => [
                'title' => __('Code'),
                'icon' => 'fa fa-code',
            ],
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return view('dashboard.settings.edit', [
            'sections' => $this->sections(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $defaults = Setting::defaults();
        $keys = array_keys($defaults);

        // Check if all validations pass
        $request->validate(Setting::rules());

        foreach ($keys as $key) {
            $value = match (true) {
                $request->hasFile($key) => $this->uploadFile($request->file($key), 'settings'),
                is_bool($defaults[$key]) => $request->boolean($key),
                default => $request->input($key),
            };

            // Skip if the value is null
            if ($value === null) {
                continue;
            }

            // Update or create the setting
            Setting::set($key, $value);
        }

        // Flush the cache
        Artisan::call('optimize:clear');

        return $this->updated(__('Settings'));
    }
}
