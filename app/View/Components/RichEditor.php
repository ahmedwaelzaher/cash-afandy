<?php

namespace App\View\Components;

use Illuminate\View\ComponentAttributeBag;

class RichEditor extends Textarea
{
    /**
     * View path for the component.
     */
    public string $view = 'components.rich-editor';

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        $this->attributes(function (ComponentAttributeBag $attributes) {
            return $attributes->merge(['init' => 'hugerte']);
        });

        return [];
    }
}
