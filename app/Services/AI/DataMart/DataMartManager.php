<?php

namespace App\Services\AI\DataMart;

use App\Services\AI\DataMart\Contracts\DataMartContract;
use App\Services\AI\DataMart\Modules\DriverDataMart;
use App\Services\AI\DataMart\Modules\GasolineDataMart;
use App\Services\AI\DataMart\Modules\PermohonanDataMart;
use App\Services\AI\DataMart\Modules\VehicleDataMart;
use InvalidArgumentException;
use RuntimeException;

/**
 * Mengelola lifecycle Data Mart:
 *   resolve module -> build -> cache pendek (TTL) -> discard.
 *
 * Data Mart TIDAK disimpan permanen; cache hanya jangka pendek (config TTL).
 */
class DataMartManager
{
    /** @var array<string, class-string<DataMartContract>> */
    protected array $registry = [
        'permohonan_bbm' => PermohonanDataMart::class,
        'vehicle' => VehicleDataMart::class,
        'driver' => DriverDataMart::class,
        'gasoline' => GasolineDataMart::class,
    ];

    /** @var array<string, DataMartContract> */
    protected array $instances = [];

    public function register(string $module, string $class): void
    {
        $this->registry[$module] = $class;
        unset($this->instances[$module]);
    }

    public function modules(): array
    {
        return array_keys($this->registry);
    }

    public function has(string $module): bool
    {
        return isset($this->registry[$module]);
    }

    /**
     * Resolve module ke instance DataMart.
     *
     * @throws RuntimeException Bila module tidak terdaftar.
     */
    public function resolve(string $module): DataMartContract
    {
        $key = strtolower(trim($module));

        if (! isset($this->registry[$key])) {
            throw new RuntimeException("Data mart module tidak dikenal: {$module}");
        }

        if (! isset($this->instances[$key])) {
            $this->instances[$key] = app()->make($this->registry[$key]);
        }

        return $this->instances[$key];
    }

    /**
     * Bangun skema Data Mart lengkap (records + summary + metadata).
     *
     * @param  array<string, mixed>  $options  Contoh: ['filters' => [...], 'limit' => int]
     * @return array<string, mixed>
     */
    public function build(string $module, array $options = []): array
    {
        $this->assertAllowedModule($module);

        $key = $this->cacheKey($module, $options);
        $ttl = (int) config('ai.datamart.cache_ttl', 300);

        if ($ttl > 0) {
            $cached = cache()->get($key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $dataMart = $this->resolve($module);
        $data = $dataMart->build($options);

        if ($ttl > 0) {
            cache()->put($key, $data, $ttl);
        }

        return $data;
    }

    /**
     * Bangun raw records tanpa metadata (untuk operasi internal ringan).
     *
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function records(string $module, array $options = []): array
    {
        $this->assertAllowedModule($module);

        return $this->resolve($module)->records($options);
    }

    /**
     * Hapus semua instance & cache Data Mart (mis. di akhir request).
     */
    public function flush(): void
    {
        $this->instances = [];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function cacheKey(string $module, array $options): string
    {
        return 'ai.datamart.'.$module.'.'.hash('xxh128', json_encode($options));
    }

    protected function assertAllowedModule(string $module): void
    {
        if (! $this->has($module)) {
            throw new InvalidArgumentException("Module Data Mart tidak diizinkan: {$module}");
        }
    }
}
