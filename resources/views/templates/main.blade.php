<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>{{ $title ?? "SIAGA" }}</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Favicons -->
  <link href="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts (dihapus: tipografi kini lokal lewat GDE UI Templates) -->

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>

      <!-- Make sure you put this AFTER Leaflet's CSS -->
 <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>


    {{-- <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <script src="{{ asset('DashboardTemplates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script> --}}


  <!-- Vendor CSS Files -->
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{asset('DashboardTemplates/assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  {{-- <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet"> --}}
  {{-- <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet"> --}}
  <link href="{{ asset('DashboardTemplates/assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

 <!-- Template Main CSS File -->
  <link href="{{ asset('DashboardTemplates/assets/css/style.css') }}" rel="stylesheet">

  <!-- GDE UI Templates — design system Geodipa (dimuat terakhir agar menimpa NiceAdmin) -->
  @include('partials.gde-head')

  {{-- Shell baru (sidebar + header seperti x-layouts.app); isi halaman tetap Bootstrap. --}}
  @vite(['resources/css/legacy-shell.css', 'resources/js/legacy-shell.js'])

</head>

@php($withSidebar = auth()->check() && ! ($hideSidebar ?? false))
<body class="min-h-svh lg:bg-sidebar-gradient">
    {{-- Pasang status sidebar tersembunyi sebelum render supaya tidak berkedip. --}}
    <script>try { if (localStorage.getItem('sidebar-collapsed') === '1') document.body.setAttribute('data-sidebar-collapsed', ''); } catch (e) {}</script>

  @if ($withSidebar)
    {{-- Tampil untuk SEMUA user terautentikasi, termasuk role custom (dynamic RBAC);
         isi menu disaring berdasarkan permission di partials/sidebar-menu. --}}
    <div data-shell>
      <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden [[data-sidebar-open]_&]:block"></div>
      <x-layouts.app.sidebar />
    </div>
  @endif

  <div aria-hidden="true" class="bg-sidebar-gradient fixed inset-x-0 top-0 z-30 hidden h-2 lg:block"></div>

  <div @class(['transition-[padding] lg:py-2 lg:pr-2', 'lg:pl-64 lg:[[data-sidebar-collapsed]_&]:pl-2' => $withSidebar, 'lg:pl-2' => ! $withSidebar])>
    <div class="flex min-h-svh flex-col bg-background lg:min-h-[calc(100svh-1rem)] lg:rounded-xl lg:shadow-xl lg:shadow-black/20">
      @if ($withSidebar)
        <div data-shell class="contents">
          <x-layouts.app.header />
          <x-layouts.app.gemini-banner />
        </div>
      @endif

      {{-- Utilitas shell tidak berlaku di dalam #main (lihat legacy-shell.css), jadi lebar diatur pembungkus ini. --}}
      <div class="mx-auto w-full max-w-screen-2xl flex-1">
        <main id="main" class="main">
          @yield('content')
        </main><!-- End #main -->
      </div>
    </div>
  </div>


  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('DashboardTemplates/assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('DashboardTemplates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('DashboardTemplates/assets/vendor/chart.js/chart.umd.js') }}"></script>
  <script src="{{ asset('DashboardTemplates/assets/vendor/echarts/echarts.min.js') }}"></script>
  {{-- <script src="assets/vendor/quill/quill.js"></script> --}}
  <script src="{{ asset('DashboardTemplates/assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
  {{-- <script src="assets/vendor/tinymce/tinymce.min.js"></script> --}}
  <script src="{{ asset('DashboardTemplates/assets/vendor/php-email-form/validate.js') }}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('DashboardTemplates/assets/js/main.js') }}"></script>

  {{-- sweet alert --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.23.0/dist/sweetalert2.all.min.js"
     integrity="sha384-ms4yaO2qHEUIRbq8CXAqtz3qhnO7nYo/xAWv23Zx/iX9tT+nvVZP66kSXwT/7a0J"
     crossorigin="anonymous"></script>


    <!-- jsPDF for PDF Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"
     integrity="sha384-JcnsjUPPylna1s1fvi1u12X5qjY5OL56iySh75FdtrwhO/SWXgMjoVqcKyIIWOLk"
     crossorigin="anonymous"></script>

    <!-- Epson ePOS SDK for Thermal Printer -->
    <script src="https://epson.github.io/ePOS_SDK/epos-dev.js"></script>

    <!-- Custom Thermal Printer Scripts -->
    {{-- <script src="{{ asset('js/thermal-printer.js') }}"></script> --}}

    {{-- Stack untuk script tambahan dari child views (mis. SIAGA) --}}
    @stack('scripts')

    {{-- GDE UI Templates — perilaku komponen gde-* --}}
    @include('partials.gde-scripts')

</body>

</html>
