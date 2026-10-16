<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\StaticPage;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class StaticPageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.static-pages.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.static-pages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'content' => 'nullable|array',
            'content.*' => 'nullable|string',
        ]);

        $validated['active'] = $request->has('active');

        StaticPage::create($validated);

        return $this->created(__('Page'), 'dashboard.static-pages.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(StaticPage $staticPage)
    {
        return view('dashboard.static-pages.show', [
            'staticPage' => $staticPage,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StaticPage $staticPage)
    {
        return view('dashboard.static-pages.edit', [
            'staticPage' => $staticPage,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StaticPage $staticPage)
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'content' => 'nullable|array',
            'content.*' => 'nullable|string',
        ]);

        $validated['active'] = $request->has('active');

        $staticPage->update($validated);

        return $this->updated(__('Page'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StaticPage $staticPage)
    {
        $staticPage->delete();

        return $this->deleted(__('Page'), 'dashboard.static-pages.index');
    }
}
