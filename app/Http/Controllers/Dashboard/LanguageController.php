<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Redot\Http\Controllers\Controller;
use Redot\Models\Language;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.languages.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.languages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|alpha|max:2|unique:languages,code',
            'source' => 'required|in:' . implode(',', array_keys(config('app.locales'))),
            'direction' => 'required|in:ltr,rtl',
        ]);

        // Set the is_rtl attribute based on the direction attribute.
        $validated['is_rtl'] = $validated['direction'] === 'rtl';

        $language = Language::create($validated);

        // Copy the source language files to the new language directory.
        if (! is_dir(lang_path($validated['code']))) {
            File::copyDirectory(lang_path($validated['source']), lang_path($validated['code']));
        }

        // Copy the source json file to the new language json file.
        if (! is_dir(lang_path($validated['code'] . '.json'))) {
            File::copy(lang_path($validated['source'] . '.json'), lang_path($validated['code'] . '.json'));
        }

        // Copy the source database token to the new language database token.
        $language->tokens()->createMany(
            Language::where('code', $validated['source'])->first()->tokens->map(function ($token) {
                return $token->only(['key', 'value', 'original_translation', 'from_json', 'is_published']);
            })->toArray()
        );

        return $this->created(__('Language'), 'dashboard.languages.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Language $language)
    {
        return view('dashboard.languages.edit', [
            'language' => $language,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'direction' => 'required|in:ltr,rtl',
        ]);

        // Set the is_rtl attribute based on the direction attribute.
        $validated['is_rtl'] = $validated['direction'] === 'rtl';

        $language->update($validated);

        return $this->updated(__('Language'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Language $language)
    {
        // Prevent deleting if there is only one language.
        if (Language::count() === 1) {
            return $this->error(__('You cannot delete the last language.'));
        }

        // Prevent deleting if the language is used in the website locales.
        if (in_array($language->code, setting('website_locales'))) {
            return $this->error(__('You cannot delete this language because it is used in the website locales.'));
        }

        // Prevent deleting if the language is used in the dashboard locales.
        if (in_array($language->code, setting('dashboard_locales'))) {
            return $this->error(__('You cannot delete this language because it is used in the dashboard locales.'));
        }

        // Prevent deleting if the language is the application language.
        if (app()->getLocale() === $language->code) {
            return $this->error(__('Change the application language before deleting this language.'));
        }

        $language->delete();

        // Delete the language directory.
        File::deleteDirectory(lang_path($language->code));

        // Delete the language json file.
        File::delete(lang_path($language->code . '.json'));

        return $this->deleted(__('Language'));
    }
}
