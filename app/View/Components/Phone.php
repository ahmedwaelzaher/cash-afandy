<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\ComponentAttributeBag;

class Phone extends Component
{
    /**
     * View path for the component.
     */
    public string $view = 'components.phone';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->id ??= uniqid('phone-');

        return function (): View {
            $this->attributes(function (ComponentAttributeBag $attributes) {
                return $attributes->merge([
                    'init' => 'intl-tel-input',
                    'iti-country-name-locale' => app()->getLocale(),
                    'iti-initial-country' => strtolower(website_country()?->code ?? ''),
                ]);
            });

            return view($this->view, [
                'required' => str_contains($this->attributes->get('validation') ?: '', 'required'),
            ]);
        };
    }
}
