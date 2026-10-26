<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\Permohonan;
use App\Models\Approved_Request;
use App\Models\Transaction_Proofs;

class ImportPreprocessedDataSeeder extends Seeder
{
    /**
     * BBM Data Import Seeder v3.1
     *
     * Features:
     * - Smart fuel calculation (Pertamax: 12,350, Pertamina Dex: 13,500)
     * - Authorizer ID from spreadsheet
     * - Auto-generate trip validations with baseline logic
     * - Complete error handling & validation
     *
     * Urutan import:
     * 1. Users
     * 2. Vehicles (VehicleAndToolsConsumers)
     * 3. Permohonans (ALL status: pending, approved, completed)
     * 4. Approved_Requests (only approved & completed)
     * 5. Transaction_Proofs (only completed, with smart fuel calculation)
     * 6. Trip Validations (auto-calculate metrics with baseline logic)
     *
     * Usage:
     * 1. Place CSV files in: storage/app/import_csv/
     * 2. Run: php artisan db:seed --class=ImportPreprocessedDataSeeder
     */
    public function run()
    {
        $this->command->info('BBM Data Import v3.1 - Starting...');
        $this->command->newLine();

        // ✅ Check if migration has been run
        $this->command->info('Checking database schema...');

        try {
            // Check if trip_validations has new columns
            $hasFuelType = \Schema::hasColumn('trip_validations', 'fuel_type');
            $hasBaselineOverride = \Schema::hasColumn('trip_validations', 'baseline_override');

            if (!$hasFuelType || !$hasBaselineOverride) {
                $this->command->error('[ERROR] Migration belum dijalankan!');
                $this->command->newLine();
                $this->command->warn('Table trip_validations belum memiliki kolom enhancement.');
                $this->command->info('Silakan jalankan migration terlebih dahulu:');
                $this->command->line('   php artisan migrate:fresh');
                $this->command->line('   ATAU');
                $this->command->line('   php artisan migrate:rollback --step=1');
                $this->command->line('   php artisan migrate');
                $this->command->newLine();
                return;
            }

            $this->command->info('✅ Database schema OK');
            $this->command->newLine();

        } catch (\Exception $e) {
            $this->command->error('[ERROR]Error checking schema: ' . $e->getMessage());
            return;
        }

        $csvPath = storage_path('app/import_csv');

        // Check if directory exists
        if (!is_dir($csvPath)){
            $this->command->error("[ERROR] Directory not found: {$csvPath}");
            $this->command->info("\n Setup instructions:");
            $this->command->info("   1. mkdir -p storage/app/import_csv");
            $this->command->info("   2. Place these CSV files:");
            $this->command->info("      - users.csv");
            $this->command->info("      - vehicles.csv");
            $this->command->info("      - permohonans.csv");
            $this->command->info("      - approved_requests.csv");
            $this->command->info("      - transaction_proofs.csv");
            return;
        }

        // Check required files
        $requiredFiles = [
            'users.csv',
            'vehicles.csv',
            'permohonans.csv',
            'approved_requests.csv',
            'transaction_proofs.csv'
        ];

        foreach ($requiredFiles as $file) {
            if (!file_exists($csvPath . '/' . $file)) {
                $this->command->error("[ERROR] File not found: {$file}");
                return;
            }
        }

        DB::beginTransaction();

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Import in correct order
            $this->command->info('📊 Import Order:');
            $this->command->info('   1. Users');
            $this->command->info('   2. Vehicles');
            $this->command->info('   3. Permohonans (ALL status)');
            $this->command->info('   4. Approved Requests (approved & completed)');
            $this->command->info('   5. Transaction Proofs (completed, with smart fuel calc)');
            $this->command->info('   6. Trip Validations (auto-calculate metrics)');
            $this->command->newLine();

            // 1. Users
            $this->importUsers($csvPath);

            // 2. Vehicles
            $this->importVehicles($csvPath);

            // 3. Permohonans (ALL)
            $this->importPermohonans($csvPath);

            // 4. Approved Requests (filtered)
            $this->importApprovedRequests($csvPath);

            // 5. Transaction Proofs (filtered with smart fuel calculation)
            $this->importTransactionProofs($csvPath);

            // 6. Generate Trip Validations (auto-calculate metrics with baseline logic)
            $this->generateTripValidations();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            DB::commit();

            // Success summary
            $this->command->newLine();
            $this->command->info('[SUCCESS] ALL DATA IMPORTED SUCCESSFULLY!');
            $this->command->newLine();
            $this->printSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('[ERROR] Import failed!');
            $this->command->error('Error: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile());
            $this->command->error('Line: ' . $e->getLine());
            $this->command->newLine();
            $this->command->warn('💡 Tip: Check CSV format and foreign key relationships');
        }
    }

    /**
     * Import Users
     */
    private function importUsers($csvPath)
    {
        $this->command->info('👥 Importing Users...');

        $file = $csvPath . '/users.csv';
        $csv = array_map('str_getcsv', file($file));
        $header = array_shift($csv);

        $count = 0;
        foreach ($csv as $row) {
            if (empty($row[0])) continue; // Skip empty rows

            $data = array_combine($header, $row);

            // Hash password properly
            $hashedPassword = Hash::make($data['password']);

           // make a user code based on roles and location
           // superadimin = S00.
           // admin = A01.
           // requester = R02.
           // manager = M03
           // dispatcher = D04
           // non ga staff = N05

            // Patuha = PTH
            // PMU = PMU

           // user format = <role_code>.<incremental_id>.<location_code>
           // example = S00.0001.PMU for superadmin with id 1 in PMU location
            // Generate user code: <role_code>.<incremental_id>.<location_code>
            $roleCodeMap = [
                'superadmin' => 'S00',
                'admin' => 'A01',
                'requester' => 'R02',
                'manager' => 'M03',
                'dispatcher' => 'D04',
                'non_ga_staff' => 'N05'
            ];

            $locationCode = in_array($data['role'], ['superadmin', 'admin']) ? 'PMU' : 'PTH';
            $roleCode = $roleCodeMap[$data['role']] ?? 'X99';
            $incrementalId = str_pad((int) $data['id'], 4, '0', STR_PAD_LEFT);
            $userCode = "{$roleCode}.{$incrementalId}.{$locationCode}";


            User::create([
                'id' => (int) $data['id'],
                'username' => $data['username'],
                'password' => $hashedPassword,
                'nama_lengkap' => $data['nama_lengkap'],
                'role' => $data['role'],
                'email' => !empty($data['email']) ? $data['email'] : null,
                'default_site_id' => \App\Models\Site::where('code', in_array($data['role'], ['superadmin', 'admin']) ? 'PMU' : 'Patuha')->value('id'),
                'jabatan' => !empty($data['jabatan']) ? $data['jabatan'] : null,
                'user_code' => $userCode,
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]);

            $count++;
        }

        $this->command->info("   ✅ {$count} users imported");
        $this->command->newLine();
    }

    /**
     * Import Vehicles
     */
    private function importVehicles($csvPath)
    {
        $this->command->info('🚗 Importing Vehicles...');

        $file = $csvPath . '/vehicles.csv';
        $csv = array_map('str_getcsv', file($file));
        $header = array_shift($csv);

        $count = 0;
        foreach ($csv as $row) {
            if (empty($row[0])) continue;

            $data = array_combine($header, $row);

            VehicleAndToolsConsumers::create([
                'id' => (int) $data['id'],
                'consumerial_type' => $data['consumerial_type'],
                'consumerial_code' => $data['consumerial_code'],
                'consumerial_name' => $data['consumerial_name'],
                'consumerial_notes' => !empty($data['consumerial_notes']) ? $data['consumerial_notes'] : null,
                'is_approved' => $this->parseBool($data['is_approved']),
                'is_active' => $this->parseBool($data['is_active']),
                'approved_by' => (int) $data['approved_by'],
                'created_by' => (int) $data['created_by'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
                'deleted_at' => !empty($data['deleted_at']) ? $data['deleted_at'] : null,
            ]);

            $count++;
        }

        $this->command->info("   [INFO] {$count} vehicles imported");
        $this->command->newLine();
    }

    /**
     * Import Permohonans - ALL STATUS
     */
    private function importPermohonans($csvPath)
    {
        $this->command->info(' Importing Permohonans (ALL status)...');

        $file = $csvPath . '/permohonans.csv';
        $csv = array_map('str_getcsv', file($file));
        $header = array_shift($csv);

        $count = 0;
        $errors = 0;
        $statusCount = [];

        foreach ($csv as $row) {
            if (empty($row[0])) continue;

            // Check column count match
            if (count($header) !== count($row)) {
                $errors++;
                continue;
            }

            $data = array_combine($header, $row);

            try {
                Permohonan::create([
                    'id' => (int) $data['id'],
                    'request_number' => $data['request_number'],
                    'consumerial_tools_id' => (int) $data['consumerial_tools_id'],
                    'requester_id' => (int) $data['requester_id'],
                    'gasoline_type' => $data['gasoline_type'],
                    'bill_amounts' => (float) $data['bill_amounts'],
                    'status' => $data['status'],
                    'request_date' => $data['request_date'],
                    'requester_notes' => !empty($data['requester_notes']) ? $data['requester_notes'] : null,
                    'authorizer_notes' => !empty($data['authorizer_notes']) ? $data['authorizer_notes'] : null,
                    'authorizer_id' => !empty($data['authorizer_id']) ? (int) $data['authorizer_id'] : null,
                    'authorization_date' => !empty($data['authorization_date']) ? $data['authorization_date'] : null,
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                    'deleted_at' => !empty($data['deleted_at']) ? $data['deleted_at'] : null,
                ]);

                // Count by status
                $status = $data['status'];
                $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;

                $count++;
            } catch (\Exception $e) {
                $errors++;
                $this->command->warn("   ⚠️  Row {$data['id']}: {$e->getMessage()}");
            }
        }

        $this->command->info("   [INFO] {$count} permohonans imported");

        // Show status breakdown
        if (!empty($statusCount)) {
            $this->command->info("   [INFO] Status breakdown:");
            foreach ($statusCount as $status => $statusTotal) {
                $this->command->info("      - {$status}: {$statusTotal}");
            }
        }

        if ($errors > 0) {
            $this->command->warn("   [WARN] {$errors} rows failed");
        }
        $this->command->newLine();
    }

    /**
     * Import Approved Requests - ONLY approved & completed
     * Note: authorizer_id already from CSV (from spreadsheet)
     */
    private function importApprovedRequests($csvPath)
    {
        $this->command->info(' [INFO] Importing Approved Requests (approved & completed only)...');

        $file = $csvPath . '/approved_requests.csv';
        $csv = array_map('str_getcsv', file($file));
        $header = array_shift($csv);

        $count = 0;
        $errors = 0;

        foreach ($csv as $row) {
            if (empty($row[0])) continue;

            $data = array_combine($header, $row);

            try {
                // ✅ VALIDATION: Skip jika permohonan_id invalid
                $permohonanId = (int) $data['permohonan_id'];
                if ($permohonanId === 0) {
                    throw new \Exception("Invalid permohonan_id: 0");
                }

                // ✅ Check if permohonan exists
                $permohonanExists = Permohonan::where('id', $permohonanId)->exists();
                if (!$permohonanExists) {
                    throw new \Exception("Permohonan ID {$permohonanId} not found");
                }

                // ✅ HANDLE EMPTY DATETIME: Convert empty string to NULL
                $approvalDate = !empty($data['approval_date']) && $data['approval_date'] !== ''
                    ? $data['approval_date']
                    : null;

                $validUntil = !empty($data['valid_until']) && $data['valid_until'] !== ''
                    ? $data['valid_until']
                    : null;

                Approved_Request::create([
                    'id' => (int) $data['id'],
                    'permohonan_id' => $permohonanId,
                    'approval_date' => $approvalDate,
                    'voucher_code' => $data['voucher_code'],
                    'valid_until' => $validUntil,
                    'approved_amount' => (float) $data['approved_amount'],
                    'authorizer_id' => (int) $data['authorizer_id'], // ✅ From CSV (spreadsheet)
                    'approval_notes' => !empty($data['approval_notes']) ? $data['approval_notes'] : null,
                    'status' => $data['status'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                ]);

                $count++;
            } catch (\Exception $e) {
                $errors++;
                $this->command->warn("   [WARN] Approved Request ID {$data['id']}: {$e->getMessage()}");
            }
        }

        $this->command->info("   [INFO] {$count} approved requests imported");
        if ($errors > 0) {
            $this->command->warn("   [WARN] {$errors} rows failed");
        }
        $this->command->newLine();
    }

    /**
     * Import Transaction Proofs - ONLY completed
     * Auto-fill: requester_id
     * Smart fuel calculation: Based on gasoline_type from permohonan
     *   - Pertamax: Rp 12,350 per liter
     *   - Pertamina Dex: Rp 13,500 per liter
     * Note: fuel_volume already calculated in v2.1 preprocessing
     */
    private function importTransactionProofs($csvPath)
    {
        $this->command->info(' [INFO] Importing Transaction Proofs (completed only)...');
        $this->command->info('   [INFO] Using smart fuel calculation (Pertamax: 12,350, Dex: 13,500)');

        $file = $csvPath . '/transaction_proofs.csv';
        $csv = array_map('str_getcsv', file($file));
        $header = array_shift($csv);

        $count = 0;
        $errors = 0;
        $fuelTypeCount = ['Pertamax' => 0, 'Pertamina Dex' => 0, 'Other' => 0];

        foreach ($csv as $row) {
            if (empty($row[0])) continue;

            $data = array_combine($header, $row);

            try {
                // ✅ VALIDATION: Skip jika req_id invalid atau empty
                $reqId = trim($data['req_id']);

                if (empty($reqId) || $reqId === '' || $reqId === 'None') {
                    throw new \Exception("Invalid req_id: empty or None");
                }

                // Convert to int (handle float like "231.0")
                $reqId = (int) floatval($reqId);

                if ($reqId === 0) {
                    throw new \Exception("Invalid req_id: 0");
                }

                // Get permohonan data
                $permohonan = Permohonan::find($reqId);

                if (!$permohonan) {
                    throw new \Exception("Permohonan not found for req_id: {$reqId}");
                }

                // Auto-fill requester_id from permohonan
                $requesterId = $permohonan->requester_id;

                // ✅ SMART FUEL CALCULATION based on gasoline_type
                $fuelVolume = $data['fuel_volume'];

                if (empty($fuelVolume) || strtoupper($fuelVolume) === 'NONE' || $fuelVolume === 'None' || $fuelVolume === '0') {
                    // Calculate based on gasoline_type with correct prices
                    $fuelPrices = [
                        'Pertamax' => 12350,
                        'Pertamina Dex' => 13500
                    ];

                    $gasolineType = $permohonan->gasoline_type;
                    $fuelPrice = $fuelPrices[$gasolineType] ?? 13500; // Default to Dex price

                    $fuelVolume = round($permohonan->bill_amounts / $fuelPrice, 2);

                    // Track fuel type usage
                    if (isset($fuelPrices[$gasolineType])) {
                        $fuelTypeCount[$gasolineType]++;
                    } else {
                        $fuelTypeCount['Other']++;
                    }
                } else {
                    // Use volume from CSV (already calculated in preprocessing)
                    $gasolineType = $permohonan->gasoline_type;
                    if ($gasolineType === 'Pertamax' || $gasolineType === 'Pertamina Dex') {
                        $fuelTypeCount[$gasolineType]++;
                    } else {
                        $fuelTypeCount['Other']++;
                    }
                }

                Transaction_Proofs::create([
                    'id' => (int) $data['id'],
                    'transaction_id' => $data['transaction_id'],
                    'req_id' => $reqId,
                    'requester_id' => $requesterId, // ✅ Auto-filled
                    'voucher_number' => $data['voucher_number'],
                    'purchase_datetime' => $data['purchase_datetime'],
                    'fuel_volume' => (float) $fuelVolume, // ✅ Smart calculated
                    'km_terakhir' => (int) $data['km_terakhir'],
                    'struk_bbm_path' => $data['struk_bbm_path'],
                    'odometer_photo_path' => $data['odometer_photo_path'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at'],
                    'deleted_at' => !empty($data['deleted_at']) ? $data['deleted_at'] : null,
                ]);

                $count++;
            } catch (\Exception $e) {
                $errors++;
                $this->command->warn("   [WARN] Transaction Proof ID {$data['id']}: {$e->getMessage()}");
            }
        }

        $this->command->info("   [INFO] {$count} transaction proofs imported");
        $this->command->info("   [INFO] Fuel type breakdown:");
        foreach ($fuelTypeCount as $type => $typeCount) {
            if ($typeCount > 0) {
                $this->command->info("      [INFO] - {$type}: {$typeCount} transactions");
            }
        }

        if ($errors > 0) {
            $this->command->warn("   ⚠️  {$errors} rows failed");
        }
        $this->command->newLine();
    }

    /**
     * Generate Trip Validations from Transaction Proofs
     * Auto-calculate efficiency metrics for each trip with baseline logic
     */
    private function generateTripValidations()
    {
        $this->command->info(' [INFO] Generating Trip Validations...');

        // Get all vehicles
        $vehicles = \App\Models\VehicleAndToolsConsumers::where('consumerial_type', 'KENDARAAN')
            ->where('is_approved', true)
            ->get();

        $totalValidations = 0;
        $longDistanceCount = 0;
        $warningCount = 0;
        $criticalCount = 0;
        $errors = 0;

        foreach ($vehicles as $vehicle) {
            try {
                // Get all transaction proofs untuk kendaraan ini, sorted by date
                $proofs = \App\Models\Transaction_Proofs::whereHas('permohonan', function($q) use ($vehicle) {
                        $q->where('consumerial_tools_id', $vehicle->id)
                          ->where('status', 'completed');
                    })
                    ->where('km_terakhir', '>', 0)
                    ->orderBy('purchase_datetime', 'asc')
                    ->get();

                // Skip jika kurang dari 2 transaksi
                if ($proofs->count() < 2) {
                    continue;
                }

                // Loop untuk setiap pasangan transaksi berurutan
                for ($i = 0; $i < $proofs->count() - 1; $i++) {
                    $prevProof = $proofs[$i];
                    $nextProof = $proofs[$i + 1];

                    // Hitung selisih KM
                    $kmDiff = $nextProof->km_terakhir - $prevProof->km_terakhir;

                    // Skip jika KM turun atau terlalu besar
                    if ($kmDiff <= 0 || $kmDiff > 10000) {
                        continue;
                    }

                    // Create TripValidation
                    $validation = \App\Models\TripValidation::create([
                        'transaction_proof_previous_id' => $prevProof->id,
                        'transaction_proof_latest_id' => $nextProof->id,
                        'previous_odo' => $prevProof->km_terakhir,
                        'latest_odo' => $nextProof->km_terakhir,
                        'diff_odo' => $kmDiff,
                        'is_long_trip' => false,
                        'long_trip_notes' => null,
                        'validated_by' => null,
                        'validated_at' => null,
                    ]);

                    // ✅ AUTO-CALCULATE METRICS (pakai default baseline)
                    // Baseline: Pertamax: 15 km/L, Dex: 7 km/L
                    $validation->calculateMetrics();

                    $totalValidations++;

                    // Count by category
                    if ($kmDiff >= 300) {
                        $longDistanceCount++;
                    }

                    if ($validation->alert_level === 'warning') {
                        $warningCount++;
                    } elseif ($validation->alert_level === 'critical') {
                        $criticalCount++;
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->command->warn("   ⚠️  Vehicle {$vehicle->consumerial_code}: {$e->getMessage()}");
            }
        }

        $this->command->info("   [INFO] {$totalValidations} trip validations generated");
        $this->command->info("   [INFO] {$longDistanceCount} long distance trips (≥300 KM)");
        $this->command->info("   [WARN] {$warningCount} trips with warning level");
        $this->command->info("   [CRITICAL] {$criticalCount} trips with critical level");

        if ($errors > 0) {
            $this->command->warn("   [ERROR] {$errors} vehicles failed");
        }

        $this->command->newLine();
    }

    /**
     * Print final summary
     */
    private function printSummary()
    {
        $this->command->info('Database Summary:');
        $this->command->table(
            ['Table', 'Count'],
            [
                ['Users', User::count()],
                ['Vehicles', VehicleAndToolsConsumers::count()],
                ['Permohonans', Permohonan::count()],
                ['  ├─ pending', Permohonan::where('status', 'pending')->count()],
                ['  ├─ approved', Permohonan::where('status', 'approved')->count()],
                ['  └─ completed', Permohonan::where('status', 'completed')->count()],
                ['Approved Requests', Approved_Request::count()],
                ['Transaction Proofs', Transaction_Proofs::count()],
                ['Trip Validations', \App\Models\TripValidation::count()],
                ['  ├─ need validation', \App\Models\TripValidation::whereNull('validated_at')
                    ->where(function($q) {
                        $q->where('diff_odo', '>=', 300)
                          ->orWhere('is_premature_refuel', true)
                          ->orWhere('efficiency_deviation_percent', '<', -20);
                    })->count()],
                ['  ├─ with custom baseline', \App\Models\TripValidation::whereNotNull('baseline_override')->count()],
                ['  ├─ warning level', \App\Models\TripValidation::where('alert_level', 'warning')->count()],
                ['  └─ critical level', \App\Models\TripValidation::where('alert_level', 'critical')->count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('[SUCCESS]Import completed successfully');
        $this->command->newLine();

        $this->command->info('[INFO] Next steps:');
        $this->command->info('   1. Verify images exist in: public/transaction_proofs/');
        $this->command->info('   2. Test application functionality');
        $this->command->info('   3. Check user logins & voucher generation');
        $this->command->info('   4. Review trip validations & efficiency metrics');
        $this->command->info('   5. Verify fuel volume calculations (Pertamax vs Dex)');
        $this->command->newLine();

        $this->command->info('[INFO] Fuel Calculation Info:');
        $this->command->info('   - Pertamax: Rp 12,350 per liter');
        $this->command->info('   - Pertamina Dex: Rp 13,500 per liter');
        $this->command->info('   - Trip baseline: Pertamax 15 km/L, Dex 7 km/L');
        $this->command->info('   - Superadmin dapat mengubah baseline per-trip di halaman validasi');
    }

    /**
     * Helper: Parse boolean from CSV
     */
    private function parseBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim($value));
        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }
}
