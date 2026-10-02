<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Module;
use Illuminate\Database\Seeder;

/**
 * Mendaftarkan fitur dari registry config/features.php.
 *
 * Idempotent dan TIDAK menimpa keputusan developer:
 *   - Fitur baru            -> dibuat dengan stage dari config.
 *   - Fitur sudah ada       -> nama/deskripsi/modul diupdate, tetapi `stage`
 *                              TIDAK disentuh, supaya menjalankan seeder ulang
 *                              tidak membatalkan aktivasi/deaktivasi yang
 *                              sudah dilakukan developer.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $registry = (array) config('features.registry', []);

        foreach ($registry as $code => $def) {
            $moduleId = isset($def['module'])
                ? Module::where('code', $def['module'])->value('id')
                : null;

            $feature = Feature::where('code', $code)->first();

            if ($feature) {
                $feature->update([
                    'name' => $def['name'],
                    'description' => $def['description'] ?? $feature->description,
                    'module_id' => $moduleId,
                ]);
                continue;
            }

            Feature::create([
                'code' => $code,
                'name' => $def['name'],
                'description' => $def['description'] ?? null,
                'module_id' => $moduleId,
                'stage' => $def['stage'] ?? Feature::STAGE_PRODUCTION,
            ]);
        }
    }
}
