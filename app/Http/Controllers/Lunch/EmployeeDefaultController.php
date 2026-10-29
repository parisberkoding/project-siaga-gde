<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAlias;
use App\Models\EmployeeDefault;
use App\Models\Site;
use App\Models\Unit;
use App\Services\Lunch\NameNormalizer;
use App\Services\UnitScope;
use Illuminate\Http\Request;

class EmployeeDefaultController extends Controller
{
    /**
     * Master karyawan Lunch Management.
     *
     * Unit adalah parameter utama: admin unit hanya melihat karyawan unitnya.
     * Karyawan hasil scan ditandai "pending" sampai diverifikasi.
     */
    public function index(Request $request)
    {
        $scope = UnitScope::for($request->user());
        $siteIds = $scope->siteIds();

        $query = EmployeeDefault::with(['defaultSite.unit', 'unit', 'aliases', 'user'])
            ->inUnit($siteIds);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhereHas('aliases', fn ($a) => $a->where('alias', 'like', "%{$search}%"));
            });
        }

        if ($siteId = $request->get('site_id')) {
            $scope->ensureSite((int) $siteId);
            $query->where('default_site_id', $siteId);
        }

        if ($unitId = $request->get('unit_id')) {
            $scope->ensureUnit((int) $unitId);
            $query->where('unit_id', $unitId);
        }

        if ($verification = $request->get('verification')) {
            if ($verification === 'pending') {
                $query->pendingVerification();
            } elseif ($verification === 'verified') {
                $query->verified();
            }
        }

        $employees = $query->orderBy('employee_name')->paginate(20)->withQueryString();

        $sites = $scope->sites();
        $units = $scope->units();

        $totalsBySite = EmployeeDefault::active()
            ->inUnit($siteIds)
            ->whereNotNull('default_site_id')
            ->selectRaw('default_site_id, count(*) as total')
            ->groupBy('default_site_id')
            ->pluck('total', 'default_site_id');

        $pendingCount = EmployeeDefault::active()
            ->inUnit($siteIds)
            ->pendingVerification()
            ->count();

        return view('lunch.employee-defaults.index', compact(
            'employees', 'sites', 'units', 'totalsBySite', 'pendingCount'
        ));
    }

    public function store(Request $request)
    {
        $scope = UnitScope::for($request->user());
        $validated = $this->validatePayload($request, $scope);

        $this->ensureUnitAndSite($scope, $validated);

        $validated['verification_status'] = 'verified';
        $validated['source'] = 'admin';
        $validated['created_by'] = $request->user()?->id;

        $employee = EmployeeDefault::create($validated);
        $this->syncAliases($employee, $request->input('aliases', ''));

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, EmployeeDefault $employeeDefault)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureEmployee($employeeDefault);

        $validated = $this->validatePayload($request, $scope, $employeeDefault->id);

        $this->ensureUnitAndSite($scope, $validated);

        $validated['verification_status'] = 'verified';
        $validated['source'] = $employeeDefault->source === 'scan' ? 'scan' : 'admin';

        $employeeDefault->update($validated);
        $this->syncAliases($employeeDefault, $request->input('aliases', ''));

        return back()->with('success', 'Data karyawan berhasil diperbarui.');
    }

    /**
     * Verifikasi karyawan hasil scan.
     */
    public function verify(Request $request, EmployeeDefault $employeeDefault)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureEmployee($employeeDefault);

        $employeeDefault->update([
            'verification_status' => 'verified',
            'is_active'           => true,
        ]);

        return back()->with('success', "Karyawan {$employeeDefault->employee_name} berhasil diverifikasi.");
    }

    public function destroy(Request $request, EmployeeDefault $employeeDefault)
    {
        UnitScope::for($request->user())->ensureEmployee($employeeDefault);

        // Non-aktifkan alih-alih hard delete: histori transaksi & learning
        // profile karyawan tetap utuh untuk analytics.
        $employeeDefault->update(['is_active' => false]);

        return back()->with('success', 'Karyawan dinonaktifkan dari Lunch Management.');
    }

    public function restore(Request $request, EmployeeDefault $employeeDefault)
    {
        UnitScope::for($request->user())->ensureEmployee($employeeDefault);

        $employeeDefault->update(['is_active' => true]);

        return back()->with('success', 'Karyawan diaktifkan kembali.');
    }

    /**
     * Export master ke CSV (dipakai juga sebagai template import).
     */
    public function export(Request $request)
    {
        $scope = UnitScope::for($request->user());
        $siteIds = $scope->siteIds();

        $fileName = 'employee-defaults-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($siteIds) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'employee_code', 'employee_name', 'division', 'position',
                'unit', 'default_site', 'verification_status', 'aliases', 'is_active',
            ]);

            EmployeeDefault::with(['defaultSite', 'unit', 'aliases'])
                ->inUnit($siteIds)
                ->orderBy('employee_name')
                ->each(function (EmployeeDefault $e) use ($out) {
                    fputcsv($out, [
                        $e->employee_code,
                        $e->employee_name,
                        $e->division,
                        $e->position,
                        $e->unit?->code,
                        $e->defaultSite?->code,
                        $e->verification_status,
                        $e->aliases->pluck('alias')->implode('|'),
                        $e->is_active ? 1 : 0,
                    ]);
                });

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Import CSV sederhana (kolom sama dengan hasil export).
     * Baris dengan employee_code yang sudah ada akan di-update.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $scope = UnitScope::for($request->user());

        $siteIdByCode = Site::query()
            ->when($scope->siteIds() !== null, fn ($q) => $q->whereIn('id', $scope->siteIds()))
            ->pluck('id', 'code');

        $unitIdByCode = Unit::pluck('id', 'code');

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, array_pad($row, count($header), null));

            if (empty($data['employee_name'])) {
                $skipped++;
                continue;
            }

            $defaultSiteId = $siteIdByCode[$data['default_site'] ?? ''] ?? null;
            $unitId = $unitIdByCode[$data['unit'] ?? ''] ?? null;

            if (! $unitId && $defaultSiteId) {
                $unitId = Site::whereKey($defaultSiteId)->value('unit_id');
            }

            if (! $scope->allowsUnit($unitId)) {
                $skipped++;
                continue;
            }

            if ($defaultSiteId && ! $scope->allowsSite($defaultSiteId)) {
                $skipped++;
                continue;
            }

            $attributes = [
                'employee_name'       => trim($data['employee_name']),
                'division'            => ($data['division'] ?? null) ?: null,
                'position'            => ($data['position'] ?? null) ?: null,
                'unit_id'             => $unitId,
                'default_site_id'     => $defaultSiteId,
                'verification_status' => ($data['verification_status'] ?? null) ?: 'verified',
                'is_active'           => ($data['is_active'] ?? '1') != '0',
            ];

            $employee = ! empty($data['employee_code'])
                ? EmployeeDefault::firstOrNew(['employee_code' => trim($data['employee_code'])])
                : new EmployeeDefault();

            if ($employee->exists) {
                $scope->ensureEmployee($employee);
            }

            $wasNew = ! $employee->exists;
            $employee->fill($attributes)->save();
            $wasNew ? $created++ : $updated++;

            $this->syncAliases($employee, str_replace('|', ',', (string) ($data['aliases'] ?? '')), true);
        }

        fclose($handle);

        return back()->with('success', "Import selesai: {$created} baru, {$updated} diperbarui, {$skipped} dilewati.");
    }

    private function validatePayload(Request $request, UnitScope $scope, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'employee_code'   => 'nullable|string|max:50|unique:employee_defaults,employee_code' . ($ignoreId ? ',' . $ignoreId : ''),
            'employee_name'   => 'required|string|max:150',
            'division'        => 'nullable|string|max:100',
            'position'        => 'nullable|string|max:100',
            'unit_id'         => 'nullable|exists:units,id',
            'default_site_id' => 'nullable|exists:sites,id',
            'is_active'       => 'boolean',
        ]);

        if (! empty($validated['unit_id'])) {
            $scope->ensureUnit((int) $validated['unit_id']);
        }

        if (! empty($validated['default_site_id'])) {
            $scope->ensureSite((int) $validated['default_site_id']);
        }

        return $validated;
    }

    /**
     * Pastikan unit & site konsisten: site harus milik unit yang dipilih.
     */
    private function ensureUnitAndSite(UnitScope $scope, array &$validated): void
    {
        $siteId = $validated['default_site_id'] ?? null;
        $unitId = $validated['unit_id'] ?? null;

        if (! $unitId && $siteId) {
            $unitId = Site::whereKey($siteId)->value('unit_id');
            $validated['unit_id'] = $unitId;
        }

        if (! $unitId && ! $scope->isGlobal()) {
            $validated['unit_id'] = $scope->user()?->unit_id;
        }

        if ($unitId) {
            $scope->ensureUnit((int) $unitId);
        }

        if ($siteId) {
            $site = Site::whereKey($siteId)->first();
            if (! $site) {
                abort(422, 'Site tidak valid.');
            }
            if ($unitId && (int) $site->unit_id !== (int) $unitId) {
                abort(422, "Site {$site->name} bukan bagian dari unit yang dipilih.");
            }
        }
    }

    /**
     * Sinkronkan alias manual dari input dipisah koma.
     * Alias hasil learning tidak ikut dihapus.
     */
    private function syncAliases(EmployeeDefault $employee, ?string $rawAliases, bool $keepExisting = false): void
    {
        $aliases = collect(explode(',', (string) $rawAliases))
            ->map(fn ($a) => NameNormalizer::normalize($a))
            ->filter(fn ($a) => $a !== '')
            ->push(NameNormalizer::normalize($employee->employee_name))
            ->unique()
            ->values();

        if (! $keepExisting) {
            $employee->aliases()->where('source', 'manual')
                ->whereNotIn('alias', $aliases)
                ->delete();
        }

        foreach ($aliases as $alias) {
            EmployeeAlias::firstOrCreate(
                ['employee_default_id' => $employee->id, 'alias' => $alias],
                ['source' => 'manual']
            );
        }
    }
}
