<?php

return [
    'base_url' => env('SHEET_API_URL'),
    'token'    => env('SHEET_API_TOKEN'),
    'timeout'  => env('SHEET_API_TIMEOUT', 120),
    'limit'    => env('SHEET_API_LIMIT', 500),
];