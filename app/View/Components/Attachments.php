<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;

class Attachments extends Component
{
    /**
     * View path for the component.
     */
    public string $view = 'components.attachments';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $id,
        public array $attachments,
        public ?string $title = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->id ??= uniqid('attachments-');

        return view($this->view);
    }

    /**
     * Get the file icon for the given MIME type.
     */
    public function getFileIcon(string $mimeType): string
    {
        $icons = [
            'application/pdf' => 'ti ti-file-type-pdf',
            'application/msword' => 'ti ti-file-type-doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'ti ti-file-type-doc',
            'application/vnd.ms-excel' => 'ti ti-file-type-xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'ti ti-file-type-xls',
            'application/vnd.ms-powerpoint' => 'ti ti-file-type-ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'ti ti-file-type-ppt',
            'application/zip' => 'ti ti-file-zip',
            'application/x-rar-compressed' => 'ti ti-file-zip',
            'application/x-7z-compressed' => 'ti ti-file-zip',
            'text/plain' => 'ti ti-file-text',
            'text/html' => 'ti ti-file-code',
            'text/css' => 'ti ti-file-code',
            'text/javascript' => 'ti ti-file-code',
            'application/json' => 'ti ti-file-code',
            'video/' => 'ti ti-video',
            'audio/' => 'ti ti-file-music',
        ];

        foreach ($icons as $type => $icon) {
            if (str_starts_with($mimeType, $type)) return $icon;
        }

        return 'ti ti-file';
    }

    /**
     * Format the file size.
     */
    public function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return round($size, 2) . ' ' . $units[$index];
    }
}
