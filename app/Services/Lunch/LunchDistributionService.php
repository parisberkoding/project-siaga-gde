<?php

namespace App\Services\Lunch;

use App\Models\LunchCustomPurchase;
use App\Models\LunchDistribution;
use App\Models\LunchForecast;
use App\Models\LunchGuestReservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lunch Distribution: snapshot jumlah porsi per site per tanggal yang
 * dikirim ke vendor catering.
 *
 * employee_count  = baris forecast dengan predicted_site = site tsb.
 * guest_count     = reservasi tamu berstatus confirmed.
 *
 * Custom Purchase yang menandai `replaces_catering` akan menol-kan angka
 * katering pada tanggal + site tersebut, sehingga vendor tidak ditagih dua
 * kali untuk hari yang sama.
 *
 * Disimpan sebagai snapshot yang bisa dikunci (locked) — begitu angka
 * dikirim ke vendor, perubahan forecast/reservasi tidak boleh diam-diam
 * menggeser angka pesanan.
 */
class LunchDistributionService
{
    /**
     * Bangun / perbarui distribusi untuk satu tanggal.
     *
     * @param  array<int, int>|null  $siteIds  null = global
     * @return Collection<int, LunchDistribution>
     */
    public function buildForDate(string $date, ?User $actor = null, ?array $siteIds = null): Collection
    {
        $employeeBySite = LunchForecast::forDate($date)
            ->inUnit($siteIds)
            ->whereNotNull('predicted_site_id')
            ->selectRaw('predicted_site_id, count(*) as total')
            ->groupBy('predicted_site_id')
            ->pluck('total', 'predicted_site_id');

        $guestBySite = LunchGuestReservation::query()
            ->whereDate('reservation_date', $date)
            ->inUnit($siteIds)
            ->confirmed()
            ->selectRaw('site_id, sum(guest_count) as total')
            ->groupBy('site_id')
            ->pluck('total', 'site_id');

        $customBySite = LunchCustomPurchase::query()
            ->whereDate('purchase_date', $date)
            ->inUnit($siteIds)
            ->replacesCatering()
            ->get()
            ->keyBy('site_id');

        $latestBatchId = LunchForecast::forDate($date)
            ->inUnit($siteIds)
            ->whereNotNull('forecast_batch_id')
            ->max('forecast_batch_id');

        $sites = Site::active()
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->get();

        return $sites->map(function (Site $site) use ($date, $employeeBySite, $guestBySite, $customBySite, $latestBatchId) {
            $existing = LunchDistribution::forDate($date)
                ->where('site_id', $site->id)
                ->first();

            // Distribusi yang sudah dikunci tidak boleh berubah otomatis.
            if ($existing && $existing->is_locked) {
                return $existing;
            }

            $custom = $customBySite->get($site->id);

            $employeeCount = $custom ? 0 : (int) ($employeeBySite[$site->id] ?? 0);
            $guestCount    = $custom ? 0 : (int) ($guestBySite[$site->id] ?? 0);

            $attributes = [
                'employee_count'     => $employeeCount,
                'guest_count'        => $guestCount,
                'total_count'        => $employeeCount + $guestCount,
                'forecast_batch_id'  => $latestBatchId,
                'custom_purchase_id' => $custom?->id,
                'is_custom_purchase' => $custom !== null,
            ];

            if ($existing) {
                $existing->update($attributes);

                return $existing;
            }

            return LunchDistribution::create($attributes + [
                'distribution_date' => $date,
                'site_id'           => $site->id,
            ]);
        });
    }

    /**
     * Kunci seluruh distribusi tanggal tersebut (angka final vendor).
     *
     * @param  array<int, int>|null  $siteIds  null = global
     */
    public function lockForDate(string $date, User $actor, ?array $siteIds = null): int
    {
        return LunchDistribution::forDate($date)
            ->inUnit($siteIds)
            ->where('is_locked', false)
            ->update([
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $actor->id,
            ]);
    }
}
