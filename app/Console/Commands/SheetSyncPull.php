<?php

namespace App\Console\Commands;

use App\Services\SheetSync\SheetApiClient;
use App\Services\SheetSync\SheetApiException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SheetSyncPull extends Command
{
    protected $signature = 'sheet:pull
        {resource? : Resource name, empty for all}
        {--full : Ignore cursor, pull all}
        {--dry-run : Report rows without writing}';

    protected $description = 'Tarik data legacy dari Google Sheets API ke tabel staging';

    protected array $resourceOrder = [
        'gasoline_type',
        'users',
        'vehicles',
        'permohonan',
        'transaction_proof',
        'vehicle_health',
    ];

    protected array $resourceConfig = [
        'gasoline_type'     => ['key' => 'gasoline_type_id', 'incremental' => false, 'cursor_field' => null],
        'users'             => ['key' => 'legacy_id',        'incremental' => false, 'cursor_field' => null],
        'vehicles'          => ['key' => 'legacy_id',        'incremental' => false, 'cursor_field' => null],
        'permohonan'        => ['key' => 'request_id',       'incremental' => true,  'cursor_field' => 'request_at'],
        'transaction_proof' => ['key' => 'proof_id',         'incremental' => true,  'cursor_field' => 'created_at'],
        'vehicle_health'    => ['key' => 'legacy_id',        'incremental' => true,  'cursor_field' => 'ltp_date'],
    ];

    protected array $stagingTableMap = [
        'gasoline_type'     => 'legacy_gasoline_types',
        'users'             => 'legacy_users',
        'vehicles'          => 'legacy_vehicles',
        'permohonan'        => 'legacy_permohonans',
        'transaction_proof' => 'legacy_transaction_proofs',
        'vehicle_health'    => 'legacy_vehicle_healths',
    ];

    public function handle(SheetApiClient $client): int
    {
        $requested = $this->argument('resource');
        $full      = (bool) $this->option('full');
        $dryRun    = (bool) $this->option('dry-run');

        $resources = $requested
            ? [$requested]
            : $this->resourceOrder;

        if ($dryRun) {
            $this->warn('=== DRY-RUN MODE === Tidak ada data yang ditulis.');
        }

        $summary = [];

        foreach ($resources as $resource) {
            if (! isset($this->resourceConfig[$resource])) {
                $this->error("Unknown resource: {$resource}");
                continue;
            }

            $this->newLine();
            $this->info(">>> Pulling: {$resource}");

            try {
                $result = $this->pullResource($client, $resource, $full, $dryRun);
                $summary[$resource] = $result;
                $this->line("    OK: {$result['total']} rows fetched, {$result['new']} new");
            } catch (SheetApiException $e) {
                $this->error("    FAILED: {$e->getMessage()}");
                $summary[$resource] = ['total' => 0, 'new' => 0, 'error' => $e->getMessage()];
                continue;
            } catch (Throwable $e) {
                $this->error("    UNEXPECTED: {$e->getMessage()}");
                $summary[$resource] = ['total' => 0, 'new' => 0, 'error' => $e->getMessage()];
                continue;
            }
        }

        $this->newLine();
        $this->line('=== SUMMARY ===');
        foreach ($summary as $res => $s) {
            $error = isset($s['error']) ? " ERROR: {$s['error']}" : '';
            $this->line("  {$res}: {$s['total']} rows ({$s['new']} new){$error}");
        }

        $hasError = collect($summary)->contains(fn ($s) => isset($s['error']));

        return $hasError ? self::FAILURE : self::SUCCESS;
    }

    protected function pullResource(SheetApiClient $client, string $resource, bool $full, bool $dryRun): array
    {
        $config   = $this->resourceConfig[$resource];
        $table    = $this->stagingTableMap[$resource];
        $keyField = $config['key'];
        $since    = null;

        if (! $full && $config['incremental'] && $config['cursor_field']) {
            $state = DB::table('sheet_sync_states')->where('resource', $resource)->first();
            if ($state && $state->cursor) {
                $since = $state->cursor;
            }
        }

        $now       = now();
        $totalNew  = 0;
        $totalRows = 0;
        $nextSince = $since;

        $bar = $this->output->createProgressBar();
        $bar->setFormat("    %current% pages [%bar%] %percent:3s%% — %message%");
        $bar->setMessage('starting...');
        $bar->start();

        foreach ($client->pages($resource, $since) as $page) {
            $rows = $page['data'] ?? [];
            $count = count($rows);
            $totalRows += $count;
            $bar->setMessage("{$resource} page {$page['page']} ({$count} rows)");
            $bar->advance();

            if (empty($rows)) {
                continue;
            }

            $upsertData = [];
            foreach ($rows as $row) {
                $sourceRow = $row['_row'] ?? null;
                unset($row['_row']);

                $record = $this->normalizeRow($resource, $row);
                $record['source_row'] = $sourceRow;
                $record['synced_at']  = $now;
                $record['created_at'] = $now;
                $record['updated_at'] = $now;

                $upsertData[] = $record;
            }

            if (! $dryRun) {
                $newCount = 0;

                foreach (array_chunk($upsertData, 200) as $chunk) {
                    foreach ($chunk as $record) {
                        $keyValue = $record[$keyField] ?? null;

                        $exists = DB::table($table)->where($keyField, $keyValue)->exists();

                        if ($exists) {
                            continue;
                        }

                        DB::table($table)->insert($record);
                        $newCount++;
                    }
                }

                $totalNew += $newCount;
            }

            // Track next_since from each page — use the largest value
            $pageSince = $page['next_since'] ?? null;
            if ($pageSince && ($nextSince === null || $pageSince > $nextSince)) {
                $nextSince = $pageSince;
            }
        }

        $bar->finish();
        $this->newLine();

        // Commit cursor only after all pages succeed
        if (! $dryRun && $config['incremental'] && $nextSince !== null && $nextSince !== $since) {
            DB::table('sheet_sync_states')->updateOrInsert(
                ['resource' => $resource],
                [
                    'cursor'          => $nextSince,
                    'last_synced_at'  => $now,
                    'last_row_count'  => $totalRows,
                    'updated_at'      => $now,
                ]
            );
        } elseif (! $dryRun && ! $config['incremental'] && $totalRows > 0) {
            DB::table('sheet_sync_states')->updateOrInsert(
                ['resource' => $resource],
                [
                    'last_synced_at'  => $now,
                    'last_row_count'  => $totalRows,
                    'updated_at'      => $now,
                ]
            );
        }

        Log::channel('sheet-sync')->info("Pull complete: {$resource}", [
            'total'   => $totalRows,
            'new'     => $totalNew,
            'since'   => $since,
            'next'    => $nextSince,
            'dry_run' => $dryRun,
        ]);

        return [
            'total'   => $totalRows,
            'new'     => $totalNew,
        ];
    }

    protected function normalizeRow(string $resource, array $row): array
    {
        $record = [];

        switch ($resource) {
            case 'gasoline_type':
                $record = [
                    'gasoline_type_id'   => $row['gasoline_type_id'] ?? '',
                    'gasoline_name'      => $row['gasoline_name'] ?? null,
                    'price_per_liter_rp' => $row['price_per_liter_rp'] ?? null,
                ];
                break;

            case 'users':
                $record = [
                    'legacy_id' => (int) ($row['id'] ?? 0),
                    'username' => $row['username'] ?? null,
                    'fullname' => $row['fullname'] ?? null,
                    'role'     => $row['role'] ?? null,
                    'email'    => $row['email'] ?? null,
                ];
                break;

            case 'vehicles':
                $record = [
                    'legacy_id'  => (int) ($row['id'] ?? 0),
                    'plat_nomer' => $row['plat_nomer'] ?? null,
                    'merk'       => $row['merk'] ?? null,
                    'jenis'      => $row['jenis'] ?? null,
                ];
                break;

            case 'permohonan':
                $record = [
                    'request_id'         => $row['request_id'] ?? '',
                    'driver_id'          => isset($row['driver_id']) && $row['driver_id'] !== null ? (int) $row['driver_id'] : null,
                    'driver_name'        => $row['driver_name'] ?? null,
                    'vehicle_id'         => isset($row['vehicle_id']) && $row['vehicle_id'] !== null ? (string) $row['vehicle_id'] : null,
                    'km_terakhir'        => $row['km_terakhir'] ?? null,
                    'gasoline_type'      => $row['gasoline_type'] ?? null,
                    'bill_payments'      => $row['bill_payments'] ?? null,
                    'request_at'         => $this->parseDate($row['request_at'] ?? null),
                    'status'             => $row['status'] ?? null,
                    'approve_at'         => $this->parseDate($row['approve_at'] ?? null),
                    'authorizer_id'      => isset($row['authorizer_id']) && $row['authorizer_id'] !== null ? (int) $row['authorizer_id'] : null,
                    'authorizer_comment' => $row['authorizer_comment'] ?? null,
                    'voucher_code'       => $row['voucher_code'] ?? null,
                    'requester_note'     => $row['requester_note'] ?? null,
                ];
                break;

            case 'transaction_proof':
                $record = [
                    'proof_id'                 => $row['proof_id'] ?? '',
                    'request_id'               => $row['request_id'] ?? null,
                    'voucher_code'             => $row['voucher_code'] ?? null,
                    'km_terakhir'              => $row['km_terakhir'] ?? null,
                    'foto_km_terakhir'         => $row['foto_km_terakhir'] ?? null,
                    'foto_struk_bbm_pertamina' => $row['foto_struk_bbm_pertamina'] ?? null,
                    'proof_created_at'           => $this->parseDate($row['created_at'] ?? null),
                ];
                break;

            case 'vehicle_health':
                $record = [
                    'legacy_id'                    => (int) ($row['id'] ?? 0),
                    'vehicle_id'                 => isset($row['vehicle_id']) && $row['vehicle_id'] !== null ? (int) $row['vehicle_id'] : null,
                    'last_transaction_proof_odoo' => $row['last_transaction_proof_odoo'] ?? null,
                    'ltpo_date'                  => $this->parseDate($row['ltpo_date'] ?? null),
                    'lates_transaction_proof_id'  => $row['lates_transaction_proof_id'] ?? null,
                    'ltp_date'                   => $this->parseDate($row['ltp_date'] ?? null),
                    'sum_of_km'                  => $row['sum_of_km'] ?? null,
                    'calc_odoo'                   => $row['calc_odoo'] ?? null,
                    'service_date_schedule'       => $row['service_date_schedule'] ?? null,
                    'determine_service_date'      => $row['determine_service_date'] ?? null,
                    'pic_services'                => $row['pic_services'] ?? null,
                    'service_notes'               => $row['service_notes'] ?? null,
                    'service_invoice_picture'      => $row['service_invoice_picture'] ?? null,
                    'service_location'             => $row['service_location'] ?? null,
                    'service_location_address'     => $row['service_location_address'] ?? null,
                    'latitude'      => $row['lat'] ?? $row['latitude'] ?? null,
                    'longitude'     => $row['long'] ?? $row['longitude'] ?? null,
                ];
                break;
        }

        return $record;
    }

    protected function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }
}