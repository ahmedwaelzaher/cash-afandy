<?php

namespace App\View\Layouts;

use App\Models\Admin;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Redot\Models\Language;
use Redot\Sidebar\Item;
use Redot\Sidebar\Sidebar;

class Dashboard extends Component
{
    /**
     * The authenticated admin user.
     */
    public Admin $admin;

    /**
     * The sidebar instance.
     */
    public Sidebar $sidebar;

    /**
     * The items to be displayed in the sidebar.
     */
    public array $items = [];

    /**
     * Active sidebar item.
     */
    public ?Item $active = null;

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
        $this->admin = current_admin();
        $this->sidebar = include base_path('app/sidebar.php');

        $this->items = $this->sidebar->getItems();
        $this->active = $this->sidebar->getActiveItem();
        $this->inline = $this->inline || request()->has('inline');

        // Set the title if not provided to the active item's title
        if ($this->title === null && $this->active && $this->active->title) {
            $this->title = $this->active->title;
        }

        return view('layouts.dashboard.base', [
            'direction' => Language::current()->direction,
        ]);
    }
}
