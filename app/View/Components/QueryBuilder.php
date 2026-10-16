<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\ComponentAttributeBag;
use Redot\Support\QueryFilters;

class QueryBuilder extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.query-builder';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        public ?string $model = null,
        protected array|string|Collection|null $value = null,
        public array|string|null $filters = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->filters = QueryFilters::resolve($this->model, $this->filters);

        return parent::render();
    }

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        // Convert array to JSON string
        if (is_array($this->value) || $this->value instanceof Collection) {
            $this->value = json_encode($this->value, JSON_UNESCAPED_UNICODE);
        }

        $this->attributes(function (ComponentAttributeBag $attributes) {
            return $attributes->merge(['query-filters' => is_array($this->filters) ? json_encode($this->filters, JSON_UNESCAPED_UNICODE) : $this->filters]);
        });

        return [];
    }
}
