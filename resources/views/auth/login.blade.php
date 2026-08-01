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

<body class="auth-body">

    <div class="auth-scene">
        <div class="auth-shell">
            <section class="auth-left" aria-label="Informasi Aplikasi">
                <div class="auth-left-pattern"></div>

                <div class="brand-row">
                    <img src="{{ asset('img/logo.png') }}" alt="SMKN 8 Garut" class="brand-logo">
                    <span class="brand-name">SMKN 8 Garut</span>
                </div>

                <div class="illus-wrap" aria-hidden="true">
                    <div class="illus-board">
                        <div class="illus-clip"></div>
                        <div class="illus-line"></div>
                        <div class="illus-line"></div>
                        <div class="illus-line"></div>
                    </div>
                </div>

                <h1>Welcome!</h1>
                <p>
                    Get a real intranet on top of your school attendance environment,
                    with SMKN 8 Garut digital system.
                </p>

                <div class="slider-dot" aria-hidden="true">
                    <span class="active"></span>
                    <span></span>
                    <span></span>
                </div>
            </section>

            <section class="auth-right" aria-label="Form Login">
                <div class="auth-right-inner">
                    <h2>Silahkan Masuk</h2>

                    <p class="helper-text mb-4">Proses masuk akan memakan waktu kurang dari satu menit.</p>

                    <div class="version-text version-text-top">
                        <span class="version-label">Versi Aplikasi</span>
                        <strong>v{{ config('app_version.version') }}</strong>
                        <span class="version-separator">-</span>
                        <span>{{ config('app_version.developer') }}</span>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-line-wrap mb-3">
                            <input type="text" class="form-line @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email') }}" placeholder="Username" required autofocus>
                            <i class="fas fa-user form-icon"></i>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-line-wrap mb-4">
                            <input type="password" class="form-line @error('password') is-invalid @enderror"
                                id="password" name="password" placeholder="Password" required>
                            <button class="password-toggle" type="button" id="showPassword"
                                aria-label="Tampilkan password">
                                <i class="fas fa-eye" id="showPasswordIcon"></i>
                            </button>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="action-row">
                            <button class="btn login-btn" type="submit">Masuk</button>

                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label" for="remember">Ingat password</label>
                            </div>
                        </div>

                        @if (Route::has('password.request'))
                            <div class="text-center mt-3">
                                <a href="{{ route('password.request') }}" class="forgot-link">Lupa password?</a>
                            </div>
                        @endif
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.getElementById('showPassword').onclick = function() {

            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('showPasswordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        };
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
