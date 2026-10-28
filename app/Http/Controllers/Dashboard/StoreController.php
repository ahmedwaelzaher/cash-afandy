<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Category;
use App\Models\Country;
use App\Models\Store;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;
use Redot\Traits\CanUploadFile;

class StoreController extends Controller
{
    use CanUploadFile;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.stores.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.stores.create', [
            'categories' => Category::query(),
            'countries' => Country::query(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'slug' => 'required|string|alpha_dash|unique:stores,slug',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'url' => 'required|url',
            'logo' => 'required|image',
            'active' => 'nullable|boolean',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'countries' => 'required|array',
            'countries.*' => 'exists:countries,id',
        ]);

        $validated['active'] = $request->boolean('active');

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->uploadFile($request->file('logo'), 'stores');
        }

        $store = Store::create($validated);
        $store->categories()->attach($validated['categories']);
        $store->countries()->attach($validated['countries']);

        return $this->created(__('Store'), 'dashboard.stores.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        return view('dashboard.stores.edit', [
            'store' => $store,
            'categories' => Category::query(),
            'countries' => Country::query(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'slug' => 'required|string|alpha_dash|unique:stores,slug,' . $store->id,
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'url' => 'required|url',
            'logo' => 'nullable|image',
            'active' => 'nullable|boolean',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'countries' => 'required|array',
            'countries.*' => 'exists:countries,id',
        ]);

        $validated['active'] = $request->boolean('active');

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->uploadFile($request->file('logo'), 'stores');
        }

        $store->update($validated);
        $store->categories()->sync($validated['categories']);
        $store->countries()->sync($validated['countries']);

        return $this->updated(__('Store'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $store->delete();

        return $this->deleted(__('Store'));
    }
}
