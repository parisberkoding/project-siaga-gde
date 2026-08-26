<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class StorageFileController extends Controller
{
    public function serve($path)
    {
        // Base directory yang boleh dilayani.
        $base = realpath(storage_path('app/public'));
        if ($base === false) {
            abort(404);
        }

        // Resolve path ke bentuk absolut (normalisasi '..', '....//', dll).
        $fullPath = realpath($base . DIRECTORY_SEPARATOR . $path);

        // Path traversal: file di luar base ditolak. realpath() gagal bila
        // segmen '..'/'....' tidak valid, dan containment check di bawah
        // memastikan file selalu berada di dalam storage/app/public.
        if ($fullPath === false || !str_starts_with($fullPath, $base . DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        if (!is_file($fullPath)) {
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
