<?php

namespace App\View\Layouts;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Redot\Models\Language;

class Website extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $title = null,
        public bool $inline = false,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        $this->inline = $this->inline || request()->has('inline');

        return view('layouts.website.base', [
            'direction' => Language::current()->direction,
        ]);
    }
}
