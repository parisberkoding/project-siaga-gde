<?php

namespace App\Services\Lunch;

use App\Models\EmployeeAlias;
use App\Models\EmployeeDefault;
use App\Models\LunchTransaction;
use App\Models\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Identity Resolution Engine.
 *
 * Pipeline:
 *   Raw Input → Normalize → Alias Lookup → Employee Lookup
 *   → Historical Transaction Lookup → Fuzzy Match
 *   → Confidence Score → Resolved Employee → Lunch Transaction
 *
 * Raw input TIDAK pernah dipakai sebagai data utama; ia hanya disimpan
 * sebagai audit trail pada lunch_transactions.raw_*. Data utama selalu
 * hasil lookup terhadap master employee_defaults.
 */
class IdentityResolutionService
{
    /** Confidence minimum agar fuzzy match dianggap sah. */
    public const FUZZY_THRESHOLD = 70.0;

    /** Confidence minimum agar alias baru boleh dipelajari otomatis. */
    public const LEARN_ALIAS_THRESHOLD = 85.0;

    /**
     * Pencarian nama untuk autocomplete di form attendance (AJAX).
     * Mengembalikan kandidat terbatas — tanpa data sensitif.
     */
    public function search(string $query, int $limit = 8): Collection
    {
        $normalized = NameNormalizer::normalize($query);

        if (mb_strlen($normalized) < 2) {
            return collect();
        }

        $byAlias = EmployeeAlias::query()
            ->where('alias', 'like', $normalized . '%')
            ->with('employee.defaultSite')
            ->limit($limit)
            ->get()
            ->pluck('employee');

        $byName = EmployeeDefault::query()
            ->active()
            ->where('employee_name', 'like', '%' . $normalized . '%')
            ->with('defaultSite')
            ->limit($limit)
            ->get();

        return $byAlias->merge($byName)
            ->filter(fn ($e) => $e && $e->is_active)
            ->unique('id')
            ->take($limit)
            ->values()
            ->map(fn (EmployeeDefault $e) => [
                'id'       => $e->id,
                'slug'     => $e->slug,
                'name'     => $e->employee_name,
                'position' => $e->position,
                'division' => $e->division,
                'site'     => $e->defaultSite?->code,
            ]);
    }

    /**
     * Resolve raw input menjadi karyawan master + metadata confidence.
     *
     * @return array{employee: ?EmployeeDefault, method: string, confidence: float}
     */
    public function resolve(string $rawAlias, ?int $selectedEmployeeId = null): array
    {
        // Jika user memilih dari daftar autocomplete, itu resolusi manual
        // dengan kepastian penuh — tidak perlu tebak-tebakan.
        if ($selectedEmployeeId) {
            $employee = EmployeeDefault::active()->find($selectedEmployeeId);
            if ($employee) {
                return ['employee' => $employee, 'method' => 'manual', 'confidence' => 100.0];
            }
        }

        $normalized = NameNormalizer::normalize($rawAlias);

        if ($normalized === '') {
            return ['employee' => null, 'method' => 'unresolved', 'confidence' => 0.0];
        }

        // 1. Alias lookup — exact match pada alias ternormalisasi.
        $alias = EmployeeAlias::with('employee')
            ->where('alias', $normalized)
            ->first();

        if ($alias && $alias->employee && $alias->employee->is_active) {
            return ['employee' => $alias->employee, 'method' => 'alias_exact', 'confidence' => 100.0];
        }

        // 2. Employee lookup — exact match nama lengkap master.
        $employee = EmployeeDefault::active()
            ->get(['id', 'employee_name'])
            ->first(fn ($e) => NameNormalizer::normalize($e->employee_name) === $normalized);

        if ($employee) {
            return [
                'employee'   => EmployeeDefault::find($employee->id),
                'method'     => 'name_exact',
                'confidence' => 95.0,
            ];
        }

        // 3. Historical lookup — raw input sama pernah berhasil di-resolve.
        $historical = LunchTransaction::query()
            ->whereNotNull('employee_default_id')
            ->where('raw_alias', $rawAlias)
            ->latest('id')
            ->with('employee')
            ->first();

        if ($historical && $historical->employee && $historical->employee->is_active) {
            return ['employee' => $historical->employee, 'method' => 'historical', 'confidence' => 90.0];
        }

        // 4. Fuzzy match terhadap seluruh alias + nama master.
        $best = null;
        $bestScore = 0.0;

        foreach (EmployeeDefault::active()->with('aliases')->get() as $candidate) {
            $candidates = $candidate->aliases->pluck('alias')
                ->push(NameNormalizer::normalize($candidate->employee_name));

            foreach ($candidates as $target) {
                $score = NameNormalizer::similarity($normalized, $target);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $candidate;
                }
            }
        }

        if ($best && $bestScore >= self::FUZZY_THRESHOLD) {
            return ['employee' => $best, 'method' => 'fuzzy', 'confidence' => round($bestScore, 2)];
        }

        return ['employee' => null, 'method' => 'unresolved', 'confidence' => round($bestScore, 2)];
    }

    /**
     * Simpan Lunch Transaction dari hasil resolve.
     *
     * Primary data = hasil lookup (employee master); raw input hanya
     * masuk kolom raw_* sebagai audit trail.
     */
    public function saveTransaction(
        Site $site,
        string $rawAlias,
        ?string $rawPosition,
        ?string $rawDivision,
        ?int $selectedEmployeeId = null
    ): LunchTransaction {
        $today = now()->toDateString();

        $result   = $this->resolve($rawAlias, $selectedEmployeeId);
        $employee = $result['employee'];

        // Karyawan yang sama hanya tercatat sekali per hari — scan ulang
        // memperbarui site, bukan menggandakan porsi.
        $transaction = null;
        if ($employee) {
            $transaction = LunchTransaction::where('employee_default_id', $employee->id)
                ->whereDate('transaction_date', $today)
                ->first();
        }

        $attributes = [
            'transaction_date'    => $today,
            'site_id'             => $site->id,
            'employee_default_id' => $employee?->id,
            'employee_name'       => $employee?->employee_name,
            'division'            => $employee?->division,
            'position'            => $employee?->position,
            'raw_alias'           => $rawAlias,
            'raw_position'        => $rawPosition,
            'raw_division'        => $rawDivision,
            'confidence_score'    => $result['confidence'],
            'resolution_method'   => $result['method'],
            'confirmation_token'  => Str::random(48),
            'source'              => 'qr',
            'scanned_at'          => now(),
        ];

        if ($transaction) {
            $transaction->update($attributes);
        } else {
            $transaction = LunchTransaction::create($attributes);
        }

        if ($employee) {
            $this->learnAlias($employee, $rawAlias, $result['method'], $result['confidence']);
        }

        return $transaction->fresh(['site', 'employee']);
    }

    /**
     * Learning Engine untuk alias: input yang berhasil di-resolve dengan
     * keyakinan tinggi disimpan sebagai alias baru, sehingga resolve
     * berikutnya untuk ejaan yang sama langsung exact match.
     */
    private function learnAlias(EmployeeDefault $employee, string $rawAlias, string $method, float $confidence): void
    {
        $normalized = NameNormalizer::normalize($rawAlias);

        if ($normalized === '' || mb_strlen($normalized) < 3) {
            return;
        }

        $trustable = in_array($method, ['manual', 'name_exact', 'historical'], true)
            || ($method === 'fuzzy' && $confidence >= self::LEARN_ALIAS_THRESHOLD);

        if (! $trustable) {
            return;
        }

        EmployeeAlias::firstOrCreate(
            ['employee_default_id' => $employee->id, 'alias' => $normalized],
            ['source' => 'learned']
        );
    }
}
