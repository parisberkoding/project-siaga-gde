<?php

namespace App\Services;

use App\Models\Approved_Request;
use App\Models\Permohonan;
use App\Models\UserSlotAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VoucherExpiryService
 *
 * Menandai voucher BBM yang sudah melewati `valid_until` (masa berlaku 50 jam)
 * sebagai kedaluwarsa. Dipakai dua jalur:
 *   - lazy check dari controller (perilaku lama), dan
 *   - scheduler `bbm-expire-vouchers` setiap 10 menit (agar tetap berjalan
 *     walau tidak ada trafik pengguna).
 *
 * Operasi bersifat idempotent: satu voucher diproses dalam satu transaksi dan
 * statusnya diperiksa ulang di dalam transaksi, sehingga pemanggilan berulang
 * (atau bersamaan) tidak membuat penalti ganda.
 */
class VoucherExpiryService
{
    /**
     * Status voucher yang masih dianggap "berjalan" sebelum kedaluwarsa.
     * Persetujuan tunggal menulis status `active`, sedangkan persetujuan
     * massal/legacy menulis `approved` — keduanya harus ikut kedaluwarsa.
     *
     * @var array<int, string>
     */
    private const ACTIVE_STATUSES = ['approved', 'active'];

    /**
     * Kedaluwarsakan seluruh voucher yang sudah lewat masa berlaku.
     *
     * @return int Jumlah voucher yang benar-benar ditransisikan ke cancelled.
     */
    public function expireStale(): int
    {
        $expired = 0;

        Approved_Request::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('valid_until', '<', now())
            ->orderBy('id')
            ->chunkById(100, function ($vouchers) use (&$expired) {
                foreach ($vouchers as $voucher) {
                    try {
                        $didExpire = DB::transaction(function () use ($voucher) {
                            // Re-check di dalam transaksi: idempotent bila dua
                            // proses berjalan bersamaan.
                            $fresh = Approved_Request::whereKey($voucher->id)
                                ->whereIn('status', self::ACTIVE_STATUSES)
                                ->where('valid_until', '<', now())
                                ->lockForUpdate()
                                ->first();

                            if (! $fresh) {
                                return false;
                            }

                            $fresh->update(['status' => 'cancelled']);

                            $permohonan = Permohonan::find($fresh->permohonan_id);

                            if ($permohonan && $permohonan->status === 'approved') {
                                $permohonan->update([
                                    'status' => 'cancelled',
                                    'requester_notes' => ($permohonan->requester_notes ?? '')
                                        ."\n\n[SYSTEM AUTO-CANCEL] Voucher expired, tidak digunakan dalam 50 jam.",
                                ]);

                                // PENALTY: kurangi 1 slot untuk 3 bulan. Dicek
                                // agar pemrosesan ulang tidak menambah penalti.
                                $alreadyPenalised = UserSlotAdjustment::where('user_id', $permohonan->requester_id)
                                    ->where('type', 'penalty')
                                    ->where('reason', 'voucher_expired')
                                    ->where('related_permohonan_id', $permohonan->id)
                                    ->exists();

                                if (! $alreadyPenalised) {
                                    UserSlotAdjustment::create([
                                        'user_id' => $permohonan->requester_id,
                                        'type' => 'penalty',
                                        'reason' => 'voucher_expired',
                                        'related_permohonan_id' => $permohonan->id,
                                        'effective_from' => now(),
                                        'effective_until' => now()->addMonths(3),
                                        'is_active' => true,
                                    ]);
                                }

                                Log::info("❌ Auto-cancelled voucher #{$fresh->id} for request #{$permohonan->id}");
                            }

                            return true;
                        });

                        if ($didExpire) {
                            $expired++;
                        }
                    } catch (\Throwable $e) {
                        // Satu voucher gagal tidak boleh menghentikan sisanya.
                        Log::error("Failed to expire voucher #{$voucher->id}: ".$e->getMessage());
                    }
                }
            });

        return $expired;
    }
}
