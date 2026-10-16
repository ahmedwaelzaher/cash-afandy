<?php

namespace App\View\Components;

use Illuminate\Support\Collection;
use Illuminate\View\ComponentAttributeBag;

class Uploader extends FormField
{
    /**
     * View path for the component.
     */
    public string $view = 'components.uploader';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $hint = null,
        protected array|string|Collection|null $value = null,
        public string $directory = 'general',
        public string $accept = '*',
        public string $serverValidation = '',
        public bool $optimize = true,
        public bool $thumbnail = true,
    ) {}

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
            return $attributes->merge([
                'init' => 'uploader',
                'uploader-accept' => $this->accept,
                'uploader-config' => $this->getUploaderConfig(),
            ]);
        });

        return [];
    }

    /**
     * Get the uploader data.
     */
    protected function getUploaderConfig(): string
    {
        $data = [
            'accept' => $this->accept,
            'directory' => $this->directory,
            'locale' => app()->getLocale(),
            'serverValidation' => $this->serverValidation,
            'optimize' => $this->optimize,
            'thumbnail' => $this->thumbnail,
        ];

        return encrypt(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
