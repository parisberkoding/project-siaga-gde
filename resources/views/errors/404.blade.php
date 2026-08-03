<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
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
            background: #f0f7ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-icon i {
            font-size: 60px;
            color: #0d6efd;
        }

        .error-code {
            font-size: 72px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 10px;
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 28px;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 15px;
        }

        .error-description {
            font-size: 16px;
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .divider {
            width: 60px;
            height: 3px;
            background: #0d6efd;
            margin: 25px auto;
            border-radius: 2px;
        }

        .btn-group-custom {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: #212529;
            color: white;
            border: none;
            padding: 14px 30px;
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

        .btn-secondary-custom {
            background: white;
            color: #212529;
            border: 2px solid #dee2e6;
            padding: 12px 30px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary-custom:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            transform: translateY(-2px);
            color: #212529;
        }

        .suggestions {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-top: 35px;
            text-align: left;
        }

        .suggestions-title {
            font-size: 15px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .suggestions ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .suggestions li {
            padding: 10px 0;
            color: #6c757d;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .suggestions li i {
            color: #0d6efd;
            margin-top: 2px;
            flex-shrink: 0;
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

            .btn-group-custom {
                flex-direction: column;
            }

            .btn-primary-custom,
            .btn-secondary-custom {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .error-card {
                padding: 35px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i class="bi bi-search"></i>
            </div>

            <h1 class="error-code">404</h1>
            <div class="divider"></div>
            <h2 class="error-title">Halaman Tidak Ditemukan</h2>

            <p class="error-description">
                Halaman yang Anda cari tidak dapat ditemukan. Kemungkinan halaman telah dipindahkan atau URL yang Anda masukkan salah.
            </p>

            <div class="btn-group-custom">
                <a href="javascript:history.back()" class="btn-secondary-custom">
                    <i class="bi bi-arrow-left"></i>
                    Kembali
                </a>
            </div>

            <div class="suggestions">
                <div class="suggestions-title">
                    <i class="bi bi-lightbulb"></i>
                    Saran
                </div>
                <ul>
                    <li>
                        <i class="bi bi-check2"></i>
                        <span>Periksa kembali URL yang Anda masukkan</span>
                    </li>
                    <li>
                        <i class="bi bi-check2"></i>
                        <span>Gunakan tombol kembali atau navigasi menu utama</span>
                    </li>
                    <li>
                        <i class="bi bi-check2"></i>
                        <span>Hubungi administrator jika masalah berlanjut</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
