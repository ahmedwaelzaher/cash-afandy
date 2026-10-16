<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class Checkboxes extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.checkboxes';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        public ?string $name = null,
        public array|Collection $options = [],
        protected array|string|null $value = [],
        public array|string $disabled = [],
        public bool $inline = false,
        public ?string $validation = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        // If the disabled is csv, we will convert it to an array.
        if (! is_array($this->disabled)) {
            $this->disabled = is_string($this->disabled) ? parse_csv($this->disabled) : [$this->disabled];
        }

        return parent::render();
    }

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        // If the value is csv, we will convert it to an array.
        if (! is_array($this->value)) {
            $this->value = is_string($this->value) ? parse_csv($this->value) : [$this->value];
        }

        return [];
    }
}
