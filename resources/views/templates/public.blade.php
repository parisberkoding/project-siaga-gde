<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>{{ $title ?? 'Lunch Attendance' }}</title>

  <!-- Favicons -->
  <link href="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" rel="icon">

  <!-- Google Fonts (dihapus: tipografi kini lokal lewat GDE UI Templates) -->

  <!-- Vendor CSS Files (NiceAdmin) -->
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('DashboardTemplates/assets/css/style.css') }}" rel="stylesheet">

  <!-- GDE UI Templates — design system Geodipa (dimuat terakhir agar menimpa NiceAdmin) -->
  @include('partials.gde-head')

  @stack('head')
</head>

{{-- Halaman publik: tanpa navbar, tanpa sidebar, tanpa dashboard. --}}
<body class="bg-light">

  <main class="container py-4" style="max-width: 480px;">
    @yield('content')
  </main>

  <script src="{{ asset('DashboardTemplates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

  @stack('scripts')

  @include('partials.gde-scripts')
</body>

</html>
