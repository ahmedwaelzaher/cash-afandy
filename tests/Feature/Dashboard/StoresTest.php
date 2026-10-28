<?php

use App\Http\Controllers\Dashboard\StoreController;
use App\Models\Admin;
use App\Models\Broker;
use App\Models\Category;
use App\Models\Country;
use App\Models\Representative;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

beforeEach(function () {
    $this->withoutMiddleware(EnsureDependenciesBuilt::class);
    $this->actingAs(Admin::factory()->create(), 'admins');
});

test('renamed store dashboard pages render', function (string $action, string $locale) {
    $store = Store::factory()->create();

    $this->get(route('dashboard.stores.' . $action, [
        'locale' => $locale,
        ...($action === 'edit' ? ['store' => $store] : []),
    ]))->assertOk();
})->with(['index', 'create', 'edit'])->with(['ar', 'en']);

test('stores can be created updated and deleted with their category and country links', function () {
    $category = Category::factory()->create();
    $replacement = Category::factory()->create();
    $country = Country::where('code', 'eg')->firstOrFail();
    $this->partialMock(StoreController::class, function ($mock) {
        $mock->shouldReceive('uploadFile')->once()->with(Mockery::type(UploadedFile::class), 'stores')
            ->andReturn('https://example.test/store-logo.png');
    });
    $data = [
        'title' => ['en' => 'Renamed store', 'ar' => 'متجر جديد'],
        'slug' => 'renamed-store',
        'url' => 'https://example.test',
        'logo' => UploadedFile::fake()->image('store.png'),
        'active' => true,
        'categories' => [$category->id],
        'countries' => [$country->id],
    ];

    $this->post(route('dashboard.stores.store'), $data)->assertSessionHasNoErrors();
    $store = Store::where('slug', 'renamed-store')->firstOrFail();
    $this->assertDatabaseHas('category_store', ['store_id' => $store->id, 'category_id' => $category->id]);
    $this->assertDatabaseHas('country_store', ['store_id' => $store->id, 'country_id' => $country->id]);

    unset($data['logo']);
    $data['categories'] = [$replacement->id];
    $this->put(route('dashboard.stores.update', $store), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('category_store', ['store_id' => $store->id, 'category_id' => $category->id]);
    $this->assertDatabaseHas('category_store', ['store_id' => $store->id, 'category_id' => $replacement->id]);

    $this->delete(route('dashboard.stores.destroy', $store))->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    $this->assertDatabaseMissing('category_store', ['store_id' => $store->id]);
    $this->assertDatabaseMissing('country_store', ['store_id' => $store->id]);
});

test('representative assignments use store ids and validate the referenced store', function () {
    $broker = Broker::factory()->create();
    $store = Store::factory()->create();
    $country = Country::where('code', 'eg')->firstOrFail();
    $data = [
        'first_name' => 'Store',
        'last_name' => 'Representative',
        'stores' => [['store_id' => $store->id, 'country_id' => $country->id]],
    ];

    $this->post(route('dashboard.brokers.representatives.store', $broker), $data)->assertSessionHasNoErrors();
    $representative = Representative::where('broker_id', $broker->id)->firstOrFail();
    expect($representative->stores)->toBe($data['stores']);

    $data['stores'][0]['store_id'] = Store::max('id') + 1;
    $this->put(route('dashboard.brokers.representatives.update', [$broker, $representative]), $data)
        ->assertSessionHasErrors('stores.0.store_id');
    expect($representative->fresh()->stores)->toBe($representative->stores);
});
