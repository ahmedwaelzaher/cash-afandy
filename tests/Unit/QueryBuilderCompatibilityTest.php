<?php

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

uses(TestCase::class);

test('query builder components load the existing compatibility script once before the library', function () {
    $html = Blade::render(<<<'BLADE'
        <x-query-builder :filters="$filters" />
        <x-query-builder :filters="$filters" />
        @stack('plugins-scripts')
        BLADE, ['filters' => ['id' => ['title' => 'ID', 'type' => 'integer']]]);

    expect(substr_count($html, '/vendor/query-builder/query-builder.compatibility.js'))->toBe(1)
        ->and(substr_count($html, '/vendor/query-builder/query-builder.min.js'))->toBe(1)
        ->and(strpos($html, 'query-builder.compatibility.js'))->toBeLessThan(strpos($html, 'query-builder.min.js'));
});

test('the installed compatibility script restores the helpers used by QueryBuilder extendext', function () {
    $node = (new ExecutableFinder)->find('node');
    if (! $node) {
        $this->markTestSkipped('Node.js is required to exercise QueryBuilder compatibility.');
    }

    $script = <<<'JS'
        const assert = require('node:assert/strict');
        const fs = require('node:fs');
        const path = require('node:path');
        const vm = require('node:vm');
        const root = process.argv[1];
        const library = fs.readFileSync(path.join(root, 'public/vendor/query-builder/query-builder.min.js'), 'utf8');
        const compatibility = fs.readFileSync(path.join(root, 'public/vendor/query-builder/query-builder.compatibility.js'), 'utf8');
        // Exercise the actual bundled extendext implementation without a browser or a DOM package.
        const extendext = library.slice(0, library.indexOf('/*!', 4)).replace(/,\s*$/, ';');
        const jquery = {
            isPlainObject: value => Object.prototype.toString.call(value) === '[object Object]',
            extend: (...args) => {
                if (typeof args[0] === 'boolean') args.shift();
                return Object.assign(args.shift(), ...args);
            },
        };
        const context = vm.createContext({ jQuery: jquery, $: jquery });
        vm.runInContext(extendext, context);
        const merge = () => jquery.extendext(true, 'replace', {}, { filters: [{ id: 'user' }], icons: { add: 'plus' } });
        assert.throws(merge, /isArray is not a function/);
        vm.runInContext(compatibility, context);
        assert.deepEqual(JSON.parse(JSON.stringify(merge())), { filters: [{ id: 'user' }], icons: { add: 'plus' } });
        assert.equal(jquery.isArray([]), true);
        assert.equal(jquery.isFunction(() => {}), true);
        assert.equal(jquery.trim(null), '');
        assert.equal(jquery.trim(' text '), 'text');
        JS;

    $process = new Process([$node, '-e', $script, base_path()]);
    $process->setTimeout(15)->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput() . $process->getOutput());
});
