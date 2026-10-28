{{-- Ringkasan jumlah permohonan untuk halaman approval admin. --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat-card label="Menunggu" :value="$stats['pending']" hint="Perlu diproses" icon="bi bi-hourglass-split" tone="warning" />
    <x-ui.stat-card label="Disetujui" :value="$stats['approved']" hint="Voucher sudah terbit" icon="bi bi-check-circle" tone="success" />
    <x-ui.stat-card label="Ditolak" :value="$stats['rejected']" hint="Tidak diproses" icon="bi bi-x-circle" tone="danger" />
    <x-ui.stat-card label="Total permohonan" :value="$stats['total']" hint="Semua status" icon="bi bi-list-check" />
</div>
