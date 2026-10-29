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
    preg_match('/rotate-words="([^"]+)"/', $html, $matches);
    $words = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);

    expect($words)->toBe($locale === 'ar' ? ['هدية', 'كوبون'] : ['<img src=x onerror=alert(1)>', 'gift'])
        ->and($html)->not->toContain('<img src=x onerror=alert(1)>')
        ->and($html)->toContain(
            'aria-hidden="true"', 'visually-hidden',
            'word-rotate', 'rotate-duration="350"', 'rotate-delay="2500"',
        );
})->with(['ar', 'en']);

test('hero keeps its original heading when no words exist in the current language', function (string $locale) {
    app()->setLocale($locale);
    cache()->forever('settings.hero_words', [['word' => [$locale === 'ar' ? 'en' : 'ar' => 'Other language']]]);

    $html = view('website.home.partials.hero', ['heroStores' => collect()])->render();

    expect($html)->toContain(e(__(':word in hand', ['word' => __('Offer')])))->not->toContain('word-rotate', 'Other language');
})->with(['ar', 'en']);
