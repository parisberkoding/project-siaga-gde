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

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>

      <!-- Make sure you put this AFTER Leaflet's CSS -->
 <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>


    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script> --}}


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

  <!-- Custom CSS untuk role lainnya (other_user, manager) yang baru -->

</head>

</head>

<body>

  
  @if (Auth::user()->role === 'superadmin'
  || Auth::user()->role === 'requester'
  || Auth::user()->role === 'driver'
  || Auth::user()->role === 'admin'
  || Auth::user()->role === 'non_ga_staff'
  || Auth::user()->role === 'manager'
  || Auth::user()->role === 'dispatcher'
  )
    @include('partials.navbar')
    @include('partials.sidebar')
    @else
  
    @endif


    <main id="main" class="main">
      @yield('content')
    </main><!-- End #main -->


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

</body>

</html>
