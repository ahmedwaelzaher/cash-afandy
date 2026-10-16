<?php

namespace App\View\Components;

class Toggle extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.toggle';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        protected ?bool $value = false,
        public ?string $on = null,
        public ?string $off = null,
    ) {}

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        $this->value = (bool) $this->value;

        return [];
    }
}
