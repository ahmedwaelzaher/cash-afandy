<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\ComponentAttributeBag;

class Alert extends Component
{
    /**
     * View path for the component.
     */
    public string $view = 'components.alert';

    const TYPES_ICONS = [
        'success' => 'ti ti-circle-check',
        'error' => 'ti ti-circle-x',
        'warning' => 'ti ti-alert-triangle',
        'info' => 'ti ti-info-circle',
    ];

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $icon = null,
        public ?string $title = null,
        public ?string $description = null,
        public string $type = 'success',
        public bool $dismissible = false,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->type = in_array($this->type, array_keys(self::TYPES_ICONS)) ? $this->type : 'success';
        $this->icon ??= self::TYPES_ICONS[$this->type];

        return function (): View {
            $this->attributes(function (ComponentAttributeBag $attributes) {
                return $attributes->class([
                    'alert',
                    'alert-' . ($this->type === 'error' ? 'danger' : $this->type),
                    'alert-dismissible' => $this->dismissible,
                ]);
            });

            return view($this->view);
        };
    }
}
