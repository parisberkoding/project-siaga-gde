<?php

namespace App\Http\Controllers\Lunch;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAlias;
use App\Models\EmployeeDefault;
use App\Models\Site;
use App\Services\Lunch\NameNormalizer;
use Illuminate\Http\Request;

class EmployeeDefaultController extends Controller
{
    /**
     * Master Employee Default: siapa saja yang makan siang dan di site
     * mana default-nya. Ini titik masuk pertama arsitektur Lunch V2.
     */
    public function index(Request $request)
    {
        $query = EmployeeDefault::with(['defaultSite', 'aliases']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhereHas('aliases', fn ($a) => $a->where('alias', 'like', "%{$search}%"));
            });
        }

        if ($siteId = $request->get('site_id')) {
            $query->where('default_site_id', $siteId);
        }

        $employees = $query->orderBy('employee_name')->paginate(20)->withQueryString();
        $sites     = Site::active()->get();

        $totalsBySite = EmployeeDefault::active()
            ->whereNotNull('default_site_id')
            ->selectRaw('default_site_id, count(*) as total')
            ->groupBy('default_site_id')
            ->pluck('total', 'default_site_id');

        return view('lunch.employee-defaults.index', compact('employees', 'sites', 'totalsBySite'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $employee = EmployeeDefault::create($validated);
        $this->syncAliases($employee, $request->input('aliases', ''));

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, EmployeeDefault $employeeDefault)
    {
        $validated = $this->validatePayload($request, $employeeDefault->id);

        $employeeDefault->update($validated);
        $this->syncAliases($employeeDefault, $request->input('aliases', ''));

        return back()->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(EmployeeDefault $employeeDefault)
    {
        // Non-aktifkan alih-alih hard delete: histori transaksi & learning
        // profile karyawan tetap utuh untuk analytics.
        $employeeDefault->update(['is_active' => false]);

        return back()->with('success', 'Karyawan dinonaktifkan dari Lunch Management.');
    }

    public function restore(EmployeeDefault $employeeDefault)
    {
        $employeeDefault->update(['is_active' => true]);

        return back()->with('success', 'Karyawan diaktifkan kembali.');
    }

    /**
     * Export master ke CSV (dipakai juga sebagai template import).
     */
    public function export()
    {
        $fileName = 'employee-defaults-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['employee_code', 'employee_name', 'division', 'position', 'default_site', 'aliases', 'is_active']);

            EmployeeDefault::with(['defaultSite', 'aliases'])
                ->orderBy('employee_name')
                ->each(function (EmployeeDefault $e) use ($out) {
                    fputcsv($out, [
                        $e->employee_code,
                        $e->employee_name,
                        $e->division,
                        $e->position,
                        $e->defaultSite?->code,
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

        $siteIdByCode = Site::pluck('id', 'code');

        $handle  = fopen($request->file('file')->getRealPath(), 'r');
        $header  = fgetcsv($handle);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, array_pad($row, count($header), null));

            if (empty($data['employee_name'])) {
                $skipped++;
                continue;
            }

            $attributes = [
                'employee_name'   => trim($data['employee_name']),
                'division'        => $data['division'] ?: null,
                'position'        => $data['position'] ?: null,
                'default_site_id' => $siteIdByCode[$data['default_site'] ?? ''] ?? null,
                'is_active'       => ($data['is_active'] ?? '1') != '0',
            ];

            $employee = ! empty($data['employee_code'])
                ? EmployeeDefault::firstOrNew(['employee_code' => trim($data['employee_code'])])
                : new EmployeeDefault();

            $wasNew = ! $employee->exists;
            $employee->fill($attributes)->save();
            $wasNew ? $created++ : $updated++;

            $this->syncAliases($employee, str_replace('|', ',', (string) ($data['aliases'] ?? '')), true);
        }

        fclose($handle);

        return back()->with('success', "Import selesai: {$created} baru, {$updated} diperbarui, {$skipped} dilewati.");
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'employee_code'   => 'nullable|string|max:50|unique:employee_defaults,employee_code' . ($ignoreId ? ',' . $ignoreId : ''),
            'employee_name'   => 'required|string|max:150',
            'division'        => 'nullable|string|max:100',
            'position'        => 'nullable|string|max:100',
            'default_site_id' => 'nullable|exists:sites,id',
            'is_active'       => 'boolean',
        ]);
    }

    /**
     * Sinkronkan alias manual dari input dipisah koma.
     * Alias hasil learning tidak ikut dihapus.
     */
    private function syncAliases(EmployeeDefault $employee, string $rawAliases, bool $keepExisting = false): void
    {
        $aliases = collect(explode(',', $rawAliases))
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
