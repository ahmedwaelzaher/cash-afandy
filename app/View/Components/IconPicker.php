<?php

namespace App\View\Components;

class IconPicker extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.icon-picker';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
    ) {}
}
