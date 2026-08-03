<?php

namespace App\Services\Lunch;

use App\Models\EmployeeDefault;
use App\Models\LunchForecast;
use App\Models\LunchForecastBatch;
use App\Models\LunchTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Forecast Engine (H-1 / H+1).
 *
 * Prioritas sumber prediksi per karyawan:
 *   1. Manual Override        — diinput admin, tidak pernah ditimpa engine
 *   2. Self Booking           — booking mandiri oleh user (kalender H+14)
 *   3. Tomorrow Confirmation  — jawaban popup "Besok makan dimana?" kemarin
 *   4. Historical Pattern     — learning_profiles (transaksi aktual)
 *   5. Default Site           — employee_defaults.default_site_id
 *
 * Vehicle Tracking sudah DIHAPUS sebagai prediction source: perusahaan
 * memakai GPS vendor dan trip internal tidak lagi merepresentasikan
 * lokasi makan siang.
 */
class LunchForecastService
{
    public const ALGORITHM_VERSION = 'v2-no-vehicle';

    /** Minimal transaksi historis agar pola dianggap bermakna. */
    public const MIN_HISTORY = 3;

    /**
     * Generate (atau regenerate) forecast untuk satu tanggal.
     */
    public function generateForDate(string $date, ?User $generator = null, string $type = 'manual'): LunchForecastBatch
    {
        return DB::transaction(function () use ($date, $generator, $type) {
            $batch = LunchForecastBatch::create([
                'forecast_date'        => $date,
                'generated_by_type'    => $type,
                'generated_by_user_id' => $generator?->id,
                'algorithm_version'    => self::ALGORITHM_VERSION,
                'history_sample_size'  => LunchTransaction::resolved()->count(),
                'status'               => 'finalized',
                'finalized_at'         => now(),
            ]);

            $yesterday = Carbon::parse($date)->subDay()->toDateString();

            // Tomorrow Confirmation: jawaban popup pada transaksi H-1.
            $confirmations = LunchTransaction::query()
                ->whereDate('transaction_date', $yesterday)
                ->whereNotNull('employee_default_id')
                ->whereNotNull('tomorrow_site_id')
                ->pluck('tomorrow_site_id', 'employee_default_id');

            $employees = EmployeeDefault::active()
                ->with('learningProfiles')
                ->get();

            foreach ($employees as $employee) {
                $existing = LunchForecast::where('employee_default_id', $employee->id)
                    ->whereDate('lunch_date', $date)
                    ->first();

                // Prioritas 1 — Manual Override milik admin tidak ditimpa.
                if ($existing && $existing->prediction_source === 'manual') {
                    $existing->update(['forecast_batch_id' => $batch->id]);
                    continue;
                }

                // Prioritas 2 — Self Booking milik user tidak ditimpa.
                if ($existing && $existing->prediction_source === 'self_booking') {
                    $existing->update(['forecast_batch_id' => $batch->id]);
                    continue;
                }

                [$siteId, $source, $confidence] = $this->predict($employee, $confirmations);

                if (! $siteId) {
                    continue; // Tidak ada informasi apa pun — tidak dipaksakan.
                }

                $this->upsertForecast($employee->id, $date, [
                    'user_id'           => $employee->user_id,
                    'predicted_site_id' => $siteId,
                    'prediction_source' => $source,
                    'confidence'        => $confidence,
                    'forecast_batch_id' => $batch->id,
                    'created_by'        => $generator?->id,
                    'updated_by'        => $generator?->id,
                ]);
            }

            return $batch;
        });
    }

    /**
     * @param  \Illuminate\Support\Collection  $confirmations  employee_default_id => site_id
     * @return array{0: ?int, 1: string, 2: float}
     */
    private function predict(EmployeeDefault $employee, $confirmations): array
    {
        // Prioritas 3 — Tomorrow Confirmation.
        if ($confirmations->has($employee->id)) {
            return [(int) $confirmations->get($employee->id), 'confirmation', 95.0];
        }

        // Prioritas 4 — Historical Pattern dari Learning Engine.
        $profiles = $employee->learningProfiles;
        $total    = $profiles->sum('transaction_count');

        if ($total >= self::MIN_HISTORY) {
            $dominant = $profiles->sortByDesc('transaction_count')->first();
            $share    = round($dominant->transaction_count / $total * 100, 2);

            if ($share >= 50) {
                return [$dominant->site_id, 'historical', $share];
            }
        }

        // Prioritas 5 — Default Site.
        if ($employee->default_site_id) {
            return [$employee->default_site_id, 'default', 50.0];
        }

        return [null, 'default', 0.0];
    }

    /**
     * Manual override oleh admin — prioritas tertinggi.
     */
    public function applyOverride(EmployeeDefault $employee, string $date, int $siteId, User $admin): LunchForecast
    {
        return $this->upsertForecast($employee->id, $date, [
            'user_id'           => $employee->user_id,
            'predicted_site_id' => $siteId,
            'prediction_source' => 'manual',
            'confidence'        => 100,
            'updated_by'        => $admin->id,
        ]);
    }

    /**
     * Update-or-create satu baris forecast per (karyawan, tanggal).
     * Lookup memakai whereDate — pencocokan string mentah pada kolom
     * date bisa meleset antar driver database.
     */
    private function upsertForecast(int $employeeId, string $date, array $attributes): LunchForecast
    {
        $forecast = LunchForecast::where('employee_default_id', $employeeId)
            ->whereDate('lunch_date', $date)
            ->first();

        if ($forecast) {
            $forecast->update($attributes);

            return $forecast;
        }

        return LunchForecast::create($attributes + [
            'employee_default_id' => $employeeId,
            'lunch_date'          => $date,
        ]);
    }

    /**
     * Akurasi forecast untuk satu tanggal (baris yang sudah punya actual).
     *
     * @return array{total: int, evaluated: int, correct: int, accuracy: ?float}
     */
    public function accuracyForDate(string $date): array
    {
        $rows = LunchForecast::forDate($date)->get(['is_prediction_correct']);

        $evaluated = $rows->whereNotNull('is_prediction_correct');
        $correct   = $evaluated->where('is_prediction_correct', true)->count();

        return [
            'total'     => $rows->count(),
            'evaluated' => $evaluated->count(),
            'correct'   => $correct,
            'accuracy'  => $evaluated->count() > 0
                ? round($correct / $evaluated->count() * 100, 1)
                : null,
        ];
    }
}
