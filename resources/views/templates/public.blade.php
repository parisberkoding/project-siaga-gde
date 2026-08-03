<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>{{ $title ?? 'Lunch Attendance' }}</title>

  <!-- Favicons -->
  <link href="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" rel="icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files (NiceAdmin) -->
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('DashboardTemplates/assets/css/style.css') }}" rel="stylesheet">

  @stack('head')
</head>

{{-- Halaman publik: tanpa navbar, tanpa sidebar, tanpa dashboard. --}}
<body class="bg-light">

  <main class="container py-4" style="max-width: 480px;">
    @yield('content')
  </main>

  <script src="{{ asset('DashboardTemplates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

  @stack('scripts')
</body>

</html>
