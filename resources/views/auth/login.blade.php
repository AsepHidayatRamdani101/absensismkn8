<!DOCTYPE html>
<html lang="id" style="">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Sistem Absensi SMKN 8 Garut</title>
    <!-- Load Tailwind CSS v3 -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <style data-purpose="typography">
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <style data-purpose="custom-layout">
        /* Custom background color to match the reference image */
        .bg-brand-blue {
            background-color: #407BFF;
        }

        .text-brand-blue {
            color: #407BFF;
        }

        .border-brand-blue {
            border-color: #407BFF;
        }

        /* Shape for the left panel similar to the reference */
        .left-panel-shape {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .panel-content-balance {
            width: 100%;
            max-width: 460px;
            margin-inline: auto;
        }

        @media (max-width: 1336px) and (max-height: 768px) {
            [data-purpose="login-wrapper"] {
                width: 100vw;
                max-width: 100vw;
                min-height: 100dvh !important;
                max-height: 100dvh;
                border-radius: 0;
                box-shadow: none;
            }

            [data-purpose="left-brand-panel"] {
                padding: 1.25rem;
            }

            [data-purpose="left-brand-panel"] h1 {
                font-size: 1.7rem;
                margin-bottom: 0.35rem;
            }

            [data-purpose="left-brand-panel"] p {
                font-size: 0.95rem;
                line-height: 1.35rem;
            }

            [data-purpose="right-form-panel"] {
                padding: 1.25rem 2rem;
            }

            [data-purpose="right-form-panel"] .mb-12 {
                margin-bottom: 1.4rem;
            }

            [data-purpose="right-form-panel"] .space-y-6> :not([hidden])~ :not([hidden]) {
                margin-top: 0.9rem;
            }

            .left-panel-shape {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center h-dvh overflow-hidden p-0">
    <!-- BEGIN: MainContainer -->
    <main
        class="bg-white w-screen max-w-none h-dvh flex flex-col md:flex-row shadow-2xl md:rounded-r-3xl md:rounded-l-none overflow-hidden"
        data-purpose="login-wrapper">
        <!-- BEGIN: SidebarPanel -->
        <section
            class="hidden md:flex flex-col flex-1 bg-brand-blue left-panel-shape text-white p-8 md:p-10 relative items-center justify-center overflow-hidden"
            data-purpose="left-brand-panel">
            <!-- Illustration Content -->
            <div class="relative z-10 flex flex-col items-center text-center panel-content-balance">
                <!-- School Logo -->
                <div class="mb-6">
                    <img alt="Logo SMKN 8 Garut" class="w-24 h-24 object-contain"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBVIS2FBm9moMekKBOWp5KIBTmwWHtKRDQY17RJ-FngfS5pM75YSf5shZSlJ9gYm-V_cD9tHJ2aaRrRehDjUE1UQ6_HNCaiLr6Irub4Pfu5eb6gSgYsemzF3AeCw7AhdznGrBdFreL6L01X1c1RR8bfFFwHmUDQvze6U0kLQoSIS2Ya9HHjnR9IxS7ufwO3_UAmZOyEnApPu-q68lD8AdSn5kAwJdLL9qQ4va0MlBh4ZuNp_zwOiqOWTLgHVSYxRv9ZhPY">
                </div>
                <!-- Branding Text -->
                <h1 class="text-3xl font-bold mb-2">SMKN 8 GARUT</h1>
                <p class="text-white/80 text-lg max-w-xs mx-auto">Setiap kehadiran adalah langkah awal menuju masa depan
                    yang gemilang.</p>

                <div class="mt-12 text-center opacity-70 text-sm w-full">
                    <div class="flex justify-center space-x-4 mb-4">
                        <span class="cursor-pointer">f</span>
                        <span class="cursor-pointer">in</span>
                        <span class="cursor-pointer">ig</span>
                    </div>
                    <p>© 2026 Asep Hidayat Ramdani, S.T</p>
                </div>
            </div>
            <!-- Decorative background circles -->
            <div class="absolute -top-20 -left-20 w-64 h-64 bg-white/5 rounded-full"></div>
            <div class="absolute top-1/4 -right-10 w-40 h-40 bg-white/5 rounded-full"></div>
        </section>
        <!-- END: SidebarPanel -->
        <!-- BEGIN: FormPanel -->
        <section class="flex-1 flex flex-col justify-center items-center p-6 md:p-10 lg:p-12 text-center"
            data-purpose="right-form-panel">
            <div class="panel-content-balance">
                <!-- Logo for Mobile (Hidden on Desktop) -->
                <div class="md:hidden flex justify-center mb-8">
                    <img alt="Logo SMKN 8 Garut" class="h-16"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDmfGPX8VK9HiJgTtB9jHF0qgaZKGyUuJ0sgIujz1XPHawg52hzbCsHlLoFaeTMCdhjM5FegooolRQYvxncKHkm5q92_zYgzLEiaFNVKWFdprh3BoDsZvwAT9-aeji0cMJTMeFuwfS0La6DEC4xMSQ_TV_PepVTl5HxbxMy4m8RpQPSkdUtOkPRRc-b_Oq_mnXoojSNrg39QA7RMIQR1NMQBDBteaONxBbsutXP80QDhlMD7znLa9mcZtOkb9vqk5rnOJI">
                </div>
                <!-- System Logo/Text -->
                <div class="text-center mb-12">

                    <h2 class="text-3xl font-bold text-gray-800">SMKN 8 GARUT</h2>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        role="alert">
                        Login gagal. Username atau kata sandi tidak sesuai.
                    </div>
                @endif

                <!-- Login Form -->
                <form action="{{ route('login') }}" class="space-y-6 text-left" method="POST">
                    @csrf
                    <!-- Username Field -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest"
                            for="login">Username/NISN/NIP</label>
                        <input
                            class="w-full border-0 border-b-2 border-gray-200 focus:ring-0 focus:border-brand-blue py-2 px-0 transition-colors text-gray-700"
                            id="login" name="email" value="{{ old('email') }}" placeholder="Username / NIP / NIS"
                            required="" type="text">
                    </div>
                    <!-- Password Field -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest"
                            for="password">Kata Sandi</label>
                        <div class="relative">
                            <input
                                class="w-full border-0 border-b-2 border-gray-200 focus:ring-0 focus:border-brand-blue py-2 pl-0 pr-10 transition-colors text-gray-700"
                                id="password" name="password" placeholder="••••••••••••" required=""
                                type="password">
                            <button type="button" id="togglePassword"
                                class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-500 hover:text-brand-blue"
                                aria-label="Tampilkan kata sandi">
                                <svg id="iconEye" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <path d="M1 12C1 12 5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <!-- Checkbox and Forgot Password -->
                    <div class="flex items-center justify-between py-2">
                        <div class="flex items-center">
                            <input class="h-4 w-4 text-brand-blue focus:ring-brand-blue border-gray-300 rounded"
                                id="remember_me" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                            <label class="ml-2 block text-sm text-gray-600" for="remember_me">
                                Ingat saya
                            </label>
                        </div>
                        <div class="text-sm">
                            <a class="font-medium text-brand-blue hover:text-blue-700 flex items-center" href="#">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Lupa kata sandi?
                            </a>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="flex items-center justify-start pt-4">

                        <button
                            class="w-full bg-brand-blue hover:bg-blue-600 text-white font-semibold py-3 px-8 rounded-full shadow-lg shadow-blue-200 transition-all transform hover:scale-[1.01] flex items-center justify-center"
                            type="submit">
                            Masuk
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="pt-2 text-xs text-gray-500 text-center">
                        Versi aplikasi v{{ config('app_version.version') }}
                    </div>
                </form>
            </div>
        </section>
        <!-- END: FormPanel -->
    </main>
    <!-- END: MainContainer -->

    <script>
        (function() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePassword');
            const iconEye = document.getElementById('iconEye');

            if (!passwordInput || !toggleBtn || !iconEye) {
                return;
            }

            toggleBtn.addEventListener('click', function() {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleBtn.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' :
                    'Tampilkan kata sandi');

                iconEye.innerHTML = isHidden ?
                    '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.8 21.8 0 0 1 5.06-6.94M9.9 4.24A10.58 10.58 0 0 1 12 4c7 0 11 8 11 8a21.86 21.86 0 0 1-3.18 4.74M1 1l22 22" />' :
                    '<path d="M1 12C1 12 5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z" /><circle cx="12" cy="12" r="3" />';
            });
        })();
    </script>
</body>

</html>
