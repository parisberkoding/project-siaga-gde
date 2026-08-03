<?php

namespace App\Http\Controllers;

use App\Models\LunchGuestReservation;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CateringGuestController extends Controller
{
    /**
     * Kalender tamu catering bulanan.
     * Superadmin bisa melihat, menambah, dan mengkonfirmasi tamu.
     */
    public function calendar(Request $request)
    {
        $month      = $request->get('month', now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();
        $prevMonth  = $monthStart->copy()->subMonth()->format('Y-m');
        $nextMonth  = $monthStart->copy()->addMonth()->format('Y-m');

        // Semua tamu dalam bulan ini, digroup per tanggal
        $guests = LunchGuestReservation::with('site')
            ->whereDate('reservation_date', '>=', $monthStart->toDateString())
            ->whereDate('reservation_date', '<=', $monthEnd->toDateString())
            ->get()->groupBy(fn ($g) => $g->reservation_date->toDateString());

        // Cari tamu yang butuh konfirmasi H-2 (status pending, 0–2 hari lagi)
        $today      = Carbon::today();
        $h2Deadline = $today->copy()->addDays(2);

        $pendingH2 = LunchGuestReservation::with('site')
            ->where('status', 'pending')
            ->whereDate('reservation_date', '>=', $today->toDateString())
            ->whereDate('reservation_date', '<=', $h2Deadline->toDateString())
            ->orderBy('reservation_date')
            ->get();

        return view('lunch.guests-calendar', compact(
            'monthStart', 'prevMonth', 'nextMonth',
            'guests', 'pendingH2'
        ));
    }

    /**
     * Simpan data tamu baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal'  => 'required|date|after_or_equal:today',
            'instansi' => 'required|string|max:200',
            'jumlah'   => 'required|integer|min:1|max:9999',
            'site'     => 'required|in:PMU,Patuha',
            'catatan'  => 'nullable|string|max:500',
        ]);

        LunchGuestReservation::create([
            'reservation_date' => $validated['tanggal'],
            'institution'      => $validated['instansi'],
            'guest_count'      => $validated['jumlah'],
            'site_id'          => Site::where('code', $validated['site'])->value('id'),
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
        if ($cateringGuest->status !== 'pending') {
            return back()->with('error', 'Data tamu yang sudah dikonfirmasi atau dibatalkan tidak bisa diubah.');
        }

        $validated = $request->validate([
            'tanggal'  => 'required|date',
            'instansi' => 'required|string|max:200',
            'jumlah'   => 'required|integer|min:1|max:9999',
            'site'     => 'required|in:PMU,Patuha',
            'catatan'  => 'nullable|string|max:500',
        ]);

        $cateringGuest->update([
            'reservation_date' => $validated['tanggal'],
            'institution'      => $validated['instansi'],
            'guest_count'      => $validated['jumlah'],
            'site_id'          => Site::where('code', $validated['site'])->value('id'),
            'notes'            => $validated['catatan'] ?? null,
        ]);

        return back()->with('success', 'Data tamu berhasil diperbarui.');
    }

    /**
     * Hapus data tamu (hanya jika masih pending).
     */
    public function destroy(LunchGuestReservation $cateringGuest)
    {
        if ($cateringGuest->status !== 'pending') {
            return back()->with('error', 'Data tamu yang sudah dikonfirmasi tidak bisa dihapus.');
        }

        $cateringGuest->delete();

        return back()->with('success', 'Data tamu berhasil dihapus.');
    }

    /**
     * Superadmin konfirmasi tamu jadi datang (H-2 confirmation).
     */
    public function h2Confirm(LunchGuestReservation $cateringGuest)
    {
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
     * Superadmin batalkan tamu (H-2 cancellation).
     */
    public function h2Cancel(LunchGuestReservation $cateringGuest)
    {
        if ($cateringGuest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Status tidak valid.'], 422);
        }

        $cateringGuest->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => "Tamu dari {$cateringGuest->institution} dibatalkan. Tidak akan dimasukkan ke order catering.",
        ]);
    }
}
