<?php

use App\Models\User;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

uses(TestCase::class);

beforeEach(function () {
    $this->previousResolver = Model::getConnectionResolver();
    $connection = Mockery::mock(SQLiteConnection::class)->makePartial();
    $connection->__construct(null, ':memory:', '', ['driver' => 'sqlite']);
    $connection->shouldReceive('select')->andReturn([(object) ['id' => 1, 'code' => 'eg']]);
    $resolver = Mockery::mock(ConnectionResolverInterface::class);
    $resolver->shouldReceive('connection')->andReturn($connection);
    Model::setConnectionResolver($resolver);
    cache()->forever('settings.default_website_country', 'eg');
    cache()->forever('settings.show_website_countries_dropdown', false);
});

afterEach(function () {
    Model::setConnectionResolver($this->previousResolver);
});

function profileLayoutDocument(array $errors = []): DOMXPath
{
    $user = new User(['first_name' => 'Sample', 'last_name' => 'User', 'email' => 'sample@example.com']);
    $source = file_get_contents(resource_path('views/website/profile/edit.blade.php'));
    // Exercise the real account content without rendering database-backed global navigation.
    $source = str_replace(['<x-layouts::website :title="__(\'Account\')">', '</x-layouts::website>'], ['<div>', '</div>'], $source);
    $html = Blade::render($source, [
        'user' => $user,
        'details' => ['phone' => null, 'birthdate' => null, 'gender' => 'female'],
        'errors' => (new ViewErrorBag)->put('default', new MessageBag($errors)),
    ]);
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);

    return new DOMXPath($document);
}

test('account sections keep profile and password submissions independent', function (string $locale) {
    app()->setLocale($locale);
    $xpath = profileLayoutDocument();
    $profile = '//div[@id="account-profile"]/form';
    $password = '//div[@id="account-password"]/form';

    expect($xpath->query('//a[@role="tab"]')->length)->toBe(3);
    foreach (['profile', 'password', 'notifications'] as $section) {
        expect($xpath->query('//a[@aria-controls="account-' . $section . '"]')->length)->toBe(1)
            ->and($xpath->query('//div[@id="account-' . $section . '"][@role="tabpanel"]')->length)->toBe(1)
            ->and($xpath->query('//div[@id="account-' . $section . '"]/h2')->length)->toBe(1)
            ->and($xpath->query('//div[@id="account-' . $section . '"]//form//h2')->length)->toBe(0);
    }
    foreach (['first_name', 'last_name', 'email', 'phone', 'birthdate', 'gender'] as $field) {
        expect($xpath->query($profile . '//input[@name="' . $field . '"]')->length)->toBeGreaterThan(0)
            ->and($xpath->query($password . '//input[@name="' . $field . '"]')->length)->toBe(0);
    }
    foreach (['password', 'password_confirmation'] as $field) {
        expect($xpath->query($password . '//input[@name="' . $field . '"]')->length)->toBe(1)
            ->and($xpath->query($profile . '//input[@name="' . $field . '"]')->length)->toBe(0);
    }
    expect($xpath->query('//form[@action="' . route('website.profile.update') . '"]//input[@name="_method"][@value="PUT"]')->length)->toBe(2)
        ->and($xpath->query('//form//input[@name="_token"]')->length)->toBe(3)
        ->and($xpath->query('//input[@name="gender"][@value="female"][@checked]')->length)->toBe(1)
        ->and($xpath->query('//div[@id="account-notifications"]//input[@name="notifications_enabled"][@type="checkbox"]')->length)->toBe(1)
        ->and($xpath->query('//div[@id="account-notifications"]//input[@name="notification_types[]"][@type="checkbox"]')->length)->toBe(1)
        ->and($xpath->query('//div[@id="account-notifications"]//div[@visible-when="$account-notifications-enabled"]')->length)->toBe(2)
        ->and($xpath->query('//div[@id="account-notifications"]//select')->length)->toBe(0)
        ->and($xpath->query('//div[@id="account-notifications"]//*[@data-push-status]')->length)->toBe(0)
        ->and($xpath->query('//div[@id="account-notifications"]//form[@action="' . route('website.profile.preferences.update') . '"]')->length)->toBe(1)
        ->and($xpath->query('//div[@id="account-notifications"]//button[@type="submit"]')->length)->toBe(1)
        ->and($xpath->query('//div[@id="account-notifications"]//div[@data-browser-notifications]//h2')->length)->toBe(0);
})->with(['ar', 'en']);

test('account validation opens the section that contains the invalid field', function (string $field, string $section) {
    $xpath = profileLayoutDocument([$field => ['Invalid value']]);

    expect($xpath->query('//a[@id="account-' . $section . '-tab"][@aria-selected="true"]')->length)->toBe(1)
        ->and($xpath->query('//div[@id="account-' . $section . '"][contains(concat(" ", normalize-space(@class), " "), " active ")]')->length)->toBe(1);
})->with([['password', 'password'], ['email', 'profile'], ['notification_types.0', 'notifications']]);

test('registration keeps the shared gender field required without optional profile fields', function () {
    $html = view('website.profile.partials.personal-details', [
        'showPhone' => false, 'showBirthdate' => false, 'requireGender' => true,
    ])->render();

    expect($html)->toContain('name="gender"', 'validation="required"', 'class="mb-3"')
        ->not->toContain('name="phone"', 'name="birthdate"', 'col-md-6');
});
