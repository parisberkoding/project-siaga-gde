<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StorageFileController extends Controller
{
    public function serve($path)
    {
        // Sanitize path
        $path = str_replace(['../', '..\\'], '', $path);

        // Full path
        $fullPath = storage_path('app/public/' . $path);

        if (!File::exists($fullPath)) {
            abort(404);
        }

        $mimeType = File::mimeType($fullPath);

        // Return& Headers caching
        return response(File::get($fullPath), 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Length', File::size($fullPath))
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
