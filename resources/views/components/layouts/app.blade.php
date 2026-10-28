{{--
  Layout aplikasi (Tailwind + komponen x-ui). Pemakaian:
      <x-layouts.app title="Riwayat Permohonan" :breadcrumbs="['Permohonan BBM' => route('requester-index'), 'Riwayat' => null]">
          ...
      </x-layouts.app>
  Script halaman: @push('scripts') @vite('resources/js/pages/...') @endpush
--}}
@props(['title' => null, 'breadcrumbs' => []])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' - SIAGA' : 'SIAGA' }}</title>
    <link rel="icon" href="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}">
    <link rel="stylesheet" href="{{ asset('gde-ui-templates/fonts/poppins.css') }}">
    <link rel="stylesheet" href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-svh">
    <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden [[data-sidebar-open]_&]:block"></div>

    <x-layouts.app.sidebar />

    <div class="flex min-h-svh flex-col lg:pl-64">
        <x-layouts.app.header :breadcrumbs="$breadcrumbs" />
        <x-layouts.app.gemini-banner />

        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
            <x-layouts.app.flash />
            {{ $slot }}
        </main>
    </div>

    <x-layouts.app.confirm-dialog />
    @stack('scripts')
</body>
</html>
