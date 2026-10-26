<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>{{ $title }}</title>
  <meta content="" name="description">

  <!-- Favicons -->
  <link href="{{ asset('DashboardTemplates/assets/img/geodipa-logo-croped.png') }}" rel="icon">

  <!-- Google Fonts -->


  <!-- Vendor CSS Files -->
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{asset('DashboardTemplates/assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  <link href="{{ asset('DashboardTemplates/assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('DashboardTemplates/assets/css/style.css') }}" rel="stylesheet">

  <!-- GDE UI Templates — design system Geodipa (dimuat setelah NiceAdmin agar menimpanya) -->
  <link href="{{ asset('gde-ui-templates/fonts/poppins.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-tokens.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-base.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-components.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-app.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-compat.css') }}" rel="stylesheet">
</head>

<body>

  <main>
    <div class="container">

      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                <div class="d-flex justify-content-center py-4">

                </div><!-- End Logo -->

                <!-- Alert Messages untuk error umum dan success -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <!-- End Alert Messages -->

                <div class="card mb-3">
                    <div class="card-body">

                  <div class="pt-4 pb-2">
                    <img src="{{ asset('DashboardTemplates/assets/img/geodipa-logo.png') }}" class="d-block mx-auto" style="max-width: 35%;min-width:18%;">
                    <h5 class="card-title text-center pb-0 fs-4">Siaga</h5>
                    <p class="text-center small">PT. Geo Dipa Energi (Persero) Unit Patuha</p>
                </div>

                  <form action="{{ route('login-process') }}" class="row g-3" method="POST">
                    @csrf
                    <div class="col-12">
                      <div class="input-group has-validation">
                        <span class="input-group-text" id="inputGroupPrepend"><i class="bi bi-person"></i></span>
                        <input type="text"
                               name="username"
                               class="form-control @error('username') is-invalid @enderror"
                               id="yourUsername"
                               placeholder="Masukkan Username"
                               value="{{ old('username') }}">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </div>
                    </div>

                    <div class="col-12">
                        <div class="input-group has-validation">
                            <span class="input-group-text" id="inputGroupPrepend">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password"
                                   name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="yourPassword"
                                   placeholder="Masukkan Password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showPassword" onclick="togglePassword()">
                            <label class="form-check-label" for="showPassword">
                                Tampilkan Password
                            </label>
                        </div>
                    </div>


                    <div class="col-12">
                      <button class="btn btn-primary w-100" type="submit">Login</button>
                    </div>
                    <div class="col-12 text-center mt-4">
                      <p class="small mb-0 text-secondary">Tidak Memiliki Hak Akses? Hubungi Tim IT untuk mendapatkan Hak Akses</p>
                    </div>
                    <div class="col-12 text-center mt-4">
                      <p style="font-size: 0.9rem;" class="mb-0 text-secondary"><a href="{{route('welcome')}}"> Kembali ke Halaman Utama </a></p>
                    </div>
                  </form>

                </div>
              </div>

              <div class="credits">
                {{-- Developed by <a href="https://github.com/FarisIftikharAlfarisi"><span>Faris Iftikhar Alfarisi</span></a> --}}
              </div>

            </div>
          </div>
        </div>

      </section>

    </div>
  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

   <!-- Vendor JS Files -->
   <script src="{{ asset('DashboardTemplates/assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
   <script src="{{ asset('DashboardTemplates/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
   <script src="{{ asset('DashboardTemplates/assets/vendor/chart.js/chart.umd.js') }}"></script>
   <script src="{{ asset('DashboardTemplates/assets/vendor/echarts/echarts.min.js') }}"></script>
   <script src="{{ asset('DashboardTemplates/assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
   <script src="{{ asset('DashboardTemplates/assets/vendor/php-email-form/validate.js') }}"></script>

   <!-- Template Main JS File -->
   <script src="{{ asset('DashboardTemplates/assets/js/main.js') }}"></script>
   <script>
    function togglePassword() {
        var passwordField = document.getElementById("yourPassword");
        var checkbox = document.getElementById("showPassword");

        if (checkbox.checked) {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
    </script>

</body>

</html>
