<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;

class Rating extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.rating';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        public ?string $name = null,
        protected ?string $value = null,
        public int $stars = 5,
        public string|int $size = '24px',
        public string $icon = 'ti ti-star',
        public ?string $validation = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->size = is_numeric($this->size) ? $this->size . 'px' : $this->size;

        return parent::render();
    }
}
