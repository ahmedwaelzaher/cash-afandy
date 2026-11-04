<?php

use App\Http\Middleware\EnsureProfileIsComplete;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

uses(TestCase::class);

test('profile completion protects website features while leaving recovery and essential routes accessible', function () {
    $router = app(Router::class);
    foreach ([
        'website.index', 'website.offers.index', 'website.stores.index',
        'website.profile.edit', 'website.profile.update', 'website.favorites.store',
        'website.pending-action.store', 'website.notifications.index',
    ] as $name) {
        $route = $router->getRoutes()->getByName($name);
        expect($route)->not->toBeNull();
        expect($router->gatherRouteMiddleware($route))->toContain(EnsureProfileIsComplete::class);
    }

    foreach ([
        'website.profile.complete', 'website.profile.complete.store',
        'website.login', 'website.register', 'website.logout',
        'website.socialite.callback', 'website.static-pages.show',
        'website.health-check', 'website.push-subscriptions.store',
        'dashboard.login',
    ] as $name) {
        $route = $router->getRoutes()->getByName($name);
        expect($route)->not->toBeNull();
        expect($router->gatherRouteMiddleware($route))->not->toContain(EnsureProfileIsComplete::class);
    }
});

test('incomplete profiles are redirected and keep the intended destination', function (string $locale) {
    $user = Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('hasCompletedProfile')->once()->andReturn(false);
    $request = Request::create("/{$locale}/offers");
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession(app('session.store'));
    $request->setRouteResolver(fn () => app(Router::class)->getRoutes()->getByName('website.offers.index')->bind($request));
    app('url')->setRequest($request);

    $response = (new EnsureProfileIsComplete)->handle($request, fn () => abort(500));

    expect($response->getTargetUrl())->toBe(route('website.profile.complete', ['locale' => $locale]))
        ->and(session('url.intended'))->toBe($request->fullUrl());
})->with(['ar', 'en']);

test('guests and completed profiles can continue browsing', function (bool $authenticated) {
    $user = null;
    if ($authenticated) {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasCompletedProfile')->once()->andReturn(true);
    }
    $request = Request::create('/en/offers');
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureProfileIsComplete)->handle($request, fn () => response('allowed'));

    expect($response->getContent())->toBe('allowed');
})->with([false, true]);

test('incomplete JSON requests receive the completion destination', function () {
    $user = Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('hasCompletedProfile')->once()->andReturn(false);
    $request = Request::create('/en/profile', 'PUT', server: ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureProfileIsComplete)->handle($request, fn () => abort(500));

    expect($response->getStatusCode())->toBe(409)
        ->and($response->getData(true)['redirect'])->toBe(route('website.profile.complete'));
});
