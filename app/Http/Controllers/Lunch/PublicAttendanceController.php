<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\LunchTransaction;
use App\Models\Site;
use App\Services\Lunch\IdentityResolutionService;
use App\Services\Lunch\LearningService;
use Illuminate\Http\Request;

/**
 * QR Attendance — halaman PUBLIK, tanpa login.
 *
 * Flow: Scan QR lokasi → Attendance Form → Cari Nama (alias lookup)
 *       → Submit → Popup "Besok makan dimana?" → Done.
 *
 * Keamanan tanpa auth:
 *  - endpoint dibatasi throttle (lihat routes/web.php);
 *  - konfirmasi besok hanya sah dengan confirmation_token yang
 *    dikembalikan pada submit yang sama;
 *  - tidak ada data selain nama/jabatan yang terekspos di lookup.
 */
class PublicAttendanceController extends Controller
{
    /**
     * Landing: buka kamera dan scan QR lokasi.
     */
    public function landing()
    {
        return view('lunch.public.scan');
    }

    /**
     * Attendance form untuk site tertentu (dibuka dari QR).
     */
    public function form(string $siteCode)
    {
        $site = Site::active()->where('code', $siteCode)->firstOrFail();

        $sites = Site::active()->get();

        return view('lunch.public.form', compact('site', 'sites'));
    }

    /**
     * AJAX: autocomplete nama via alias lookup.
     */
    public function lookup(Request $request, IdentityResolutionService $resolver)
    {
        $validated = $request->validate(['q' => 'required|string|max:150']);

        return response()->json([
            'results' => $resolver->search($validated['q']),
        ]);
    }

    /**
     * Submit attendance: jalankan Identity Resolution Engine lalu simpan
     * Lunch Transaction (primary data = hasil lookup, raw = audit trail).
     */
    public function submit(
        Request $request,
        IdentityResolutionService $resolver,
        LearningService $learning
    ) {
        $validated = $request->validate([
            'site_code'   => 'required|string|exists:sites,code',
            'name'        => 'required|string|max:150',
            'employee_id' => 'nullable|integer|exists:employee_defaults,id',
            'position'    => 'nullable|string|max:100',
            'division'    => 'nullable|string|max:100',
        ]);

        $site = Site::active()->where('code', $validated['site_code'])->firstOrFail();

        $transaction = $resolver->saveTransaction(
            $site,
            $validated['name'],
            $validated['position'] ?? null,
            $validated['division'] ?? null,
            $validated['employee_id'] ?? null
        );

        $learning->recordTransaction($transaction);

        return response()->json([
            'success'           => true,
            'transaction_id'    => $transaction->id,
            'token'             => $transaction->confirmation_token,
            'resolved'          => $transaction->isResolved(),
            'employee_name'     => $transaction->employee_name ?? $transaction->raw_alias,
            'division'          => $transaction->division,
            'default_dine_type' => self::defaultDineType($transaction->division),
            'site'              => $site->only(['code', 'name']),
            'message'           => $transaction->isResolved()
                ? "Terima kasih, {$transaction->employee_name}! Kehadiran di {$site->name} tercatat."
                : 'Kehadiran tercatat. Nama belum dikenali — admin akan memverifikasi.',
        ]);
    }

    /**
     * Pilih lokasi makan (ganti site_id pada transaksi yang sudah tercatat).
     */
    public function chooseSite(Request $request, LunchTransaction $transaction)
    {
        $validated = $request->validate([
            'token'   => 'required|string',
            'site_id' => 'required|exists:sites,id',
        ]);

        $tokenValid = $transaction->confirmation_token
            && hash_equals($transaction->confirmation_token, $validated['token']);

        if (! $tokenValid) {
            return response()->json(['success' => false, 'message' => 'Sesi tidak valid.'], 422);
        }

        $transaction->update(['site_id' => $validated['site_id']]);
        $site = Site::find($validated['site_id']);

        return response()->json([
            'success' => true,
            'site'    => $site?->only(['code', 'name']),
        ]);
    }

    /**
     * Pilih Dine In / Take Away.
     */
    public function chooseDineType(Request $request, LunchTransaction $transaction)
    {
        $validated = $request->validate([
            'token'     => 'required|string',
            'dine_type' => 'required|in:dine_in,take_away',
        ]);

        $tokenValid = $transaction->confirmation_token
            && hash_equals($transaction->confirmation_token, $validated['token']);

        if (! $tokenValid) {
            return response()->json(['success' => false, 'message' => 'Sesi tidak valid.'], 422);
        }

        $transaction->update(['dine_type' => $validated['dine_type']]);

        return response()->json(['success' => true]);
    }

    /**
     * Guest attendance — nama + institusi.
     */
    public function guestSubmit(Request $request)
    {
        $validated = $request->validate([
            'site_code'   => 'required|string|exists:sites,code',
            'guest_name'  => 'required|string|max:150',
            'institution' => 'required|string|max:200',
        ]);

        $site = Site::active()->where('code', $validated['site_code'])->firstOrFail();

        $transaction = LunchTransaction::create([
            'transaction_date' => today(),
            'site_id'          => $site->id,
            'employee_name'    => $validated['guest_name'],
            'institution'      => $validated['institution'],
            'raw_alias'        => $validated['guest_name'],
            'source'           => 'guest',
            'scanned_at'       => now(),
        ]);

        return response()->json([
            'success'       => true,
            'employee_name' => $validated['guest_name'],
            'site'          => $site->only(['code', 'name']),
            'message'       => "Terima kasih, {$validated['guest_name']}! Kehadiran tamu di {$site->name} tercatat.",
        ]);
    }

    /**
     * Default dine type berdasarkan divisi.
     */
    public static function defaultDineType(?string $division): string
    {
        if (!$division) return 'dine_in';
        $division = strtolower($division);
        $takeAwayKeywords = ['produksi', 'production', 'manufacturing', 'lapangan', 'lap', 'field', 'operator', 'op'];
        foreach ($takeAwayKeywords as $keyword) {
            if (str_contains($division, $keyword)) return 'take_away';
        }
        return 'dine_in';
    }

    /**
     * Popup "Besok makan dimana?" — simpan jawaban (PMU/PTH/BCMP/Lewati).
     * Jawaban menjadi input prioritas ke-2 Forecast H+1.
     */
    public function confirmTomorrow(Request $request, LunchTransaction $transaction)
    {
        $validated = $request->validate([
            'token'   => 'required|string',
            'site_id' => 'nullable|exists:sites,id', // null = Lewati
        ]);

        $tokenValid = $transaction->confirmation_token
            && hash_equals($transaction->confirmation_token, $validated['token']);

        $isToday = $transaction->transaction_date->isToday();

        if (! $tokenValid || ! $isToday) {
            return response()->json(['success' => false, 'message' => 'Sesi konfirmasi tidak valid.'], 422);
        }

        $transaction->update(['tomorrow_site_id' => $validated['site_id'] ?? null]);

        return response()->json([
            'success' => true,
            'message' => $validated['site_id']
                ? 'Terima kasih! Jawaban kamu membantu akurasi pesanan catering besok.'
                : 'Baik, dilewati. Sampai jumpa besok!',
        ]);
    }
}
