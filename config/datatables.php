<?php

use Redot\Datatables\Adapters\PDF\LaravelMpdf;
use Redot\Datatables\Exporters\CsvExporter;
use Redot\Datatables\Exporters\JsonExporter;
use Redot\Datatables\Exporters\PdfExporter;
use Redot\Datatables\Exporters\XlsxExporter;

return [

    /*
    |--------------------------------------------------------------------------
    | Redot Datatables config
    |--------------------------------------------------------------------------
    |
    | Here you can specify the configuration of the redot datatable.
    |
    */

    'assets' => [
        'css' => 'vendor/datatables/datatables.css',
        'js' => 'vendor/datatables/datatables.js',
    ],

    'export' => [
        'xlsx' => [
            'enabled' => true,
            'exporter' => XlsxExporter::class,
        ],

        'csv' => [
            'enabled' => true,
            'exporter' => CsvExporter::class,
        ],

        'json' => [
            'enabled' => true,
            'exporter' => JsonExporter::class,
        ],

        'pdf' => [
            'enabled' => true,
            'exporter' => PdfExporter::class,
            'template' => 'templates.pdf.datatable',
            'adapter' => LaravelMpdf::class,
            'options' => [
                'format' => 'A4',
                'orientation' => 'P',
            ],
        ],
    ],
];
