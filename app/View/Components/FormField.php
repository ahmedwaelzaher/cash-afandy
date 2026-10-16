<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class FormField extends Component
{
    /**
     * The field name, when the field declares it as a prop.
     */
    protected ?string $name = null;

    /**
     * The validation rules, when the field declares them as a prop.
     */
    protected ?string $validation = null;

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->id ??= uniqid(Str::kebab(class_basename($this)) . '-');

        return function (array $data): View {
            $this->fillOldInput();

            $with = array_merge(['required' => $this->isRequired()], $this->prepare($data));

            if (property_exists($this, 'value')) {
                $with['value'] = $this->value;
            }

            return view($this->view, $with);
        };
    }

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        return [];
    }

    /**
     * Determine if the validation rules contain the `required` rule.
     */
    protected function isRequired(): bool
    {
        $rules = explode('|', (string) $this->attributes->get('validation', $this->validation));

        return in_array('required', array_map('trim', $rules), true);
    }

    /**
     * Replace the value with the old input when the field's name was flashed.
     */
    protected function fillOldInput(): void
    {
        $key = str_replace(['[]', '[', ']'], ['', '.', ''], (string) $this->attributes->get('name', $this->name));

        if (! Arr::has(old(), $key)) return;

        if (property_exists($this, 'value')) {
            $this->value = old($key);
        } else {
            $this->attributes['value'] = old($key);
        }
    }
}
