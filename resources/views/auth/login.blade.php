<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Sistem Absensi SMKN 8 Garut</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">

</head>

<body>

    <div class="container-fluid">

        <div class="row vh-100">

            <!-- LEFT -->

            <div class="col-lg-7 d-none d-lg-flex left-panel">

                <div class="overlay"></div>

                <div class="content">

                    <img src="{{ asset('img/logo.png') }}" width="120">

                    <h1 class="mt-4">

                        Sistem Absensi Digital

                    </h1>

                    <p>

                        SMKN 8 Garut

                    </p>

                    <p class="text-white-50" style="line-height:1.9;text-align:justify;">

                        Selamat datang di <strong>Sistem Absensi Digital SMKN 8 Garut</strong>,
                        sebuah platform yang dirancang untuk mendukung pengelolaan kehadiran
                        secara cepat, akurat, dan terintegrasi. Sistem ini menjadi pusat
                        pengelolaan data absensi siswa dan guru dengan memanfaatkan teknologi
                        digital untuk meningkatkan efisiensi administrasi sekolah.

                    </p>

                </div>

            </div>

            <!-- RIGHT -->

            <div class="col-lg-5 d-flex align-items-center justify-content-center">

                <div class="login-card">

                    <h2>

                        Login

                    </h2>

                    <p class="text-muted">

                        Silakan login menggunakan akun Anda.

                    </p>

                    <form method="POST" action="{{ route('login') }}">

                        @csrf

                        <div class="mb-3">

                            <label>Email</label>

                            <input type="text" class="form-control" name="email" value="{{ old('email') }}"
                                placeholder="Masukkan Email/NIP/NISN" required>

                        </div>

                        <div class="mb-3">

                            <label>Password</label>

                            <div class="input-group">

                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Masukkan Password" required>

                                <button class="btn btn-outline-secondary" type="button" id="showPassword">

                                    <i class="fas fa-eye"></i>

                                </button>

                            </div>

                        </div>

                        <div class="mb-3 form-check">

                            <input class="form-check-input" type="checkbox" name="remember">

                            <label>

                                Ingat Saya

                            </label>

                        </div>

                        <button class="btn btn-primary w-100">

                            <i class="fas fa-sign-in-alt"></i>

                            Login

                        </button>

                    </form>

                    @if (Route::has('password.request'))
                        <div class="text-center mt-3">

                            <a href="{{ route('password.request') }}">

                                Lupa Password?

                            </a>

                        </div>
                    @endif

                    <hr>

                    <div class="text-center small text-muted">

                        Version {{ config('app_version.version') }}

                        <br>

                        {{ config('app_version.developer') }}

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script>
        document.getElementById('showPassword').onclick = function() {

            let x = document.getElementById('password');

            if (x.type === "password") {

                x.type = "text";

            } else {

                x.type = "password";

            }

        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if ($errors->any())
        <script>
            Swal.fire({

                icon: 'error',

                title: 'Login Gagal',

                text: 'Email atau Password salah'

            });
        </script>
    @endif

</body>

</html>
