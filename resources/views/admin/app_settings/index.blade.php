@extends('adminlte::page')

@section('title', 'Pengaturan Sistem')

@section('css')
    <style>
        .setting-header-card {
            border-radius: .75rem;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .07);
            padding: 1.1rem 1.4rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .setting-header-card .header-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: .15rem;
        }

        .setting-header-card .header-sub {
            font-size: .85rem;
            color: #6c757d;
            margin: 0;
        }

        .cache-card {
            border-radius: .75rem;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .07);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }

        .cache-card .cache-card-header {
            padding: .85rem 1.1rem .6rem;
            border-bottom: 1px solid #f1f3f5;
        }

        .cache-card .cache-card-header .card-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: .5rem;
            font-size: .9rem;
        }

        .cache-card .cache-card-header h6 {
            font-size: .95rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
            display: inline;
            vertical-align: middle;
        }

        .cache-card .cache-card-header .card-path {
            display: block;
            font-size: .75rem;
            color: #9ca3af;
            margin-top: .15rem;
            padding-left: 2.1rem;
        }

        .cache-card .cache-card-body {
            padding: .9rem 1.1rem;
        }

        .stat-boxes {
            display: flex;
            gap: .65rem;
            margin-bottom: .85rem;
        }

        .stat-box {
            flex: 1;
            border: 1px solid #e9ecef;
            border-radius: .55rem;
            padding: .6rem .75rem;
            text-align: center;
        }

        .stat-box .stat-val {
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-box .stat-lbl {
            font-size: .72rem;
            color: #6c757d;
            margin-top: .1rem;
        }

        .cache-progress-wrap {
            margin-bottom: .55rem;
        }

        .cache-progress-wrap .progress {
            height: 6px;
            border-radius: 99px;
        }

        .cache-progress-label {
            font-size: .72rem;
            color: #9ca3af;
            margin-top: .3rem;
        }

        .cache-card-footer {
            padding: 0 1.1rem 1rem;
        }

        .cache-card-footer .btn {
            width: 100%;
            font-size: .82rem;
            border-radius: .5rem;
            padding: .45rem 1rem;
        }

        .config-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .65rem 1.1rem;
            border-bottom: 1px solid #f1f3f5;
        }

        .config-row:last-of-type {
            border-bottom: 0;
        }

        .config-row .cfg-title {
            font-size: .875rem;
            font-weight: 600;
            color: #212529;
        }

        .config-row .cfg-path {
            font-size: .72rem;
            color: #9ca3af;
        }

        .config-row .cfg-size {
            font-size: .75rem;
            color: #6c757d;
            text-align: right;
        }

        .badge-aktif {
            background: #28a745;
            color: #fff;
            font-size: .68rem;
            padding: .22rem .5rem;
            border-radius: .3rem;
            font-weight: 600;
        }

        .badge-nonaktif {
            background: #6c757d;
            color: #fff;
            font-size: .68rem;
            padding: .22rem .5rem;
            border-radius: .3rem;
            font-weight: 600;
        }

        .config-actions {
            padding: .75rem 1.1rem;
            display: flex;
            gap: .5rem;
        }

        .config-actions .btn {
            flex: 1;
            font-size: .8rem;
            border-radius: .45rem;
        }

        .tips-card {
            border-radius: .75rem;
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 1rem 1.25rem;
            margin-top: .25rem;
        }

        .tips-card h6 {
            font-size: .95rem;
            font-weight: 700;
            color: #92400e;
            margin-bottom: .65rem;
        }

        .tips-card ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .tips-card ul li {
            font-size: .82rem;
            color: #78350f;
            margin-bottom: .35rem;
        }

        .tips-card ul li:last-child {
            margin-bottom: 0;
        }

        .tips-card code {
            background: #fef3c7;
            color: #b45309;
            padding: .1rem .35rem;
            border-radius: .25rem;
            font-size: .8rem;
        }
    </style>
@stop

@section('content_header')
    <h1>Pengaturan Sistem</h1>
@stop

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    {{-- Bersihkan Semua --}}
    <div class="setting-header-card">
        <div>
            <div class="header-title"><i class="fas fa-broom mr-2 text-danger"></i>Bersihkan Semua Cache &amp; Sesi</div>
            <p class="header-sub">Menjalankan cache:clear, view:clear, config:clear, route:clear, dan menghapus semua file
                sesi.</p>
        </div>
        <form method="POST" action="{{ route('app-settings.clear-all') }}"
            onsubmit="return confirm('Jalankan semua pembersihan?\n\nCache akan dihapus dan semua pengguna lain akan dipaksa login ulang.')">
            @csrf
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash-alt mr-1"></i> Bersihkan Semua
            </button>
        </form>
    </div>

    <div class="row">

        {{-- Cache Aplikasi --}}
        <div class="col-md-6">
            <div class="cache-card">
                <div class="cache-card-header">
                    <span class="card-icon bg-info" style="color:#fff"><i class="fas fa-database"></i></span>
                    <h6>Cache Aplikasi</h6>
                    <span class="card-path">storage/framework/cache/data</span>
                </div>
                <div class="cache-card-body">
                    <div class="stat-boxes">
                        <div class="stat-box">
                            <div class="stat-val text-info">{{ $cache['count'] }}</div>
                            <div class="stat-lbl">File</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val text-info">{{ $cache['size'] }}</div>
                            <div class="stat-lbl">Ukuran</div>
                        </div>
                    </div>
                    <div class="cache-progress-wrap">
                        <div class="progress">
                            <div class="progress-bar bg-info" style="width:{{ $cache['percent'] }}%"></div>
                        </div>
                        <div class="cache-progress-label">{{ $cache['size'] }} / 10 MB referensi</div>
                    </div>
                </div>
                <div class="cache-card-footer">
                    <form method="POST" action="{{ route('app-settings.clear-cache') }}"
                        onsubmit="return confirm('Hapus cache aplikasi?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-info">
                            <i class="fas fa-trash-alt mr-1"></i> Bersihkan Cache Aplikasi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sesi --}}
        <div class="col-md-6">
            <div class="cache-card">
                <div class="cache-card-header">
                    <span class="card-icon bg-warning" style="color:#fff"><i class="fas fa-users"></i></span>
                    <h6>Sesi (Session)</h6>
                    <span class="card-path">database: tabel sessions &nbsp;|&nbsp; Aktif 30 mnt:
                        <strong>{{ $session['active'] }}</strong> pengguna</span>
                </div>
                <div class="cache-card-body">
                    <div class="stat-boxes">
                        <div class="stat-box">
                            <div class="stat-val text-warning">{{ $session['count'] }}</div>
                            <div class="stat-lbl">Sesi Aktif</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val text-warning">{{ $session['size'] }}</div>
                            <div class="stat-lbl">Ukuran</div>
                        </div>
                    </div>
                    <div class="cache-progress-wrap">
                        <div class="progress">
                            <div class="progress-bar bg-warning" style="width:{{ $session['percent'] }}%"></div>
                        </div>
                        <div class="cache-progress-label">{{ $session['size'] }} / 10 MB referensi</div>
                    </div>
                    <div class="alert alert-warning py-1 px-2 mb-0 small">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Membersihkan sesi akan memaksa semua pengguna logout.
                    </div>
                </div>
                <div class="cache-card-footer">
                    <form method="POST" action="{{ route('app-settings.clear-session') }}"
                        onsubmit="return confirm('Hapus semua sesi pengguna lain?\n\nMereka akan dipaksa login ulang.')">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning">
                            <i class="fas fa-sign-out-alt mr-1"></i> Bersihkan Sesi
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        {{-- Cache View --}}
        <div class="col-md-6">
            <div class="cache-card">
                <div class="cache-card-header">
                    <span class="card-icon bg-success" style="color:#fff"><i class="fas fa-file-code"></i></span>
                    <h6>Cache View (Compiled)</h6>
                    <span class="card-path">storage/framework/views</span>
                </div>
                <div class="cache-card-body">
                    <div class="stat-boxes">
                        <div class="stat-box">
                            <div class="stat-val text-success">{{ $views['count'] }}</div>
                            <div class="stat-lbl">File</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val text-success">{{ $views['size'] }}</div>
                            <div class="stat-lbl">Ukuran</div>
                        </div>
                    </div>
                    <div class="cache-progress-wrap">
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width:{{ $views['percent'] }}%"></div>
                        </div>
                        <div class="cache-progress-label">{{ $views['size'] }} / 10 MB referensi</div>
                    </div>
                </div>
                <div class="cache-card-footer">
                    <form method="POST" action="{{ route('app-settings.clear-view') }}"
                        onsubmit="return confirm('Hapus cache view?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-success">
                            <i class="fas fa-trash-alt mr-1"></i> Bersihkan Cache View
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Config & Route Cache --}}
        <div class="col-md-6">
            <div class="cache-card">
                <div class="cache-card-header">
                    <span class="card-icon bg-secondary" style="color:#fff"><i class="fas fa-cogs"></i></span>
                    <h6>Cache Config &amp; Route</h6>
                    <span class="card-path">bootstrap/cache</span>
                </div>

                <div class="config-row">
                    <div>
                        <div class="cfg-title">
                            Config Cache
                            @if ($configCache['exists'])
                                <span class="badge-aktif ml-1">Aktif</span>
                            @else
                                <span class="badge-nonaktif ml-1">Tidak Aktif</span>
                            @endif
                        </div>
                        <div class="cfg-path">{{ $configCache['path'] }}</div>
                    </div>
                    <div class="cfg-size">{{ $configCache['size'] }}</div>
                </div>

                <div class="config-row">
                    <div>
                        <div class="cfg-title">
                            Route Cache
                            @if ($routeCache['exists'])
                                <span class="badge-aktif ml-1">Aktif</span>
                            @else
                                <span class="badge-nonaktif ml-1">Tidak Aktif</span>
                            @endif
                        </div>
                        <div class="cfg-path">{{ $routeCache['path'] }}</div>
                    </div>
                    <div class="cfg-size">{{ $routeCache['size'] }}</div>
                </div>

                <div class="config-actions">
                    <form method="POST" action="{{ route('app-settings.clear-config') }}"
                        onsubmit="return confirm('Hapus config cache?')" class="flex-fill">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-trash-alt mr-1"></i> Clear Config
                        </button>
                    </form>
                    <form method="POST" action="{{ route('app-settings.clear-route') }}"
                        onsubmit="return confirm('Hapus route cache?')" class="flex-fill">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-trash-alt mr-1"></i> Clear Route
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <div class="cache-card" id="wa-fonnte">
        <div class="cache-card-header">
            <span class="card-icon bg-success" style="color:#fff"><i class="fab fa-whatsapp"></i></span>
            <h6>Konfigurasi WA Fonnte</h6>
            <span class="card-path">Digunakan untuk pengingat otomatis guru yang belum mengisi absensi siswa dan
                agenda.</span>
        </div>
        <div class="cache-card-body">
            <div class="alert alert-info py-2 px-3 small mb-3">
                <i class="fas fa-shield-alt mr-1"></i>
                Batas keamanan aktif: fitur tes WA dibatasi maksimal 3 kali per menit per admin.
            </div>

            <form method="POST" action="{{ route('app-settings.fonnte.update') }}">
                @csrf

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="font-weight-semibold">Aktifkan Integrasi Fonnte</label>
                        <input type="hidden" name="enabled" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled"
                                value="1"
                                {{ old('enabled', $fonnte['enabled'] ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="enabled">Aktif</label>
                        </div>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-semibold">Aktifkan Pengingat Otomatis</label>
                        <input type="hidden" name="reminder_enabled" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="reminder_enabled"
                                name="reminder_enabled" value="1"
                                {{ old('reminder_enabled', $fonnte['reminder_enabled'] ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="reminder_enabled">Aktif</label>
                        </div>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="reminder_time" class="font-weight-semibold">Jam Pengingat</label>
                        <input type="time" class="form-control @error('reminder_time') is-invalid @enderror"
                            id="reminder_time" name="reminder_time"
                            value="{{ old('reminder_time', $fonnte['reminder_time']) }}" required>
                        @error('reminder_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="token" class="font-weight-semibold">Token Fonnte</label>
                        <input type="text" class="form-control @error('token') is-invalid @enderror" id="token"
                            name="token" value="{{ old('token', $fonnte['token']) }}"
                            placeholder="Masukkan token API Fonnte">
                        @error('token')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="api_url" class="font-weight-semibold">API URL</label>
                        <input type="url" class="form-control @error('api_url') is-invalid @enderror" id="api_url"
                            name="api_url" value="{{ old('api_url', $fonnte['api_url']) }}" required>
                        @error('api_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="default_country_code" class="font-weight-semibold">Kode Negara Default</label>
                        <input type="text" class="form-control @error('default_country_code') is-invalid @enderror"
                            id="default_country_code" name="default_country_code"
                            value="{{ old('default_country_code', $fonnte['default_country_code']) }}" required>
                        @error('default_country_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-8">
                        <label for="school_name" class="font-weight-semibold">Nama Sekolah (di pesan)</label>
                        <input type="text" class="form-control @error('school_name') is-invalid @enderror"
                            id="school_name" name="school_name" value="{{ old('school_name', $fonnte['school_name']) }}"
                            required>
                        @error('school_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-1"></i> Simpan Konfigurasi WA Fonnte
                </button>
            </form>

            <hr>

            <form method="POST" action="{{ route('app-settings.fonnte.test') }}">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label for="test_target" class="font-weight-semibold">Nomor Tujuan Tes</label>
                        <input type="text" class="form-control @error('test_target') is-invalid @enderror"
                            id="test_target" name="test_target" value="{{ old('test_target') }}"
                            placeholder="Contoh: 08123456789" required>
                        @error('test_target')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="test_message" class="font-weight-semibold">Pesan Tes (opsional)</label>
                        <input type="text" class="form-control @error('test_message') is-invalid @enderror"
                            id="test_message" name="test_message" value="{{ old('test_message') }}"
                            placeholder="Kosongkan untuk pesan bawaan sistem">
                        @error('test_message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-outline-success btn-block">
                            <i class="fas fa-paper-plane mr-1"></i> Tes Kirim
                        </button>
                    </div>
                </div>
                <small class="text-muted d-block">Gunakan nomor aktif WhatsApp. Format bebas, sistem akan normalisasi
                    ke kode negara default.</small>
            </form>

            <hr>

            <form method="POST" action="{{ route('app-settings.fonnte.test-guru-sample') }}"
                onsubmit="return confirm('Kirim pesan tes ke maksimal 3 guru pertama yang memiliki no_hp?')">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-md-10">
                        <label for="sample_test_message" class="font-weight-semibold">Pesan Tes ke Sampel Guru
                            (opsional)</label>
                        <input type="text" class="form-control @error('sample_test_message') is-invalid @enderror"
                            id="sample_test_message" name="sample_test_message" value="{{ old('sample_test_message') }}"
                            placeholder="Kosongkan untuk pesan bawaan sistem">
                        @error('sample_test_message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-bullhorn mr-1"></i> Tes 3 Guru
                        </button>
                    </div>
                </div>
                <small class="text-muted d-block">Mode aman: hanya mengirim ke maksimal 3 guru pertama yang punya
                    nomor HP.</small>
            </form>
        </div>
    </div>

    {{-- Tips Optimasi --}}
    <div class="tips-card">
        <h6><i class="fas fa-lightbulb mr-2"></i>Tips Optimasi</h6>
        <ul>
            <li>Jalankan <code>php artisan config:cache</code> di production agar konfigurasi dimuat lebih cepat.</li>
            <li>Jalankan <code>php artisan route:cache</code> di production untuk mempercepat pencocokan route.</li>
            <li>Cache view (<code>php artisan view:cache</code>) sudah dikompilasi otomatis saat pertama kali diakses.</li>
            <li>Sesi dengan jumlah besar dapat memperlambat performa — pertimbangkan membersihkan sesi secara berkala.</li>
            <li>Setelah update kode di production, jalankan <strong>Bersihkan Semua</strong> agar semua cache diperbarui.
            </li>
        </ul>
    </div>

@stop

@section('footer')
    @include('components.app-footer')
@stop
