<?php

namespace App\Http\Controllers;

use App\Models\LunchGuestReservation;
use App\Models\Site;
use App\Services\UnitScope;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CateringGuestController extends Controller
{
    /**
     * Kalender tamu catering bulanan.
     *
     * Site pada form diambil dari unit user, bukan hardcode PMU/Patuha.
     */
    public function calendar(Request $request)
    {
        $scope = UnitScope::for($request->user());
        $siteIds = $scope->siteIds();

        $month      = $request->get('month', now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();
        $prevMonth  = $monthStart->copy()->subMonth()->format('Y-m');
        $nextMonth  = $monthStart->copy()->addMonth()->format('Y-m');

        $guests = LunchGuestReservation::with('site.unit')
            ->inUnit($siteIds)
            ->whereDate('reservation_date', '>=', $monthStart->toDateString())
            ->whereDate('reservation_date', '<=', $monthEnd->toDateString())
            ->get()
            ->groupBy(fn ($g) => $g->reservation_date->toDateString());

        $today      = Carbon::today();
        $h2Deadline = $today->copy()->addDays(2);

        $pendingH2 = LunchGuestReservation::with('site')
            ->inUnit($siteIds)
            ->where('status', 'pending')
            ->whereDate('reservation_date', '>=', $today->toDateString())
            ->whereDate('reservation_date', '<=', $h2Deadline->toDateString())
            ->orderBy('reservation_date')
            ->get();

        $sites = $scope->sites();
        $units = $scope->units();

        return view('lunch.guests-calendar', compact(
            'monthStart', 'prevMonth', 'nextMonth', 'month',
            'guests', 'pendingH2', 'sites', 'units'
        ));
    }

    /**
     * Simpan data tamu baru.
     */
    public function store(Request $request)
    {
        $scope = UnitScope::for($request->user());

        $validated = $this->validatePayload($request);
        $site = Site::findOrFail((int) $validated['site_id']);
        $scope->ensureSite($site);

        LunchGuestReservation::create([
            'reservation_date' => $validated['tanggal'],
            'institution'      => $validated['instansi'],
            'guest_count'      => $validated['jumlah'],
            'site_id'          => $site->id,
            'notes'            => $validated['catatan'] ?? null,
            'status'           => 'pending',
            'created_by'       => auth()->id(),
        ]);

        return back()->with('success', 'Data tamu berhasil ditambahkan.');
    }

    /**
     * Update data tamu (hanya jika masih pending).
     */
    public function update(Request $request, LunchGuestReservation $cateringGuest)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($cateringGuest->site_id);

        if ($cateringGuest->status !== 'pending') {
            return back()->with('error', 'Data tamu yang sudah dikonfirmasi atau dibatalkan tidak bisa diubah.');
        }

        $validated = $this->validatePayload($request, false);
        $site = Site::findOrFail((int) $validated['site_id']);
        $scope->ensureSite($site);

        $cateringGuest->update([
            'reservation_date' => $validated['tanggal'],
            'institution'      => $validated['instansi'],
            'guest_count'      => $validated['jumlah'],
            'site_id'          => $site->id,
            'notes'            => $validated['catatan'] ?? null,
        ]);

        return back()->with('success', 'Data tamu berhasil diperbarui.');
    }

    /**
     * Hapus data tamu (hanya jika masih pending).
     */
    public function destroy(Request $request, LunchGuestReservation $cateringGuest)
    {
        UnitScope::for($request->user())->ensureSite($cateringGuest->site_id);

        if ($cateringGuest->status !== 'pending') {
            return back()->with('error', 'Data tamu yang sudah dikonfirmasi tidak bisa dihapus.');
        }

        $cateringGuest->delete();

        return back()->with('success', 'Data tamu berhasil dihapus.');
    }

    /**
     * Konfirmasi tamu jadi datang (H-2 confirmation).
     */
    public function h2Confirm(Request $request, LunchGuestReservation $cateringGuest)
    {
        UnitScope::for($request->user())->ensureSite($cateringGuest->site_id);

        if ($cateringGuest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Status tidak valid.'], 422);
        }

        $cateringGuest->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Tamu dari {$cateringGuest->institution} dikonfirmasi. Akan masuk ke order catering tanggal {$cateringGuest->reservation_date->format('d/m/Y')}.",
        ]);
    }

    /**
     * Batalkan tamu (H-2 cancellation).
     */
    public function h2Cancel(Request $request, LunchGuestReservation $cateringGuest)
    {
        UnitScope::for($request->user())->ensureSite($cateringGuest->site_id);

        if ($cateringGuest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Status tidak valid.'], 422);
        }

        $cateringGuest->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => "Tamu dari {$cateringGuest->institution} dibatalkan. Tidak akan dimasukkan ke order catering.",
        ]);
    }

    /**
     * Validasi form tamu. `site_id` wajib dan harus berada di unit user.
     */
    private function validatePayload(Request $request, bool $checkDate = true): array
    {
        return $request->validate([
            'tanggal'  => ($checkDate ? 'required|date|after_or_equal:today' : 'required|date'),
            'instansi' => 'required|string|max:200',
            'jumlah'   => 'required|integer|min:1|max:9999',
            'site_id'  => 'required|exists:sites,id',
            'catatan'  => 'nullable|string|max:500',
        ]);
    }
}
