<?php

use App\Services\AI\Resolvers\IntentResolver;
use App\Services\AI\Resolvers\KeywordMapper;
use App\Services\AI\Resolvers\ModuleResolver;

uses(Tests\TestCase::class);

it('mengklasifikasikan intent agregasi', function () {
    $intent = app(IntentResolver::class)->resolve('Berapa total pengeluaran BBM bulan ini?');

    expect($intent['intent'])->toBe('aggregation')
        ->and($intent['confidence'])->toBeGreaterThanOrEqual(0.7);
});

it('mengklasifikasikan intent detail', function () {
    $intent = app(IntentResolver::class)->resolve('Tampilkan 10 transaksi BBM terbesar');

    expect($intent['intent'])->toBe('detail');
});

it('mengklasifikasikan intent listing kendaraan', function () {
    $intent = app(IntentResolver::class)->resolve('Daftar kendaraan aktif');

    expect($intent['intent'])->toBe('listing');
});

it('mengklasifikasikan intent lookup harga', function () {
    $intent = app(IntentResolver::class)->resolve('Berapa harga pertamax sekarang?');

    expect($intent['intent'])->toBe('lookup');
});

it('mengklasifikasikan intent comparison dan trend', function () {
    expect(app(IntentResolver::class)->resolve('Perbandingan pengeluaran bulan ini dan lalu')['intent'])->toBe('comparison');
    expect(app(IntentResolver::class)->resolve('Tren pengeluaran per bulan')['intent'])->toBe('trend');
});

it('query tanpa keyword jelas menjadi unknown', function () {
    $intent = app(IntentResolver::class)->resolve('Halo apa kabar');

    expect($intent['intent'])->toBe('unknown');
});

it('memetakan kata kunci BBM ke measure bill_amounts', function () {
    $mapper = app(KeywordMapper::class);

    expect($mapper->measure('Berapa pengeluaran BBM bulan ini?', 'permohonan_bbm'))->toBe('bill_amounts')
        ->and($mapper->measure('Jumlah transaksi BBM', 'permohonan_bbm'))->toBe('transaction_count')
        ->and($mapper->measure('Harga pertamax', 'gasoline'))->toBe('price_per_liter');
});

it('memetakan kata kunci ke fungsi agregasi', function () {
    $mapper = app(KeywordMapper::class);

    expect($mapper->aggregation('Berapa total pengeluaran?'))->toBe('SUM')
        ->and($mapper->aggregation('Berapa rata-rata pengeluaran?'))->toBe('AVG')
        ->and($mapper->aggregation('Berapa jumlah transaksi?'))->toBe('COUNT')
        ->and($mapper->aggregation('Transaksi tertinggi'))->toBe('MAX');
});

it('mengenali alias BBM dan jenis bahan bakar', function () {
    $mapper = app(KeywordMapper::class);

    expect($mapper->gasolineType('pengeluaran bahan bakar'))->toBeNull()
        ->and($mapper->gasolineType('pengeluaran pertamax'))->toBe('Pertamax')
        ->and($mapper->gasolineType('harga pertamina dex'))->toBe('Pertamina Dex');
});

it('mengenali status', function () {
    $mapper = app(KeywordMapper::class);

    expect($mapper->status('transaksi yang ditolak'))->toBe('rejected')
        ->and($mapper->status('permohonan selesai'))->toBe('completed')
        ->and($mapper->status('yang masih menunggu'))->toBe('pending');
});

it('resolver module memilih permohonan_bbm untuk pengeluaran bbm', function () {
    $resolver = app(ModuleResolver::class);
    $result = $resolver->resolve('Berapa pengeluaran BBM bulan ini?');

    expect($result)->not->toBeNull()
        ->and($result['module'])->toBe('permohonan_bbm')
        ->and($result['confidence'])->toBeGreaterThanOrEqual(0.7);
});

it('resolver module memilih vehicle untuk kendaraan aktif', function () {
    $resolver = app(ModuleResolver::class);
    $result = $resolver->resolve('Berapa jumlah kendaraan aktif?');

    expect($result['module'])->toBe('vehicle');
});

it('resolver module memilih gasoline untuk harga pertamax', function () {
    $resolver = app(ModuleResolver::class);
    $result = $resolver->resolve('Berapa harga pertamax sekarang?');

    expect($result['module'])->toBe('gasoline');
});

it('resolver module memilih driver untuk statistik driver', function () {
    $resolver = app(ModuleResolver::class);
    $result = $resolver->resolve('Siapa driver dengan transaksi terbanyak?');

    expect($result)->not->toBeNull()
        ->and($result['module'])->toBeIn(['driver', 'permohonan_bbm']);
});

it('resolver module tidak menebak saat confidence rendah', function () {
    $resolver = app(ModuleResolver::class);

    expect($resolver->resolve('Bagaimana cuaca hari ini?'))->toBeNull();
});

it('resolver module menggunakan konteks sesi untuk pertanyaan lanjutan', function () {
    $resolver = app(ModuleResolver::class);

    $result = $resolver->resolve('Kalau Pertamax saja?', ['module' => 'permohonan_bbm']);

    expect($result)->not->toBeNull()
        ->and($result['module'])->toBe('permohonan_bbm')
        ->and($result['reused'])->toBeTrue();
});
