<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <link href="{{ asset('DashboardTemplates/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('DashboardTemplates/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">

  <!-- GDE UI Templates — design system Geodipa (dimuat setelah NiceAdmin agar menimpanya) -->
  <link href="{{ asset('gde-ui-templates/fonts/poppins.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-tokens.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-base.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-components.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-app.css') }}" rel="stylesheet">
  <link href="{{ asset('gde-ui-templates/css/gde-compat.css') }}" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            max-width: 700px;
            width: 100%;
        }

        .error-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            padding: 60px 50px;
            text-align: center;
        }

        .error-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: #fff5f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-icon i {
            font-size: 60px;
            color: #C4142A;
        }

        .error-code {
            font-size: 72px;
            font-weight: 700;
            color: #16202E;
            margin-bottom: 10px;
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 28px;
            font-weight: 600;
            color: #16202E;
            margin-bottom: 15px;
        }

        .error-description {
            font-size: 16px;
            color: #64748B;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .divider {
            width: 60px;
            height: 3px;
            background: #ED1B2F;
            margin: 25px auto;
            border-radius: 2px;
        }

        .btn-primary-custom {
            background: #16202E;
            color: white;
            border: none;
            padding: 14px 35px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            background: #000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .info-section {
            background: #F8FAFC;
            border-left: 4px solid #0B50A1;
            padding: 20px 25px;
            margin-top: 35px;
            border-radius: 6px;
            text-align: left;
        }

        .info-section .info-title {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-section .info-item {
            font-size: 14px;
            color: #64748B;
            margin: 6px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-section .info-label {
            font-weight: 500;
            color: #495057;
        }

        .info-section .info-value {
            font-weight: 600;
            color: #16202E;
        }

        @media (max-width: 768px) {
            .error-card {
                padding: 40px 30px;
            }

            .error-code {
                font-size: 56px;
            }

            .error-title {
                font-size: 22px;
            }

            .error-icon {
                width: 100px;
                height: 100px;
            }

            .error-icon i {
                font-size: 50px;
            }
        }

        @media (max-width: 576px) {
            .error-card {
                padding: 35px 20px;
            }

            .info-section .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <h1 class="error-code">403</h1>
            <div class="divider"></div>
            <h2 class="error-title">Akses Ditolak</h2>

            <p class="error-description">
                Maaf, Anda tidak memiliki hak akses untuk mengunjungi halaman ini.
                Silakan hubungi administrator sistem jika Anda merasa ini adalah kesalahan.
            </p>

            <a href="javascript:history.back()" class="btn-primary-custom">
                <i class="bi bi-arrow-left"></i>
                Kembali ke Halaman Sebelumnya
            </a>

            @auth
            <div class="info-section">
                <div class="info-title">
                    <i class="bi bi-info-circle-fill"></i>
                    Informasi Akun
                </div>
                <div class="info-item">
                    <span class="info-label">Pengguna:</span>
                    <span class="info-value">{{ Auth::user()->nama_lengkap }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Role:</span>
                    <span class="info-value">{{ ucfirst(Auth::user()->role) }}</span>
                </div>
            </div>
            @endauth
        </div>
    </div>
</body>
</html>
