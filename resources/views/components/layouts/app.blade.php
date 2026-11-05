{{--
  Layout aplikasi (Tailwind + komponen x-ui). Pemakaian:
      <x-layouts.app title="Riwayat Permohonan" :breadcrumbs="['BBM Operasional' => route('requester-index'), 'Riwayat' => null]">
          ...
      </x-layouts.app>
  Script halaman: @push('scripts') @vite('resources/js/pages/...') @endpush

  Pola shadcn Sidebar variant="inset": di layar lebar sidebar berwarna jadi bingkai,
  konten berada di panel membulat yang menggulir sendiri.
  :sidebar="false" = tanpa sidebar & header bawaan (halaman All Modules); isi slot `topbar` sebagai penggantinya.
--}}
@props(['title' => null, 'breadcrumbs' => [], 'sidebar' => true])

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
<body class="min-h-svh lg:bg-sidebar-gradient">
    {{-- Pasang status sidebar tersembunyi sebelum render supaya tidak berkedip. --}}
    <script>try { if (localStorage.getItem('sidebar-collapsed') === '1') document.body.setAttribute('data-sidebar-collapsed', ''); } catch (e) {}</script>
    @if ($sidebar)
        <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden [[data-sidebar-open]_&]:block"></div>

        <x-layouts.app.sidebar />
    @endif

    {{-- Halaman yang menggulir (scrollbar di tepi jendela); bingkai biru tetap diam. Strip biru di atas
         menutupi konten yang lewat di celah atas saat header menempel. --}}
    <div aria-hidden="true" class="bg-sidebar-gradient fixed inset-x-0 top-0 z-30 hidden h-2 lg:block"></div>

    <div @class(['transition-[padding] lg:py-2 lg:pr-2', 'lg:pl-64 lg:[[data-sidebar-collapsed]_&]:pl-2' => $sidebar, 'lg:pl-2' => ! $sidebar])>
        <div data-slot="sidebar-inset" class="flex min-h-svh flex-col bg-background lg:min-h-[calc(100svh-1rem)] lg:rounded-xl lg:shadow-xl lg:shadow-black/20">
            @if ($sidebar)
                <x-layouts.app.header :breadcrumbs="$breadcrumbs" />
            @else
                {{ $topbar ?? '' }}
            @endif
            <x-layouts.app.gemini-banner />

            <main class="mx-auto flex w-full max-w-screen-2xl flex-1 flex-col gap-6 p-4 md:p-6">
                <x-layouts.app.flash />
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-layouts.app.confirm-dialog />
    @stack('scripts')
</body>
</html>
