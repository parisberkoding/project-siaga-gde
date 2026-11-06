{{-- Badge tipe pengeluaran LPJ (BBM, Tol, Portir, lainnya). --}}
@props(['tipe'])

<x-ui.badge :variant="match ($tipe) { 'BBM' => 'default', 'Tol' => 'info', 'Portir' => 'warning', default => 'secondary' }" {{ $attributes }}>{{ $tipe }}</x-ui.badge>
