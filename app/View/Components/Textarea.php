<?php

namespace App\View\Components;

use Illuminate\View\ComponentAttributeBag;

class Textarea extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.textarea';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        protected ?string $value = null,
        public bool $autosize = false,
    ) {}

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        if ($this->autosize) {
            $this->attributes(function (ComponentAttributeBag $attributes) {
                return $attributes->merge(['data-bs-toggle' => 'autosize']);
            });
        }

        return [];
    }
}
