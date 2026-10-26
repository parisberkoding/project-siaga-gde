<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehiclePart;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingDetail;
use App\Models\VehicleCheckingItemResult;
use App\Models\VehiclePartsReplacement;
use App\Models\VehiclePartsReplacementDetail;
use App\Models\VehiclePartsReplacementTransaction;
use App\Models\VprtDetail;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $dispatcher = User::where('role', 'dispatcher')->first()
            ?? User::where('role', 'superadmin')->first()
            ?? User::first();
        $driver = User::where('role', 'requester')->first()
            ?? User::first();
        $admin = User::where('role', 'superadmin')->first()
            ?? User::first();

        $vehicles = VehicleAndToolsConsumers::where('consumerial_type', 'KENDARAAN')
            ->where('is_approved', true)
            ->take(2)
            ->get();

        if ($vehicles->count() < 2) {
            $this->command->error('Butuh minimal 2 kendaraan approved. Jalankan VTCSeeder dulu.');
            return;
        }

        $bodyParts = VehiclePart::where('checking_group', 'body')
            ->where('is_active', true)
            ->get();
        $routineParts = VehiclePart::where('checking_group', 'routine')
            ->where('is_active', true)
            ->get();

        if ($bodyParts->isEmpty() || $routineParts->isEmpty()) {
            $this->command->error('Vehicle parts belum ada. Jalankan VehiclePartSeeder dulu.');
            return;
        }

        $now = Carbon::now();
        $vehicle1 = $vehicles[0];
        $vehicle2 = $vehicles[1];

        // 1. Checking schedules
        $schedules = [
            [
                'schedule_code'        => 'SCH-' . $vehicle1->consumerial_code . '-DAILY',
                'vehicle_id'           => $vehicle1->id,
                'check_frequency'      => 'daily',
                'assigned_dispatcher_id' => $dispatcher->id,
                'last_checked_at'      => $now->copy()->subDay(),
                'last_checked_by'      => $dispatcher->id,
                'next_due_date'        => $now->toDateString(),
                'status'               => 'due',
                'is_active'            => true,
            ],
            [
                'schedule_code'        => 'SCH-' . $vehicle1->consumerial_code . '-WEEKLY',
                'vehicle_id'           => $vehicle1->id,
                'check_frequency'      => 'weekly',
                'assigned_dispatcher_id' => $dispatcher->id,
                'last_checked_at'      => $now->copy()->subDays(5),
                'last_checked_by'      => $dispatcher->id,
                'next_due_date'        => $now->copy()->addDays(2)->toDateString(),
                'status'               => 'pending',
                'is_active'            => true,
            ],
            [
                'schedule_code'        => 'SCH-' . $vehicle1->consumerial_code . '-MONTHLY',
                'vehicle_id'           => $vehicle1->id,
                'check_frequency'      => 'monthly',
                'assigned_dispatcher_id' => $dispatcher->id,
                'last_checked_at'      => $now->copy()->subDays(20),
                'last_checked_by'      => $dispatcher->id,
                'next_due_date'        => $now->copy()->addDays(10)->toDateString(),
                'status'               => 'pending',
                'is_active'            => true,
            ],
            [
                'schedule_code'        => 'SCH-' . $vehicle1->consumerial_code . '-BODY',
                'vehicle_id'           => $vehicle1->id,
                'check_frequency'      => 'body',
                'assigned_dispatcher_id' => $dispatcher->id,
                'last_checked_at'      => $now->copy()->subDays(25),
                'last_checked_by'      => $dispatcher->id,
                'next_due_date'        => $now->copy()->addDays(5)->toDateString(),
                'status'               => 'pending',
                'is_active'            => true,
            ],
        ];

        foreach ($schedules as $s) {
            DB::table('vehicle_checking_schedules')->updateOrInsert(
                ['schedule_code' => $s['schedule_code']],
                array_merge($s, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // 2. Period for this month (vehicle_checkings)
        $periodStart = $now->copy()->startOfMonth()->toDateString();
        $periodEnd   = $now->copy()->endOfMonth()->toDateString();
        $checkingCode = 'CHK-' . str_replace(' ', '', $vehicle1->consumerial_code) . '-' . $now->format('Y-m');

        $existingChecking = DB::table('vehicle_checkings')
            ->where('vehicle_id', $vehicle1->id)
            ->where('period_start', $periodStart)
            ->first();

        if ($existingChecking) {
            $checkingId = $existingChecking->id;
        } else {
            $checkingId = DB::table('vehicle_checkings')->insertGetId([
                'checking_code' => $checkingCode,
                'vehicle_id'    => $vehicle1->id,
                'nopol'         => $vehicle1->consumerial_code,
                'revision'      => 0,
                'period_start'  => $periodStart,
                'period_end'    => $periodEnd,
                'generated_by'  => $dispatcher->id,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // 3. Checking details
        $detailCodeRoutine = 'CHK-' . str_replace(' ', '', $vehicle1->consumerial_code) . '-' . $now->format('Y-m') . '-001';
        $existingRoutine = DB::table('vehicle_checking_details')
            ->where('checking_detail_code', $detailCodeRoutine)->first();
        $detailIdRoutine = $existingRoutine ? $existingRoutine->id
            : DB::table('vehicle_checking_details')->insertGetId([
                'checking_detail_code'  => $detailCodeRoutine,
                'vehicle_checking_id'   => $checkingId,
                'type'                  => 'routine',
                'sub_type'              => 'daily',
                'dispatcher_id'         => $dispatcher->id,
                'current_driver_id'     => $driver->id,
                'checked_at'            => $now->copy()->subDay(),
                'driver_acknowledged'   => true,
                'driver_acknowledged_at'=> $now->copy()->subDay()->addHour(),
                'notes'                 => null,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

        $detailCodeBody = 'CHK-' . str_replace(' ', '', $vehicle1->consumerial_code) . '-' . $now->format('Y-m') . '-002';
        $existingBody = DB::table('vehicle_checking_details')
            ->where('checking_detail_code', $detailCodeBody)->first();
        $detailIdBody = $existingBody ? $existingBody->id
            : DB::table('vehicle_checking_details')->insertGetId([
                'checking_detail_code'  => $detailCodeBody,
                'vehicle_checking_id'   => $checkingId,
                'type'                  => 'body',
                'sub_type'              => null,
                'dispatcher_id'         => $dispatcher->id,
                'current_driver_id'     => $driver->id,
                'checked_at'            => $now->copy()->subDays(25),
                'driver_acknowledged'   => true,
                'driver_acknowledged_at'=> $now->copy()->subDays(25)->addHour(),
                'notes'                 => 'Ada beberapa cat kecil di body bagian belakang.',
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

        // 4. Item results for routine check
        $routineStatuses = ['ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ng',
                            'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok', 'ok'];
        $needsReplacement = false;

        foreach ($routineParts as $i => $part) {
            $status = $routineStatuses[$i] ?? 'ok';
            $needsRep = ($status === 'ng');
            if ($needsRep) $needsReplacement = true;

            DB::table('vehicle_checking_item_results')->insert([
                'vehicle_checking_detail_id' => $detailIdRoutine,
                'vehicle_part_id'            => $part->id,
                'status'                     => $status,
                'notes'                      => $status === 'ng' ? 'Oli mesin kotor, perlu ganti' : null,
                'needs_replacement'          => $needsRep,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);
        }

        // Body check item results
        $bodyStatuses = ['baik', 'baik', 'cukup', 'baik', 'baik', 'baik', 'cukup', 'baik', 'baik', 'baik'];
        foreach ($bodyParts as $i => $part) {
            $status = $bodyStatuses[$i] ?? 'baik';
            $notes = null;
            if ($status === 'cukup' && $part->part_code === 'PRT-AC') {
                $notes = 'AC agak kurang dingin, perlu diperiksa';
            }
            if ($status === 'cukup' && $part->part_code === 'PRT-SEATBELT') {
                $notes = 'Seatbelt penumpang belakang agak macet';
            }

            DB::table('vehicle_checking_item_results')->insert([
                'vehicle_checking_detail_id' => $detailIdBody,
                'vehicle_part_id'            => $part->id,
                'status'                     => $status,
                'notes'                      => $notes,
                'needs_replacement'          => $status === 'kurang' || $status === 'tidak',
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);
        }

        // 5. Replacement request (from the routine check that had 'ng' item)
        if ($needsReplacement) {
            $vprCode = 'VPR-' . str_replace(' ', '', $vehicle1->consumerial_code) . '-' . $now->format('Y-m') . '-001';
            $ngItem = VehicleCheckingItemResult::where('vehicle_checking_detail_id', $detailIdRoutine)
                ->where('needs_replacement', true)
                ->first();

            if ($ngItem) {
                $existingVpr = DB::table('vehicle_parts_replacements')
                    ->where('vpr_code', $vprCode)
                    ->first();

                if ($existingVpr) {
                    $vprId = $existingVpr->id;
                } else {
                    $vprId = DB::table('vehicle_parts_replacements')->insertGetId([
                        'vpr_code'                    => $vprCode,
                        'vehicle_checking_detail_id'  => $detailIdRoutine,
                        'vehicle_id'                  => $vehicle1->id,
                        'nopol'                       => $vehicle1->consumerial_code,
                        'dispatcher_id'               => $dispatcher->id,
                        'last_driver_id'              => $driver->id,
                        'desired_driver_id'           => $driver->id,
                        'approved_by'                 => $admin->id,
                        'status'                      => 'completed',
                        'approved_at'                 => $now->copy()->subDay()->addHour(),
                        'completed_at'                => $now->copy()->subDay()->addHours(3),
                        'created_at'                  => $now,
                        'updated_at'                  => $now,
                    ]);
                }

                $vprdCode = 'VPRD-' . $vprId . '-001';
                $existingVprd = DB::table('vehicle_parts_replacement_details')
                    ->where('vprd_code', $vprdCode)
                    ->first();

                if (!$existingVprd) {
                    DB::table('vehicle_parts_replacement_details')->insert([
                        'vprd_code'                      => $vprdCode,
                        'vehicle_parts_replacement_id'   => $vprId,
                        'vehicle_part_id'                => $ngItem->vehicle_part_id,
                        'item_name'                      => $ngItem->part->name ?? 'Oli Mesin',
                        'condition_status'               => 'ng',
                        'dispatcher_notes'               => $ngItem->notes ?? 'Perlu diganti',
                        'created_at'                     => $now,
                        'updated_at'                     => $now,
                    ]);
                }

                // Service transaction
                $vpr = VehiclePartsReplacement::find($vprId);
                if ($vpr) {
                    $vprtCode = VehiclePartsReplacementTransaction::generateCode($vpr->vpr_code);
                    $transaction = VehiclePartsReplacementTransaction::firstOrCreate(
                        ['vprt_code' => $vprtCode],
                        [
                            'vehicle_parts_replacement_id' => $vprId,
                            'vpr_code'                     => $vpr->vpr_code,
                            'driver_id'                    => $driver->id,
                            'service_location'             => 'Bengkel AHASS Patuha',
                            'service_date'                 => $now->copy()->subDay()->toDateString(),
                            'total_amount'                 => 350000,
                            'proof_photo'                  => 'demo/struk-' . $vprId . '.jpg',
                            'deadline_date'                => $now->copy()->subDay()->addDays(3)->toDateString(),
                            'reported_at'                  => $now->copy()->subDay()->addHours(2),
                        ]
                    );

                    // Transaction detail
                    if (!$transaction->details()->exists()) {
                        VprtDetail::create([
                            'vprt_detail_code' => VprtDetail::generateCode($transaction->id, 1),
                            'vprt_id'          => $transaction->id,
                            'part_name'        => $ngItem->part->name ?? 'Oli Mesin',
                            'quantity'         => 1,
                            'unit_price'       => 350000,
                            'total_price'      => 350000,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Seeded vehicle schedules, checkings, item results, replacement & service transaction.');
    }
}
