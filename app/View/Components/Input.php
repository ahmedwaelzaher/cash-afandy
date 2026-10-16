<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;

class Input extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.input';

    /**
     * Determine if the input is password.
     */
    public bool $isPassword = false;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        public ?string $prepend = null,
        public ?string $append = null,
        public string $type = 'text',
        public bool $flat = false,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->isPassword = strtolower($this->type) === 'password';

        if ($this->isPassword) {
            $this->flat = true;
        }

        return parent::render();
    }

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        return [
            'isInputGroup' => $data['prepend'] || $data['append'] || $this->isPassword,
        ];
    }
}
