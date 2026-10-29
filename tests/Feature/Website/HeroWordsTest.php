<?php

test('hero words use only the current language and escape configured text', function (string $locale) {
    app()->setLocale($locale);
    cache()->forever('settings.hero_words', [
        ['word' => ['ar' => 'هدية', 'en' => '<img src=x onerror=alert(1)>']],
        ['word' => ['ar' => '  كوبون  ', 'en' => 'gift']],
        ['word' => ['ar' => 'هدية', 'en' => '<img src=x onerror=alert(1)>']],
        ['word' => ['ar' => ' ', 'en' => '']],
    ]);

    $html = view('website.home.partials.hero', ['heroStores' => collect()])->render();
    preg_match('/typed-js="([^"]+)"/', $html, $matches);
    $words = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);

    expect($words)->toBe($locale === 'ar' ? ['هدية', 'كوبون'] : ['<img src=x onerror=alert(1)>', 'gift'])
        ->and($html)->not->toContain('<img src=x onerror=alert(1)>')
        ->and($html)->toContain(
            'aria-hidden="true"', 'visually-hidden',
            'typed-type-speed="90"', 'typed-back-speed="55"', 'typed-back-delay="2200"', 'typed-start-delay="0"',
            'typed-loop="true"', 'typed-show-cursor="true"', 'typed-cursor-char=""',
        );
})->with(['ar', 'en']);

test('hero keeps its original heading when no words exist in the current language', function (string $locale) {
    app()->setLocale($locale);
    cache()->forever('settings.hero_words', [['word' => [$locale === 'ar' ? 'en' : 'ar' => 'Other language']]]);

    $html = view('website.home.partials.hero', ['heroStores' => collect()])->render();

    expect($html)->toContain(e(__(':word in hand', ['word' => __('Offer')])))->not->toContain('typed-js', 'Other language');
})->with(['ar', 'en']);
