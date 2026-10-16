<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;
use Redot\Jobs\ExtractLanguageTokens;
use Redot\Jobs\PublishLanguageTokens;
use Redot\Jobs\RevertLanguageTokens;
use Redot\Models\Language;
use Redot\Models\LanguageToken;

class LanguageTokenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Language $language)
    {
        return view('dashboard.languages.tokens.index', [
            'language' => $language,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Language $language, LanguageToken $token)
    {
        return view('dashboard.languages.tokens.edit', [
            'language' => $language,
            'token' => $token,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language, LanguageToken $token)
    {
        $request->validate([
            'value' => 'required|string',
        ]);

        $token->update([
            'value' => $request->input('value'),
        ]);

        return $this->updated(__('Language token'));
    }

    /**
     * Extract language tokens from the language files.
     */
    public function extract(Language $language)
    {
        ExtractLanguageTokens::dispatchSync($language);

        return $this->success(__('Language tokens have been extracted successfully.'));
    }

    /**
     * Publish language tokens to the language files.
     */
    public function publish(Language $language)
    {
        PublishLanguageTokens::dispatchSync($language);

        return $this->success(__('Language tokens published successfully.'), 'dashboard.languages.tokens.index', $language);
    }

    /**
     * Revert language tokens to their original translations.
     */
    public function revert(Language $language)
    {
        RevertLanguageTokens::dispatchSync($language);

        return $this->success(__('Language tokens reverted successfully.'), 'dashboard.languages.tokens.index', $language);
    }
}
