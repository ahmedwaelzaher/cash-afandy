<?php

namespace App\View\Components;

use Illuminate\Support\Collection;

class Repeater extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.repeater';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        protected array|string|Collection|null $value = null,
    ) {}

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        // Convert array to JSON string
        if (is_array($this->value) || $this->value instanceof Collection) {
            $this->value = json_encode($this->value, JSON_UNESCAPED_UNICODE);
        }

        return [];
    }
}
