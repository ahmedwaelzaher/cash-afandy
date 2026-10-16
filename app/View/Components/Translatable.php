<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;

class Translatable extends Component
{
    /**
     * View path for the component.
     */
    public string $view = 'components.translatable';

    /**
     * The locales configuration.
     */
    public array $localesConfig = [];

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $validation = null,
        public string $component = 'input',
        public ?array $value = [],
        public array|string|null $locales = null,
        public array|string|null $validateLocales = null,
        public bool $validateOnlyMainLocale = false,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->id ??= uniqid('translatable-');

        if (! $this->locales) {
            $this->locales = array_keys(config('app.locales'));
        }

        if (is_null($this->value)) {
            $this->value = [];
        }

        if (is_string($this->locales)) {
            $this->locales = parse_csv($this->locales);
        }

        if (is_string($this->validateLocales)) {
            $this->validateLocales = parse_csv($this->validateLocales);
        }

        foreach ($this->locales as $index => $locale) {
            $validation = $this->validation;

            if ($this->validateLocales && ! in_array($locale, $this->validateLocales)) {
                $validation = $this->validationWithoutRequiredRule();
            }

            if ($this->validateOnlyMainLocale && $index > 0) {
                $validation = $this->validationWithoutRequiredRule();
            }

            $this->localesConfig[$locale] = [
                'id' => sprintf('%s-%s', $this->id, $locale),
                'name' => $this->name ? sprintf('%s[%s]', $this->name, $locale) : null,
                'value' => $this->value[$locale] ?? null,
                'validation' => $validation,
            ];
        }

        return view($this->view);
    }

    /**
     * Get the validation rules without the required rule.
     */
    protected function validationWithoutRequiredRule(): ?string
    {
        $rules = array_filter(
            array_map('trim', explode('|', $this->validation ?? '')),
            fn (string $rule): bool => $rule !== '' && $rule !== 'required',
        );

        return $rules ? implode('|', $rules) : null;
    }
}
