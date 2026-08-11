<?php

use App\Models\MasterGasoline;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\AI\DataMartChatPipeline;
use App\Services\AI\Query\DateRangeExtractor;
use App\Services\AI\Query\SemanticQueryBuilder;

uses(Tests\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['ai.datamart.cache_ttl' => 0]);
});

it('mengekstrak rentang tanggal bulan ini', function () {
    $range = app(DateRangeExtractor::class)->extract('Berapa pengeluaran BBM bulan ini?');

    $now = Carbon\Carbon::today();

    expect($range['from'])->toBe($now->copy()->startOfMonth()->format('Y-m-d'))
        ->and($range['to'])->toBe($now->format('Y-m-d'));
});

it('mengekstrak rentang tanggal eksplisit 10 sampai 15 Juli', function () {
    $range = app(DateRangeExtractor::class)->extract('Tampilkan transaksi tanggal 10 sampai 15 Juli');

    expect($range['from'])->toBe(date('Y').'-07-10')
        ->and($range['to'])->toBe(date('Y').'-07-15');
});

it('mengekstrak rentang tanggal 10-15 agustus 2026', function () {
    $range = app(DateRangeExtractor::class)->extract('transaksi 10-15 agustus 2026');

    expect($range['from'])->toBe('2026-08-10')
        ->and($range['to'])->toBe('2026-08-15');
});

it('mengekstrak bulan Juli saja', function () {
    $range = app(DateRangeExtractor::class)->extract('pengeluaran bulan Juli');

    expect($range['from'])->toBe(date('Y').'-07-01')
        ->and($range['to'])->toBe(date('Y').'-07-31');
});

it('mengembalikan null saat tidak ada konteks tanggal', function () {
    expect(app(DateRangeExtractor::class)->extract('Daftar kendaraan aktif'))->toBeNull();
});

it('menyusun semantic query untuk agregasi pengeluaran', function () {
    $question = 'Berapa total pengeluaran BBM bulan ini?';
    $intent = app(App\Services\AI\Resolvers\IntentResolver::class)->resolve($question);

    $query = app(SemanticQueryBuilder::class)->build(
        $question,
        'permohonan_bbm',
        $intent
    );

    expect($query->module)->toBe('permohonan_bbm')
        ->and($query->intent)->toBe('aggregation')
        ->and($query->aggregation)->toBe('SUM')
        ->and($query->measure)->toBe('bill_amounts')
        ->and($query->filters)->toHaveKey('date_from')
        ->and($query->filters)->toHaveKey('date_to');
});

it('menyusun semantic query untuk detail top transaksi', function () {
    $question = 'Tampilkan 10 transaksi BBM terbesar';
    $intent = app(App\Services\AI\Resolvers\IntentResolver::class)->resolve($question);

    $query = app(SemanticQueryBuilder::class)->build($question, 'permohonan_bbm', $intent);

    expect($query->intent)->toBe('detail')
        ->and($query->limit)->toBe(10)
        ->and($query->sortBy)->toBe('bill_amounts')
        ->and($query->sortDir)->toBe('desc')
        ->and($query->aggregation)->toBeNull();
});

it('mengekstrak filter jenis BBM, site, dan status', function () {
    $site = App\Models\Site::where('code', 'PMU')->firstOrFail();
    $question = 'Tampilkan transaksi Pertamax PMU yang selesai';

    $intent = app(App\Services\AI\Resolvers\IntentResolver::class)->resolve($question);
    $query = app(SemanticQueryBuilder::class)->build($question, 'permohonan_bbm', $intent);

    expect($query->filters['gasoline_type'])->toBe('Pertamax')
        ->and($query->filters['site'])->toBe('PMU')
        ->and($query->filters['status'])->toBe('completed');
});

it('menggabungkan konteks sesi untuk pertanyaan lanjutan', function () {
    $previous = [
        'module' => 'permohonan_bbm',
        'measure' => 'bill_amounts',
        'aggregation' => 'SUM',
        'filters' => [
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ],
    ];

    $intent = ['intent' => 'aggregation', 'confidence' => 0.9];
    $query = app(SemanticQueryBuilder::class)->build('Kalau Pertamax saja?', 'permohonan_bbm', $intent, $previous);

    expect($query->aggregation)->toBe('SUM')
        ->and($query->measure)->toBe('bill_amounts')
        ->and($query->filters['date_from'])->toBe('2026-08-01')
        ->and($query->filters['gasoline_type'])->toBe('Pertamax');
});

it('pipeline menjalankan agregasi deterministik untuk pengeluaran', function () {
    $requester = User::factory()->create(['nama_lengkap' => 'Dian']);
    Permohonan::factory()->count(3)->create([
        'requester_id' => $requester->id,
        'status' => 'completed',
        'bill_amounts' => 200000,
        'request_date' => Carbon\Carbon::now()->format('Y-m-d H:i:s'),
    ]);

    $result = app(DataMartChatPipeline::class)->run('Berapa total pengeluaran BBM bulan ini?');

    expect($result['resolved'])->toBeTrue()
        ->and($result['metadata']['module'])->toBe('permohonan_bbm')
        ->and($result['metadata']['intent'])->toBe('aggregation')
        ->and($result['metadata']['records_matched'])->toBe(3)
        ->and($result['metadata']['records_injected'])->toBe(0)
        ->and($result['result']['aggregation']['value'])->toBe(600000.0)
        ->and($result['context'])->toContain('VERIFIED RESULT')
        ->and($result['context'])->toContain('Rp 600.000');
});

it('pipeline mengembalikan top-k untuk deep drill', function () {
    $requester = User::factory()->create(['nama_lengkap' => 'Eka']);
    Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'bill_amounts' => 100000,
        'status' => 'approved',
    ]);
    Permohonan::factory()->create([
        'requester_id' => $requester->id,
        'bill_amounts' => 900000,
        'status' => 'approved',
    ]);

    $result = app(DataMartChatPipeline::class)->run('Tampilkan 1 transaksi BBM terbesar');

    expect($result['resolved'])->toBeTrue()
        ->and($result['metadata']['intent'])->toBe('detail')
        ->and($result['metadata']['records_injected'])->toBe(1)
        ->and($result['result']['records'][0]['bill_amounts'])->toBe(900000.0)
        ->and($result['context'])->toContain('TRUNCATED: true');
});

it('pipeline tidak memutuskan saat module tidak dikenal', function () {
    $result = app(DataMartChatPipeline::class)->run('Bagaimana cuaca hari ini?');

    expect($result['resolved'])->toBeFalse()
        ->and($result['reason'])->toBe('module_not_resolved');
});

it('pipeline melaporkan data kosong secara jujur', function () {
    $result = app(DataMartChatPipeline::class)->run('Berapa total pengeluaran BBM bulan ini?');

    expect($result['resolved'])->toBeTrue()
        ->and($result['metadata']['records_matched'])->toBe(0)
        ->and($result['context'])->toContain('VERIFIED RESULT')
        ->and($result['context'])->toContain('RECORD COUNT: 0');
});

it('pipeline gasoline menghasilkan daftar harga untuk lookup', function () {
    MasterGasoline::create([
        'province' => 'Prov. Jawa Barat',
        'province_slug' => 'jawa-barat',
        'fuel_type' => 'PERTAMAX',
        'price_per_liter' => 12000,
        'price_date' => now(),
    ]);

    $result = app(DataMartChatPipeline::class)->run('Berapa harga pertamax sekarang?');

    expect($result['resolved'])->toBeTrue()
        ->and($result['metadata']['module'])->toBe('gasoline')
        ->and($result['metadata']['intent'])->toBe('lookup')
        ->and($result['metadata']['records_matched'])->toBe(1);
});
