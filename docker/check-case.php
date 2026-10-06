<?php

/**
 * check-case.php — deteksi kesalahan HURUF BESAR/KECIL pada nama file.
 *
 * Kenapa perlu?
 *   macOS (host, Herd) = case-insensitive  -> "Admin/Index" dan "admin/index" dianggap sama
 *   Linux  (container) = case-sensitive    -> yang salah huruf = error 500 / view not found
 *
 * Script ini membandingkan referensi di kode dengan nama file ASLI di disk
 * (bukan lewat file_exists, karena di macOS selalu "ada").
 *
 * Pakai:
 *      php docker/check-case.php
 *      make case
 *
 * Keluar dengan kode 1 kalau ada ketidakcocokan (aman dipakai di CI/pre-commit).
 */

$root = dirname(__DIR__);

$errors = [];
$warnings = [];

// ─────────────────────────────────────────────────────────────
// Kumpulkan file di disk (peta huruf-kecil => nama asli)
// ─────────────────────────────────────────────────────────────
function listFiles(string $dir): array
{
    $files = [];
    if (! is_dir($dir)) {
        return $files;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $files[] = $file->getPathname();
    }

    return $files;
}

$viewMap = [];   // huruf kecil => nama view asli
foreach (listFiles($root.'/resources/views') as $path) {
    $rel = substr($path, strlen($root.'/resources/views/'));

    if (str_ends_with($rel, '.blade.php')) {
        $name = substr($rel, 0, -10);
    } elseif (str_ends_with($rel, '.php')) {
        $name = substr($rel, 0, -4);
    } else {
        continue;
    }

    $name = str_replace('/', '.', $name);
    $viewMap[strtolower($name)][] = $name;
}

$classMap = [];  // huruf kecil => path asli (untuk cek PSR-4)
foreach (listFiles($root.'/app') as $path) {
    if (str_ends_with($path, '.php')) {
        $classMap[strtolower($path)][] = $path;
    }
}

$assetMap = [];  // huruf kecil => path asli (untuk cek asset())
$assetPrefix = $root.'/public/';
foreach (listFiles($root.'/public') as $path) {
    $assetMap[strtolower(substr($path, strlen($assetPrefix)))][] = substr($path, strlen($assetPrefix));
}

// ─────────────────────────────────────────────────────────────
// File sumber yang dipindai
// ─────────────────────────────────────────────────────────────
$sources = array_merge(
    listFiles($root.'/app'),
    listFiles($root.'/routes'),
    listFiles($root.'/database'),
    listFiles($root.'/config'),
    listFiles($root.'/resources/views'),
);

$sources = array_values(array_filter($sources, fn ($f) => str_ends_with($f, '.php')));

// ─────────────────────────────────────────────────────────────
// Pola referensi view
// ─────────────────────────────────────────────────────────────
$viewPatterns = [
    '/\bview\s*\(\s*[\'"]([^\'"]+)[\'"]/',
    '/@include\s*\(\s*[\'"]([^\'"]+)[\'"]/',
    '/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]/',
    '/@component\s*\(\s*[\'"]([^\'"]+)[\'"]/',
    '/@each\s*\(\s*[\'"]([^\'"]+)[\'"]/',
    '/View::make\s*\(\s*[\'"]([^\'"]+)[\'"]/',
    '/->view\s*\(\s*[\'"]([^\'"]+)[\'"]/',
    '/@includeIf\s*\(\s*[\'"]([^\'"]+)[\'"]/',
    '/@includeWhen\s*\([^,]+,\s*[\'"]([^\'"]+)[\'"]/',
];

foreach ($sources as $file) {
    $lines = @file($file) ?: [];
    $short = substr($file, strlen($root) + 1);

    foreach ($lines as $no => $line) {
        // ── Referensi view ──
        foreach ($viewPatterns as $pattern) {
            if (! preg_match_all($pattern, $line, $matches)) {
                continue;
            }

            foreach ($matches[1] as $reference) {
                // lewati nama dinamis / view ber-namespace paket
                if (str_contains($reference, '::')
                    || str_contains($reference, '$')
                    || str_contains($reference, '{')
                    || str_contains($reference, "'")
                    || trim($reference) === '') {
                    continue;
                }

                $key = strtolower($reference);

                if (! isset($viewMap[$key])) {
                    $warnings[] = sprintf('%s:%d  view "%s" tidak ditemukan', $short, $no + 1, $reference);

                    continue;
                }

                if (! in_array($reference, $viewMap[$key], true)) {
                    $errors[] = sprintf(
                        '%s:%d  view "%s" -> nama file sebenarnya: "%s"',
                        $short, $no + 1, $reference, implode('" / "', $viewMap[$key])
                    );
                }
            }
        }

        // ── Referensi class App\... (PSR-4) ──
        if (preg_match_all('/\bApp\\\\[A-Za-z0-9_\\\\]+/', $line, $matches)) {
            foreach ($matches[0] as $class) {
                $path = $root.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';
                $key = strtolower($path);

                if (! isset($classMap[$key])) {
                    continue;   // bukan class PSR-4 biasa (mis. traits di folder lain)
                }

                if (! in_array($path, $classMap[$key], true)) {
                    $errors[] = sprintf(
                        '%s:%d  class "%s" -> nama file sebenarnya: "%s"',
                        $short, $no + 1, $class, implode('" / "', $classMap[$key])
                    );
                }
            }
        }

        // ── Referensi asset() ke public/ ──
        if (preg_match_all('/\basset\s*\(\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            foreach ($matches[1] as $reference) {
                $reference = ltrim($reference, '/');

                if (str_contains($reference, '://')
                    || str_contains($reference, '$')
                    || str_contains($reference, '{')
                    || str_starts_with($reference, 'storage/')) {
                    continue;
                }

                $key = strtolower($reference);

                if (! isset($assetMap[$key])) {
                    continue;   // file mungkin dihasilkan saat runtime
                }

                if (! in_array($reference, $assetMap[$key], true)) {
                    $errors[] = sprintf(
                        '%s:%d  asset "%s" -> nama file sebenarnya: "%s"',
                        $short, $no + 1, $reference, implode('" / "', $assetMap[$key])
                    );
                }
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────
// Laporan
// ─────────────────────────────────────────────────────────────
echo "\n";
echo "CEK CASE-SENSITIVITY (macOS host -> Linux container)\n";
echo str_repeat('─', 78), "\n";

if ($errors === [] && $warnings === []) {
    echo "✅ Tidak ada masalah huruf besar/kecil.\n\n";
    exit(0);
}

if ($errors !== []) {
    echo "\n❌ BEDA HURUF (akan error di Docker/Linux, tapi baik-baik saja di macOS):\n\n";
    foreach (array_unique($errors) as $line) {
        echo "   $line\n";
    }
}

if ($warnings !== []) {
    echo "\n⚠️  TIDAK DITEMUKAN (cek manual, bisa jadi nama dinamis):\n\n";
    foreach (array_unique($warnings) as $line) {
        echo "   $line\n";
    }
}

echo "\n";
echo $errors === []
    ? "Hasil: hanya peringatan.\n\n"
    : sprintf("Hasil: %d masalah yang harus diperbaiki sebelum push.\n\n", count(array_unique($errors)));

exit($errors === [] ? 0 : 1);
