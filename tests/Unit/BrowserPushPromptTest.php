<?php

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

test('the browser invitation hides immediately after consent and recovers from registration failures', function () {
    $node = (new ExecutableFinder)->find('node');
    if (! $node) {
        $this->markTestSkipped('Node.js is required to exercise the browser notification script.');
    }

    $process = new Process([$node, '--experimental-vm-modules', __DIR__ . '/BrowserPushPrompt.cjs', __DIR__ . '/../../public/assets/js/website.js']);
    $process->setTimeout(15)->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput() . $process->getOutput());
});
