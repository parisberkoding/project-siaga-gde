{{-- Badge status surat tugas, perpanjangan, dan LPJ: satu pemetaan warna & label untuk modul SPD. --}}
@props(['status'])

@php
    [$variant, $icon, $label] = match ($status) {
        'pending' => ['warning', 'bi-hourglass-split', 'Menunggu'],
        'approved' => ['success', 'bi-check-circle', 'Disetujui'],
        'completed' => ['info', 'bi-check2-all', 'Selesai'],
        'rejected' => ['danger', 'bi-x-circle', 'Ditolak'],
        'cancelled' => ['muted', 'bi-slash-circle', 'Dibatalkan'],
        'draft' => ['muted', 'bi-pencil', 'Draft'],
        default => ['muted', 'bi-dot', ucfirst(str_replace('_', ' ', (string) $status))],
    };
@endphp

<x-ui.badge :variant="$variant" {{ $attributes }}><i class="bi {{ $icon }}"></i> {{ $label }}</x-ui.badge>
