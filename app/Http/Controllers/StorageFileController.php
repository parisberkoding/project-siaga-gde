<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

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

        // Pastikan SVG (barcode QR) disajikan dengan mime yang benar.
        // File::mimeType() kadang mengembalikan text/plain untuk .svg, yang
        // menyebabkan browser menolak render di <img>.
        if (strtolower(File::extension($fullPath)) === 'svg') {
            $mimeType = 'image/svg+xml';
        }

        return response(File::get($fullPath), 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Length', File::size($fullPath))
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
