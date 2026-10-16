<?php

use App\Models\User;

test('navbar provides real navigation and a labelled search', function () {
    $this->get(route('website.index'))
        ->assertOk()
        ->assertSee('id="site-navbar-menu"', false)
        ->assertSee('id="site-header-search"', false)
        ->assertSee('id="navbar-search"', false)
        ->assertSee('role="search"', false)
        ->assertSee(route('website.search'))
        ->assertSee(route('website.login'))
        ->assertSee(route('website.coupons.index'))
        ->assertSee(route('website.cashbacks.index'))
        ->assertSee('data-bs-toggle="offcanvas"', false)
        ->assertSee('data-bs-dismiss="offcanvas"', false)
        ->assertSee('aria-labelledby="site-navbar-title"', false)
        ->assertDontSee('data-bs-toggle="collapse"', false);
});

test('navbar shows profile and a post logout form to signed in users', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'users')
        ->get(route('website.index'))
        ->assertOk()
        ->assertSee(route('website.profile.edit'))
        ->assertSee(route('website.logout'))
        ->assertSee('method="POST"', false)
        ->assertDontSee('onclick="', false);
});

test('inline website does not render the navbar', function () {
    $this->get(route('website.index', ['inline' => true]))
        ->assertOk()
        ->assertDontSee('id="site-navbar-menu"', false)
        ->assertDontSee('id="navbar-search"', false);
});
