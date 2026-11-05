{{-- Ringkasan jumlah permohonan untuk halaman approval admin. HP: 2 kolom ringkas (x-ui.stat-card short: ikon di atas label). --}}
<div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
    <x-ui.stat-card label="Menunggu" :value="$stats['pending']" hint="Perlu diproses" icon="bi bi-hourglass-split" tone="warning" short />
    <x-ui.stat-card label="Disetujui" :value="$stats['approved']" hint="Voucher sudah terbit" icon="bi bi-check-circle" tone="success" short />
    <x-ui.stat-card label="Ditolak" :value="$stats['rejected']" hint="Tidak diproses" icon="bi bi-x-circle" tone="danger" short />
    <x-ui.stat-card label="Total permohonan" :value="$stats['total']" hint="Semua status" icon="bi bi-list-check" short />
</div>
