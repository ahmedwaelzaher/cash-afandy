<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\ComponentAttributeBag;

class FileHint extends Component
{
    /**
     * View path for the component.
     */
    public string $view = 'components.file-hint';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $file = null,
        public bool $lightbox = true,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return function (): View {
            $this->attributes(function (ComponentAttributeBag $attributes) {
                $attributes = $attributes->class(['text-decoration-none']);

                if ($this->lightbox) {
                    $attributes = $attributes->merge([
                        'data-lightbox' => '',
                    ]);
                }

                return $attributes;
            });

            return view($this->view);
        };
    }
}
