<?php

namespace App\View\Components;

use Illuminate\Support\Collection;
use Illuminate\View\ComponentAttributeBag;

class Select extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.select';

    /**
     * Create the component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        public array|Collection $options = [],
        protected array|string|null $value = [],
        public bool $tom = true,
    ) {}

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        // If the value is csv, we will convert it to an array.
        if (! is_array($this->value)) {
            $this->value = parse_csv(is_string($this->value) ? $this->value : [$this->value]);
        }

        $this->attributes(function (ComponentAttributeBag $attributes) {
            $attributes = $attributes->class(['form-select']);

            if ($this->tom) {
                $attributes = $attributes->merge(['init' => 'tomselect']);
            }

            return $attributes;
        });

        return [];
    }
}
