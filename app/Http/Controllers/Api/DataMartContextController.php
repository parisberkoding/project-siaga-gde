<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\DataMart\DataMartContextBuilder;
use Illuminate\Http\JsonResponse;

class DataMartContextController extends Controller
{
    public function __construct(
        protected DataMartContextBuilder $builder,
    ) {}

    /**
     * GET /api/data-mart/{unitId}
     *
     * Mengembalikan JSON konteks DM_<Unit> per modul (SPD, BBM, LPJ, LUNCH, VEHICLE, PARTS).
     * Response di-cache 1 hari.
     */
    public function show(int $unitId): JsonResponse
    {
        $data = $this->builder->buildCached($unitId);

        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * GET /api/data-mart/{unitId}/refresh
     *
     * Paksa rebuild + ignore cache.
     */
    public function refresh(int $unitId): JsonResponse
    {
        $data = $this->builder->buildForUnit($unitId);

        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}