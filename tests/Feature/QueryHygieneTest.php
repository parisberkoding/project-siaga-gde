<?php

use Illuminate\Support\Facades\File;

/**
 * Penjaga kebiasaan query:
 *  - dilarang memakai `Model::all()` (tanpa batas/filter) di kode aplikasi;
 *  - query daftar harus memakai scope unit yang tersedia.
 *
 * Test ini statis supaya aturan tidak mudah kambuh saat menambah fitur.
 */
it('tidak ada query Model::all() di kode aplikasi', function () {
    $roots = [
        app_path(),
        database_path('seeders'),
        base_path('routes'),
    ];

    $offenders = [];

    foreach ($roots as $root) {
        foreach (File::allFiles($root) as $file) {
            $contents = $file->getContents();

            foreach (explode("\n", $contents) as $index => $line) {
                if (str_contains($line, '::all()')) {
                    $offenders[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($index + 1);
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});
