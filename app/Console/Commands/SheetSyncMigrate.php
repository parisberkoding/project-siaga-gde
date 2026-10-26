<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SheetSyncMigrate extends Command
{
    protected $signature = 'sheet:migrate
        {--dry-run : Report what would be done without writing}
        {--chunk=200 : Rows per batch}';

    protected $description = 'Migrasi data dari tabel staging legacy_* ke tabel produksi';

    protected array $rejects = [];

    protected ?array $userLegacyMap = null;

    protected ?array $vehicleLegacyMap = null;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk  = (int) $this->option('chunk');

        if ($dryRun) {
            $this->warn('=== DRY-RUN MODE === Tidak ada data yang ditulis.');
        }

        $this->newLine();
        $this->info('Step 1/6: Mapping legacy users → production users');
        $this->migrateGasolineTypes($dryRun, $chunk);

        $this->newLine();
        $this->info('Step 2/6: Migrating users');
        $this->migrateUsers($dryRun, $chunk);

        $this->newLine();
        $this->info('Step 3/6: Migrating vehicles');
        $this->migrateVehicles($dryRun, $chunk);

        $this->newLine();
        $this->info('Step 4/6: Migrating permohonans');
        $this->migratePermohonans($dryRun, $chunk);

        $this->newLine();
        $this->info('Step 5/6: Migrating approved requests (from permohonan with voucher)');
        $this->migrateApprovedRequests($dryRun, $chunk);

        $this->newLine();
        $this->info('Step 6/6: Migrating transaction proofs');
        $this->migrateTransactionProofs($dryRun, $chunk);

        $this->printSummary();

        return self::SUCCESS;
    }

    // ========================================================================
    // STEP 1: Gasoline Types
    // ========================================================================
    protected function migrateGasolineTypes(bool $dryRun, int $chunk): void
    {
        $staging = DB::table('legacy_gasoline_types')->orderBy('id')->get();
        $this->line("    Found {$staging->count()} rows in staging");

        if ($staging->isEmpty()) {
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($staging->chunk($chunk) as $batch) {
            foreach ($batch as $row) {
                $exists = DB::table('gasoline_types')
                    ->where('gasoline_code', $row->gasoline_type_id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                if (! $dryRun) {
                    DB::table('gasoline_types')->insert([
                        'gasoline_code'   => $row->gasoline_type_id,
                        'gasoline_name'   => $row->gasoline_name,
                        'price_per_liter' => $row->price_per_liter_rp,
                        'notes'           => 'Migrated from legacy API',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                $created++;
            }
        }

        $this->line("    Created: {$created}, Skipped (already exist): {$skipped}");
    }

    // ========================================================================
    // STEP 2: Users
    // ========================================================================
    protected function migrateUsers(bool $dryRun, int $chunk): void
    {
        $staging = DB::table('legacy_users')->orderBy('legacy_id')->get();
        $this->line("    Found {$staging->count()} rows in staging");

        if ($staging->isEmpty()) {
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($staging->chunk($chunk) as $batch) {
            foreach ($batch as $row) {
                $existing = DB::table('users')->where('username', $row->username)->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                if (! $dryRun) {
                    try {
                        DB::table('users')->insert([
                            'username'      => $row->username,
                            'password'      => bcrypt(Str::random(16)),
                            'nama_lengkap'  => $row->fullname,
                            'role'          => $this->normalizeRole($row->role),
                            'jabatan'       => null,
                            'email'         => $row->email ?: null,
                            'added_at'      => now(),
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                        $created++;
                    } catch (Throwable $e) {
                        $this->logReject('users', (string) $row->legacy_id, $e->getMessage(), $row);
                    }
                } else {
                    $created++;
                }
            }
        }

        $this->line("    Created: {$created}, Skipped (already exist): {$skipped}, Failed: " . count($this->rejectsFor('users')));
    }

    protected function normalizeRole(?string $role): string
    {
        return match (trim((string) $role)) {
            'driver'       => 'requester',
            'admin'        => 'admin_bbm',
            'super admin'  => 'superadmin',
            'manager'      => 'manager',
            default        => 'requester',
        };
    }

    // ========================================================================
    // STEP 3: Vehicles
    // ========================================================================
    protected function migrateVehicles(bool $dryRun, int $chunk): void
    {
        $staging = DB::table('legacy_vehicles')->orderBy('legacy_id')->get();
        $this->line("    Found {$staging->count()} rows in staging");

        if ($staging->isEmpty()) {
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($staging->chunk($chunk) as $batch) {
            foreach ($batch as $row) {
                $existing = DB::table('vehicle_and_tools_consumers')
                    ->where('consumerial_code', trim($row->plat_nomer))
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                if (! $dryRun) {
                    try {
                        $vehicleId = DB::table('vehicle_and_tools_consumers')->insertGetId([
                            'consumerial_type'   => strtoupper($row->jenis ?? 'KENDARAAN'),
                            'consumerial_code'   => trim($row->plat_nomer),
                            'consumerial_name'   => $row->merk ?? trim($row->plat_nomer),
                            'consumerial_notes'  => 'Migrated from legacy API',
                            'is_approved'        => true,
                            'is_active'          => true,
                            'approved_by'        => 1,
                            'created_by'         => 1,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);

                        DB::table('vehicle_details')->insert([
                            'vehicle_and_tools_consumer_id' => $vehicleId,
                            'merk'                         => $row->merk,
                            'site_id'                      => null,
                            'created_at'                   => now(),
                            'updated_at'                   => now(),
                        ]);

                        $created++;
                    } catch (Throwable $e) {
                        $this->logReject('vehicles', (string) $row->legacy_id, $e->getMessage(), $row);
                    }
                } else {
                    $created++;
                }
            }
        }

        $this->line("    Created: {$created}, Skipped (already exist): {$skipped}, Failed: " . count($this->rejectsFor('vehicles')));
    }

    // ========================================================================
    // STEP 4: Permohonans
    // ========================================================================
    protected function migratePermohonans(bool $dryRun, int $chunk): void
    {
        $staging = DB::table('legacy_permohonans')->orderBy('id')->get();
        $this->line("    Found {$staging->count()} rows in staging");

        if ($staging->isEmpty()) {
            return;
        }

        $created = 0;
        $skipped = 0;
        $statusMap = [];

        foreach ($staging->chunk($chunk) as $batch) {
            foreach ($batch as $row) {
                $exists = DB::table('permohonans')
                    ->where('request_number', $row->request_id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $statusMap[$row->status] = ($statusMap[$row->status] ?? 0) + 1;
                    continue;
                }

                if (! $dryRun) {
                    try {
                        $requesterId = $this->resolveUserId((int) $row->driver_id);
                        $vehicleId   = $this->resolveVehicleId((int) $row->vehicle_id);
                        $authorizerId = $row->authorizer_id
                            ? $this->resolveUserId((int) $row->authorizer_id)
                            : null;

                        $normalizedStatus = $this->normalizePermohonanStatus($row->status);

                        DB::table('permohonans')->insert([
                            'request_number'      => $row->request_id,
                            'requester_id'        => $requesterId ?? $this->getDefaultUserId(),
                            'request_date'        => $row->request_at ?? now(),
                            'gasoline_type'       => $row->gasoline_type,
                            'bill_amounts'        => $row->bill_payments ?? 0,
                            'status'              => $normalizedStatus,
                            'requester_notes'     => $row->requester_note,
                            'authorizer_id'       => $authorizerId,
                            'authorization_date'  => $row->approve_at,
                            'authorizer_notes'    => $row->authorizer_comment,
                            'consumerial_tools_id' => $vehicleId,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);

                        $created++;
                        $statusMap[$normalizedStatus] = ($statusMap[$normalizedStatus] ?? 0) + 1;

                        if (! $requesterId) {
                            $this->logReject('permohonans', $row->request_id, "driver_id {$row->driver_id} not resolved, used default user");
                        }
                        if (! $vehicleId) {
                            $this->logReject('permohonans', $row->request_id, "vehicle_id {$row->vehicle_id} not resolved, left null");
                        }
                    } catch (Throwable $e) {
                        $this->logReject('permohonans', $row->request_id, $e->getMessage(), $row);
                    }
                } else {
                    $created++;
                    $statusMap[$row->status] = ($statusMap[$row->status] ?? 0) + 1;
                }
            }
        }

        $this->line("    Created: {$created}, Skipped: {$skipped}, Failed: " . count($this->rejectsFor('permohonans')));
        $this->line('    Status breakdown:');
        foreach ($statusMap as $s => $c) {
            $this->line("      - {$s}: {$c}");
        }
    }

    protected function normalizePermohonanStatus(string $status): string
    {
        $status = trim(preg_replace('/\s+/', ' ', $status));
        $normalized = str_replace(' ', '_', $status);

        return match (true) {
            $normalized === 'approved'                                   => 'approved',
            $normalized === 'completed'                                  => 'completed',
            $normalized === 'rejected'                                   => 'rejected',
            $normalized === 'pending'                                    => 'pending',
            str_contains($normalized, 'cancel')                          => 'rejected',
            str_contains($normalized, 'un_used')                         => 'rejected',
            default                                                          => $normalized,
        };
    }

    // ========================================================================
    // STEP 5: Approved Requests
    // ========================================================================
    protected function migrateApprovedRequests(bool $dryRun, int $chunk): void
    {
        $staging = DB::table('legacy_permohonans')
            ->whereIn('status', ['completed', 'approved'])
            ->whereNotNull('voucher_code')
            ->where('voucher_code', '!=', '')
            ->orderBy('id')
            ->get();

        $this->line("    Found {$staging->count()} permohonans with voucher_code");

        if ($staging->isEmpty()) {
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($staging->chunk($chunk) as $batch) {
            foreach ($batch as $row) {
                $exists = DB::table('approved_requests')
                    ->where('voucher_code', $row->voucher_code)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                if (! $dryRun) {
                    try {
                        $permohonan = DB::table('permohonans')
                            ->where('request_number', $row->request_id)
                            ->first();

                        if (! $permohonan) {
                            $this->logReject('approved_requests', $row->voucher_code, "Permohonan {$row->request_id} not found in production");
                            continue;
                        }

                        $authorizerId = $row->authorizer_id
                            ? $this->resolveUserId((int) $row->authorizer_id)
                            : null;

                        DB::table('approved_requests')->insert([
                            'permohonan_id'    => $permohonan->id,
                            'approval_date'    => $row->approve_at ?? now(),
                            'voucher_code'     => $row->voucher_code,
                            'valid_until'      => $row->approve_at
                                ? date('Y-m-d H:i:s', strtotime($row->approve_at . ' +30 days'))
                                : now()->addDays(30),
                            'approved_amount'  => (float) ($row->bill_payments ?? 0),
                            'authorizer_id'    => $authorizerId ?? 1,
                            'approval_notes'   => $row->authorizer_comment,
                            'status'           => $row->status === 'completed' ? 'completed' : 'approved',
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);

                        $created++;
                    } catch (Throwable $e) {
                        $this->logReject('approved_requests', $row->voucher_code, $e->getMessage(), $row);
                    }
                } else {
                    $created++;
                }
            }
        }

        $this->line("    Created: {$created}, Skipped: {$skipped}, Failed: " . count($this->rejectsFor('approved_requests')));
    }

    // ========================================================================
    // STEP 6: Transaction Proofs
    // ========================================================================
    protected function migrateTransactionProofs(bool $dryRun, int $chunk): void
    {
        $staging = DB::table('legacy_transaction_proofs')->orderBy('id')->get();
        $this->line("    Found {$staging->count()} rows in staging");

        if ($staging->isEmpty()) {
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($staging->chunk($chunk) as $batch) {
            foreach ($batch as $row) {
                $exists = DB::table('transaction_proofs')
                    ->where('transaction_id', $row->proof_id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                if (! $dryRun) {
                    try {
                        $permohonan = DB::table('permohonans')
                            ->where('request_number', $row->request_id)
                            ->first();

                        if (! $permohonan) {
                            $this->logReject('transaction_proofs', $row->proof_id, "Permohonan {$row->request_id} not found");
                            continue;
                        }

                        DB::table('transaction_proofs')->insert([
                            'transaction_id'      => $row->proof_id,
                            'req_id'              => $permohonan->id,
                            'requester_id'        => $permohonan->requester_id,
                            'voucher_number'      => $row->voucher_code,
                            'purchase_datetime'   => $row->proof_created_at ?? $permohonan->request_date,
                            'fuel_volume'         => 0,
                            'km_terakhir'         => $row->km_terakhir,
                            'struk_bbm_path'      => $row->foto_struk_bbm_pertamina,
                            'odometer_photo_path' => $row->foto_km_terakhir,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);

                        $created++;
                    } catch (Throwable $e) {
                        $this->logReject('transaction_proofs', $row->proof_id, $e->getMessage(), $row);
                    }
                } else {
                    $created++;
                }
            }
        }

        $this->line("    Created: {$created}, Skipped: {$skipped}, Failed: " . count($this->rejectsFor('transaction_proofs')));
    }

    // ========================================================================
    // Helpers
    // ========================================================================
    protected function resolveUserId(int $legacyId): ?int
    {
        if ($this->userLegacyMap === null) {
            $this->buildUserLegacyMap();
        }

        return $this->userLegacyMap[$legacyId] ?? null;
    }

    protected function buildUserLegacyMap(): void
    {
        $this->userLegacyMap = [];

        $legacyUsers = DB::table('legacy_users')->pluck('username', 'legacy_id');
        $prodUsers   = DB::table('users')->pluck('id', 'username');

        foreach ($legacyUsers as $legacyId => $username) {
            $prodId = $prodUsers[$username] ?? null;
            if ($prodId) {
                $this->userLegacyMap[$legacyId] = $prodId;
            }
        }
    }

    protected function resolveVehicleId(int $legacyId): ?int
    {
        if ($this->vehicleLegacyMap === null) {
            $this->buildVehicleLegacyMap();
        }

        return $this->vehicleLegacyMap[$legacyId] ?? null;
    }

    protected function buildVehicleLegacyMap(): void
    {
        $this->vehicleLegacyMap = [];

        $legacyVehicles = DB::table('legacy_vehicles')
            ->select('legacy_id', 'plat_nomer')
            ->get();

        foreach ($legacyVehicles as $v) {
            $prod = DB::table('vehicle_and_tools_consumers')
                ->where('consumerial_code', trim($v->plat_nomer))
                ->first(['id']);

            if ($prod) {
                $this->vehicleLegacyMap[$v->legacy_id] = $prod->id;
            }
        }
    }

    protected function getDefaultUserId(): int
    {
        return DB::table('users')->min('id') ?? 1;
    }

    protected function logReject(string $resource, string $key, string $reason, mixed $payload = null): void
    {
        $this->rejects[] = compact('resource', 'key', 'reason', 'payload');

        DB::table('sheet_sync_rejects')->insert([
            'resource'   => $resource,
            'key'        => $key,
            'reason'     => $reason,
            'payload'    => json_encode($payload ? (array) $payload : []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function rejectsFor(string $resource): array
    {
        return array_filter($this->rejects, fn ($r) => $r['resource'] === $resource);
    }

    protected function printSummary(): void
    {
        $this->newLine();
        $this->line('=== MIGRATION SUMMARY ===');

        if (! empty($this->rejects)) {
            $this->warn('Rejected rows:');
            $grouped = collect($this->rejects)->groupBy('resource');
            foreach ($grouped as $resource => $items) {
                $this->warn("  {$resource}: {$items->count()} failed");
                foreach ($items as $item) {
                    $this->line("    - {$item['key']}: {$item['reason']}");
                }
            }
        } else {
            $this->info('  No rejects! All rows migrated successfully.');
        }
    }
}