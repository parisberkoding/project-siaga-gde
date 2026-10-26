<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDefault;
use App\Models\LunchForecast;
use App\Models\LunchTransaction;
use App\Models\Site;
use App\Models\Unit;
use App\Services\Lunch\EmployeeRegistrationService;
use App\Services\Lunch\IdentityResolutionService;
use App\Services\Lunch\LearningService;
use App\Services\Lunch\LunchForecastService;
use Illuminate\Http\Request;

/**
 * QR Attendance — halaman PUBLIK, tanpa login.
 *
 * Flow:
 *   scan QR site → cari nama → (kalau belum ada) isi nama/divisi/unit
 *   → check-in tercatat → satu popup "Besok makan dimana?"
 *   → pilihan site HANYA dari unit pegawai → forecast H+1 langsung tersimpan.
 *
 * Site yang di-scan adalah lokasi makan HARI INI. Tidak ada lagi modal
 * "makan di mana hari ini" yang membingungkan; pertanyaan yang tersisa hanya
 * untuk besok, sesuai kebutuhan forecast katering.
 *
 * Keamanan tanpa auth:
 *  - endpoint dibatasi throttle (lihat routes/web.php);
 *  - konfirmasi besok hanya sah dengan confirmation_token dari submit;
 *  - pilihan site divalidasi terhadap unit pegawai di server, bukan hanya UI.
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
        $site = Site::active()->with('unit')->where('code', $siteCode)->firstOrFail();

        $units = Unit::active()
            ->with(['sites' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $sites = $units->flatMap->sites->values();

        return view('lunch.public.form', compact('site', 'units', 'sites'));
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
     * Submit attendance: resolusi identitas → simpan transaksi → siapkan
     * pilihan site besok yang sudah dibatasi unit.
     */
    public function submit(
        Request $request,
        IdentityResolutionService $resolver,
        LearningService $learning,
        EmployeeRegistrationService $registration
    ) {
        $validated = $request->validate([
            'site_code'   => 'required|string|exists:sites,code',
            'name'        => 'required|string|max:150',
            'employee_id' => 'nullable|integer|exists:employee_defaults,id',
            'position'    => 'nullable|string|max:100',
            'division'    => 'nullable|string|max:100',
            'unit_id'     => 'nullable|integer|exists:units,id',
        ]);

        $site = Site::active()->where('code', $validated['site_code'])->firstOrFail();

        $employee = null;

        if (! empty($validated['employee_id'])) {
            $employee = EmployeeDefault::active()
                ->with(['user', 'unit', 'defaultSite'])
                ->find($validated['employee_id']);
        }

        if (! $employee) {
            $result = $resolver->resolve($validated['name']);
            $employee = $result['employee'];
        }

        $registered = false;

        // Pegawai belum dikenal, atau sudah ada di master tetapi belum punya
        // akun / unit: daftarkan lewat jalur yang sama. Akun yang dibuat tetap
        // nonaktif dan tanpa role — superadmin unit yang menerbitkan kredensial.
        if (! $employee) {
            if (empty($validated['unit_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama belum terdaftar. Lengkapi Divisi dan Unit untuk mendaftar.',
                    'needs_registration' => true,
                ], 422);
            }

            $registrationResult = $registration->registerFromScan(
                $site,
                $validated['name'],
                $validated['division'] ?? null,
                (int) $validated['unit_id'],
                $validated['position'] ?? null
            );

            $employee = $registrationResult['employee'];
            $registered = true;
        } elseif (! $employee->user_id || ! $employee->unit_id) {
            $registrationResult = $registration->registerFromScan(
                $site,
                $employee->employee_name,
                $validated['division'] ?? $employee->division,
                (int) ($employee->resolvedUnitId() ?? $validated['unit_id'] ?? $site->unit_id),
                $validated['position'] ?? $employee->position
            );

            $employee = $registrationResult['employee'];
            $registered = $registrationResult['created'] || $employee->wasRecentlyCreated;
        }

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Identitas belum bisa dikenali. Hubungi admin GA unit.',
            ], 422);
        }

        $transaction = $resolver->saveTransaction(
            $site,
            $validated['name'],
            $validated['position'] ?? null,
            $validated['division'] ?? null,
            $employee->id
        );

        $learning->recordTransaction($transaction);

        $employee = $employee->fresh(['user', 'unit', 'defaultSite']);
        $tomorrowSites = $employee->selectableSites();

        // Fallback terakhir: site yang di-scan, supaya popup tetap bisa
        // ditampilkan meski master belum punya unit/site default.
        if ($tomorrowSites->isEmpty()) {
            $tomorrowSites = collect([$site]);
        }

        $defaultSiteId = $employee->default_site_id ?: $site->id;

        if (! $tomorrowSites->contains('id', $defaultSiteId)) {
            $defaultSiteId = $tomorrowSites->first()->id;
        }

        $tomorrow = now()->addDay()->toDateString();

        $tomorrowForecast = LunchForecast::where('employee_default_id', $employee->id)
            ->whereDate('lunch_date', $tomorrow)
            ->first();

        $accountPending = $employee->user
            && $employee->user->registration_source === 'scan'
            && $employee->user->isPendingCredentials();

        return response()->json([
            'success'             => true,
            'transaction_id'      => $transaction->id,
            'token'               => $transaction->confirmation_token,
            'employee_id'         => $employee->id,
            'slug'                => $employee->slug,
            'resolved'            => true,
            'registered'          => $registered,
            'employee_name'       => $employee->employee_name,
            'division'            => $employee->division,
            'unit'                => $employee->resolvedUnit()?->name,
            'unit_id'             => $employee->resolvedUnitId(),
            'has_sso'             => $employee->user?->auth_provider === 'sso',
            'has_account'         => $employee->user_id !== null,
            'account_pending'     => $accountPending,
            'needs_verification'  => $employee->isPendingVerification(),
            'default_site_id'     => $defaultSiteId,
            'tomorrow_site_id'    => $tomorrowForecast?->predicted_site_id,
            'tomorrow_date'       => $tomorrow,
            'tomorrow_sites'      => $tomorrowSites->map(fn (Site $s) => [
                'id'         => $s->id,
                'code'       => $s->code,
                'name'       => $s->name,
                'type'       => $s->type,
                'is_default' => (int) $s->id === (int) $defaultSiteId,
            ])->values(),
            'default_dine_type'   => self::defaultDineType($employee->division),
            'site'                => $site->only(['code', 'name']),
            'message'             => $registered
                ? "Terima kasih, {$employee->employee_name}! Kehadiran tercatat. Akun SIAGA kamu sedang menunggu verifikasi admin unit."
                : "Terima kasih, {$employee->employee_name}! Kehadiran di {$site->name} tercatat.",
        ]);
    }

    /**
     * Koreksi site HARI INI (opsional, dipakai admin/helpdesk).
     * UI scan tidak lagi memakai endpoint ini karena QR = lokasi hari ini.
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

        $site = Site::findOrFail($validated['site_id']);
        $employee = $transaction->employee;

        // Pegawai yang sudah dikenali hanya boleh dikoreksi ke site unitnya.
        if ($employee && ! $employee->selectableSites()->contains('id', $site->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Site tersebut berada di luar unit pegawai.',
            ], 422);
        }

        $transaction->update(['site_id' => $site->id]);

        return response()->json([
            'success' => true,
            'site'    => $site->only(['code', 'name']),
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

        LunchTransaction::create([
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
        if (! $division) {
            return 'dine_in';
        }

        $division = strtolower($division);
        $takeAwayKeywords = ['produksi', 'production', 'manufacturing', 'lapangan', 'lap', 'field', 'operator', 'op'];
        foreach ($takeAwayKeywords as $keyword) {
            if (str_contains($division, $keyword)) {
                return 'take_away';
            }
        }

        return 'dine_in';
    }

    /**
     * Popup "Besok makan dimana?".
     *
     * Pilihan site divalidasi terhadap unit pegawai. Bila dilewati, forecast
     * besok tetap terbentuk dari site default — sesuai kesepakatan bahwa yang
     * tidak menjawab diisi nilai default.
     */
    public function confirmTomorrow(
        Request $request,
        LunchTransaction $transaction,
        LunchForecastService $forecastService
    ) {
        $validated = $request->validate([
            'token'   => 'required|string',
            'site_id' => 'nullable|exists:sites,id', // null = lewati
        ]);

        $tokenValid = $transaction->confirmation_token
            && hash_equals($transaction->confirmation_token, $validated['token']);

        $isToday = $transaction->transaction_date->isToday();

        if (! $tokenValid || ! $isToday) {
            return response()->json(['success' => false, 'message' => 'Sesi konfirmasi tidak valid.'], 422);
        }

        $employee = $transaction->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Identitas belum dikenali admin. Jawaban besok belum bisa disimpan.',
            ], 422);
        }

        $siteId = $validated['site_id'] ?? null;

        if ($siteId && ! $employee->selectableSites()->contains('id', (int) $siteId)) {
            return response()->json([
                'success' => false,
                'message' => 'Site tersebut berada di luar unit kamu.',
            ], 422);
        }

        $source = $siteId ? 'confirmation' : 'default';
        $forecast = $forecastService->recordTomorrowChoice($employee, $siteId, $source);

        $transaction->update(['tomorrow_site_id' => $siteId]);

        $chosenSite = $forecast?->predictedSite ?? Site::find($siteId);

        return response()->json([
            'success' => true,
            'message' => $siteId
                ? "Terima kasih! Besok tercatat di {$chosenSite?->name}."
                : 'Baik, dilewati. Besok otomatis memakai site default'
                    .($chosenSite ? " ({$chosenSite->name})" : '').'.',
            'forecast' => $forecast ? [
                'date'      => $forecast->lunch_date->toDateString(),
                'site_id'   => $forecast->predicted_site_id,
                'site_name' => $chosenSite?->name,
                'source'    => $forecast->prediction_source,
            ] : null,
        ]);
    }
}
