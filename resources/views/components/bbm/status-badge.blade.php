{{-- Badge status permohonan BBM: satu pemetaan warna & label untuk semua halaman. --}}
@props(['status'])

@php
    [$variant, $icon, $label] = match ($status) {
        'pending' => ['warning', 'bi-hourglass-split', 'Menunggu'],
        'pending_vehicle_approval' => ['warning', 'bi-truck', 'Menunggu Kendaraan'],
        'approved' => ['success', 'bi-check-circle', 'Disetujui'],
        'completed' => ['info', 'bi-check2-all', 'Selesai'],
        'rejected' => ['danger', 'bi-x-circle', 'Ditolak'],
        'cancelled', 'cancelled_by_driver' => ['muted', 'bi-slash-circle', 'Dibatalkan'],
        'expired' => ['danger', 'bi-clock-history', 'Kedaluwarsa'],
        default => ['muted', 'bi-dot', ucfirst(str_replace('_', ' ', (string) $status))],
    };
@endphp

<x-ui.badge :variant="$variant" {{ $attributes }}><i class="bi {{ $icon }}"></i> {{ $label }}</x-ui.badge>
