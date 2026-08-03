<?php

namespace App\Services\Lunch;

use App\Models\LearningProfile;
use App\Models\LunchForecast;
use App\Models\LunchTransaction;

/**
 * Learning Engine.
 *
 * Ground truth = lunch_transactions (kehadiran aktual via QR).
 * Forecast TIDAK pernah menjadi sumber learning — engine hanya membaca
 * transaksi aktual, lalu:
 *   1. memperbarui learning_profiles (pola kebiasaan per karyawan/site)
 *      yang menjadi input "Historical Pattern" untuk Forecast H+1;
 *   2. menutup loop akurasi: mengisi actual_site_id dan
 *      is_prediction_correct pada baris forecast hari itu.
 */
class LearningService
{
    public function recordTransaction(LunchTransaction $transaction): void
    {
        if (! $transaction->employee_default_id) {
            return; // Unresolved: tidak ada yang bisa dipelajari.
        }

        $this->updateLearningProfile($transaction);
        $this->reconcileForecast($transaction);
    }

    private function updateLearningProfile(LunchTransaction $transaction): void
    {
        $profile = LearningProfile::firstOrCreate(
            [
                'employee_default_id' => $transaction->employee_default_id,
                'site_id'             => $transaction->site_id,
            ],
            ['transaction_count' => 0]
        );

        $profile->update([
            'transaction_count'     => $profile->transaction_count + 1,
            'last_transaction_date' => $transaction->transaction_date,
        ]);
    }

    private function reconcileForecast(LunchTransaction $transaction): void
    {
        $forecast = LunchForecast::where('employee_default_id', $transaction->employee_default_id)
            ->whereDate('lunch_date', $transaction->transaction_date)
            ->first();

        if (! $forecast) {
            return; // Tidak ada forecast untuk hari ini — hanya profil yang belajar.
        }

        $forecast->actual_site_id = $transaction->site_id;

        if ($forecast->predicted_site_id) {
            $forecast->is_prediction_correct =
                (int) $forecast->predicted_site_id === (int) $transaction->site_id;
        }

        $forecast->save();
    }
}
