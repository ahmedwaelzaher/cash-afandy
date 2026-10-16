<?php

namespace App\View\Components;

class ColorPicker extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.color-picker';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
    ) {}
}
