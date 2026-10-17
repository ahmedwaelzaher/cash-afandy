<?php

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Redot\Models\Setting;

test('theme defaults use locale fonts without the obsolete global font setting', function () {
    require dirname(__DIR__, 2) . '/app/settings.php';

    expect(Setting::default('theme.fonts'))->toBe([])
        ->and(Setting::default('default_locale'))->toBeString()
        ->and(array_key_exists('font', Setting::default('theme')))->toBeFalse();
});

test('font face identifiers match their family directories and point to existing files', function () {
    $css = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/app.css');
    preg_match_all("/@font-face\s*\{([^}]+)\}/", $css, $faces);

    foreach ($faces[1] as $face) {
        preg_match("/font-family:\s*'([^']+)'/", $face, $family);
        preg_match("/url\('\.\.\/fonts\/([^']+)'\)/", $face, $source);
        $path = dirname(__DIR__, 2) . '/public/assets/fonts/' . $source[1];

        expect($family[1])->toBe(basename(dirname($path)))
            ->and(is_file($path))->toBeTrue();
    }
});

test('font choices discover non-empty family folders and ignore root files and empty folders', function () {
    $previous = Container::getInstance();
    $files = new Filesystem;
    $path = sys_get_temp_dir() . '/cash-afandy-fonts-' . bin2hex(random_bytes(8));
    $app = new Application(dirname(__DIR__, 2));
    $app->usePublicPath($path);
    $app->instance('files', $files);
    Facade::clearResolvedInstance('files');
    Facade::setFacadeApplication($app);

    try {
        expect(theme_fonts())->toBe([]);

        foreach (['rubik', 'marhey', 'empty-family'] as $family) {
            $files->makeDirectory($path . '/assets/fonts/' . $family, 0755, true);
        }
        $files->put($path . '/assets/fonts/rubik/font.ttf', 'fixture');
        $files->put($path . '/assets/fonts/marhey/font.ttf', 'fixture');
        $files->put($path . '/assets/fonts/ignored.ttf', 'fixture');

        expect(theme_fonts())->toBe(['marhey' => 'Marhey', 'rubik' => 'Rubik']);
    } finally {
        $files->deleteDirectory($path);
        Facade::clearResolvedInstance('files');
        Facade::setFacadeApplication($previous);
        Container::setInstance($previous);
    }
});
