<?php

namespace App\View\Layouts;

use App\Enums\NotificationType;
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
            'websitePush' => [
                'storeUrl' => route('website.push-subscriptions.store'),
                'destroyUrl' => route('website.push-subscriptions.destroy'),
                'types' => collect(NotificationType::cases())->mapWithKeys(fn (NotificationType $type) => [
                    $type->value => ['label' => $type->label(), 'icon' => $type->icon()],
                ])->all(),
            ],
        ]);
    }
}
