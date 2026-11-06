{{--
  GDE UI TEMPLATES — kepala halaman
  Satu tempat untuk memuat seluruh aset design system GDE.

  Pemakaian di dalam <head>:
      @include('partials.gde-head')

  Urutan pemuatan WAJIB: Bootstrap → Bootstrap Icons → style.css (NiceAdmin)
  → token → base → komponen → shell → jembatan (compat).
  Jembatan harus paling akhir agar bisa menimpa gaya NiceAdmin.

  Parameter opsional:
      $gdeCompat (bool, default true) — sertakan lapisan jembatan NiceAdmin.
      $gdeApp    (bool, default true) — sertakan gaya shell aplikasi.
--}}
{{-- 1. Tipografi lokal (tanpa CDN) --}}
<link href="{{ asset('gde-ui-templates/fonts/poppins.css') }}" rel="stylesheet">

{{-- 2. Design system GDE (?v= waktu ubah file: cache browser langsung diperbarui saat CSS berubah) --}}
@php($gdeCss = fn ($file) => asset("gde-ui-templates/css/$file") . '?v=' . @filemtime(public_path("gde-ui-templates/css/$file")))
<link href="{{ $gdeCss('gde-tokens.css') }}" rel="stylesheet">
<link href="{{ $gdeCss('gde-base.css') }}" rel="stylesheet">
<link href="{{ $gdeCss('gde-components.css') }}" rel="stylesheet">

@if ($gdeApp ?? true)
  <link href="{{ $gdeCss('gde-app.css') }}" rel="stylesheet">
@endif

@if ($gdeCompat ?? true)
  {{-- Jembatan: menyeragamkan gaya Bootstrap 5 + NiceAdmin tanpa mengubah markup lama --}}
  <link href="{{ $gdeCss('gde-compat.css') }}" rel="stylesheet">
@endif
