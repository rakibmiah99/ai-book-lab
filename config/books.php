<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Book Page Rendering
    |--------------------------------------------------------------------------
    |
    | DPI used when rasterizing each PDF page to an image before it is
    | uploaded. Higher values produce sharper page screenshots (and, later,
    | more accurate AI OCR) at the cost of slower rendering and larger
    | uploads.
    |
    */

    'render_dpi' => env('BOOK_PAGE_RENDER_DPI', 300),

];
