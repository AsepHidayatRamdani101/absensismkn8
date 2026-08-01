@extends('adminlte::page')

@section('title', 'Dashboard Siswa')

@section('css')
    <style>
        .siswa-subtitle {
            margin: 0;
            color: #6c757d;
        }

        .summary-card {
            border-radius: 0.75rem;
            box-shadow: 0 0.35rem 1rem rgba(33, 37, 41, 0.08);
            height: 100%;
        }

        .summary-card .inner h3 {
            font-size: 1.7rem;
            margin-bottom: 0.2rem;
            font-weight: 700;
        }

        .label-small {
            color: #6c757d;
            font-size: 0.88rem;
            margin-bottom: 0.35rem;
        }

        .percent-value {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.45rem;
        }

        .info-card {
            border-radius: 0.75rem;
            box-shadow: 0 0.35rem 1rem rgba(33, 37, 41, 0.08);
        }

        .info-list li {
            margin-bottom: 0.45rem;
        }

        .identity-progress {
            height: 10px;
            border-radius: 999px;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end">
        <div>
            <h1 class="mb-1">Dashboard Siswa</h1>
            <p class="siswa-subtitle">Ringkasan performa kehadiran Anda</p>
        </div>
        <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
            <a href="{{ route('siswa.dashboard.dss', ['mode' => $mode ?? 'ringkas']) }}"
                class="btn btn-sm btn-outline-secondary">
                Dashboard Pancawaluya
            </a>
            <div class="btn-group btn-group-sm" role="group" aria-label="Mode tampilan dashboard siswa">
                <a href="{{ route('siswa.dashboard', ['mode' => 'ringkas']) }}"
                    class="btn {{ ($mode ?? 'ringkas') === 'ringkas' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Ringkas
                </a>
                <a href="{{ route('siswa.dashboard', ['mode' => 'detail']) }}"
                    class="btn {{ ($mode ?? 'ringkas') === 'detail' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Detail
                </a>
            </div>
            <span class="badge badge-light border px-3 py-2">{{ $today->format('d M Y') }}</span>
        </div>
    </div>
@stop

@section('content')
    @php
        $isDetail = ($mode ?? 'ringkas') === 'detail';

        $identityFields = $student
            ? [
                'Orang Tua / Wali' => $student->nama_orang_tua_wali,
                'Alamat' => $student->alamat,
                'No HP Siswa' => $student->no_hp,
                'No HP Orang Tua' => $student->no_hp_orang_tua,
                'Tinggi Badan' => $student->tinggi_badan,
                'Berat Badan' => $student->berat_badan,
            ]
            : [];

        $missingIdentityFields = collect($identityFields)
            ->filter(fn($value) => $value === null || trim((string) $value) === '')
            ->keys()
            ->values();

        $identityCompletion =
            count($identityFields) > 0
                ? round(((count($identityFields) - $missingIdentityFields->count()) / count($identityFields)) * 100)
                : 0;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if (!$student)
        <div class="alert alert-warning">
            Data siswa untuk akun ini belum ditemukan. Pastikan username login menggunakan NISN/NIS yang sesuai data siswa.
        </div>
    @endif

    @if ($student && $missingIdentityFields->isNotEmpty())
        <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong>Profil belum lengkap ({{ $identityCompletion }}%).</strong>
                <span>Data yang belum diisi: {{ $missingIdentityFields->join(', ') }}.</span>
            </div>
            <a href="{{ route('siswa.identity.edit') }}" class="btn btn-warning btn-sm mt-2 mt-md-0">
                Lengkapi Sekarang
            </a>
        </div>
    @endif

    <div class="card info-card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2 mb-md-0">
                    <a href="{{ route('siswa.attendance-history.index') }}"
                        class="btn btn-outline-primary btn-block">Riwayat Absen</a>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <a href="{{ route('siswa.leave-requests.index') }}" class="btn btn-outline-primary btn-block">Pengajuan
                        Izin/Sakit</a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('siswa.identity.edit') }}" class="btn btn-outline-primary btn-block">Identitas
                        Siswa</a>
                </div>
            </div>
            <div class="card info-card h-100">
            </div>

            @if ($isDetail)
                <div class="row">
                    <div class="col-lg-7 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Ringkasan Kehadiran</h3>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-sm-6">
                                        <p class="mb-1"><strong>Total Absensi Bulan Ini:</strong>
                                            {{ $totalRecordsMonth }}</p>
                                        <p class="mb-1"><strong>Total Hari Tercatat Bulan Ini:</strong>
                                            {{ $attendanceDaysMonth }}
                                        </p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="mb-1"><strong>Total Absensi Keseluruhan:</strong>
                                            {{ $totalRecordsAll }}</p>
                                        <p class="mb-1"><strong>Periode:</strong> {{ $monthStart->format('d M Y') }} -
                                            {{ $monthEnd->format('d M Y') }}</p>
                                    </div>
                                </div>

                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th style="width: 25%;">Jumlah (Bulan Ini)</th>
                                            <th style="width: 25%;">Jumlah (Total)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Hadir</td>
                                            <td>{{ $statusCountsMonth['Hadir'] }}</td>
                                            <td>{{ $statusCountsTotal['Hadir'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Sakit</td>
                                            <td>{{ $statusCountsMonth['Sakit'] }}</td>
                                            <td>{{ $statusCountsTotal['Sakit'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Izin</td>
                                            <td>{{ $statusCountsMonth['Izin'] }}</td>
                                            <td>{{ $statusCountsTotal['Izin'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Alpa</td>
                                            <td>{{ $statusCountsMonth['Alpa'] }}</td>
                                            <td>{{ $statusCountsTotal['Alpa'] }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="col-lg-5 mb-3">
                            <div class="card info-card h-100">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Informasi Penting</h3>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-3 info-list pl-3">
                                        <li><strong>Nama:</strong> {{ auth()->user()->name }}</li>
                                        <li><strong>Kelas:</strong> {{ $student?->classroom?->kode_kelas ?? '-' }}</li>
                                        <li><strong>Jurusan:</strong>
                                            {{ $student?->classroom?->major?->nama_jurusan ?? '-' }}</li>
                                        <li><strong>Jam Masuk Sekolah:</strong>
                                            {{ $schoolSetting?->jam_masuk ? \Carbon\Carbon::parse($schoolSetting->jam_masuk)->format('H:i') : '-' }}
                                        </li>
                                        <li><strong>Batas Terlambat:</strong> {{ $schoolSetting?->batas_terlambat ?? '-' }}
                                            menit</li>
                                        <li><strong>Kontak Sekolah:</strong> {{ $schoolSetting?->telepon ?? '-' }}</li>
                                    </ul>

                                    @if ($latestAttendance)
                                        <div class="alert alert-light border mb-0">
                                            <strong>Absensi Terakhir:</strong><br>
                                            {{ $latestAttendance->teacherAttendance?->tanggal ? \Carbon\Carbon::parse($latestAttendance->teacherAttendance->tanggal)->format('d M Y') : '-' }}
                                            -
                                            {{ $latestAttendance->status }}
                                            @if ($latestAttendance->teacherAttendance?->subject)
                                                ({{ $latestAttendance->teacherAttendance->subject->nama_mapel }})
                                            @endif
                                        </div>
                                    @else
                                        <div class="alert alert-light border mb-0">
                                            Belum ada riwayat absensi.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

            @endif
            <div class="col-lg-7 mb-3">
                @if ($isDetail)
                    <div class="card info-card">
                        @if ($student)
                            <div class="card-header">
                                <h3 class="card-title mb-0">Identitas Siswa</h3>
                            </div>
                            <div class="card-body border-bottom">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <p class="mb-2">
                                            Lengkapi identitas Anda secara mandiri melalui halaman identitas siswa.
                                            @if ($missingIdentityFields->isEmpty())
                                                <span class="badge badge-success ml-1">Lengkap</span>
                                            @else
                                                <span class="badge badge-warning ml-1">Belum Lengkap</span>
                                            @endif
                                        </p>
                                        <p class="mb-1"><strong>Orang Tua / Wali:</strong>
                                            {{ $student->nama_orang_tua_wali ?: '-' }}</p>
                                        <p class="mb-1"><strong>No HP Siswa:</strong> {{ $student->no_hp ?: '-' }}</p>
                                        <p class="mb-1"><strong>No HP Orang Tua:</strong>
                                            {{ $student->no_hp_orang_tua ?: '-' }}</p>
                                        <p class="mb-1"><strong>Progress:</strong> {{ $identityCompletion }}%</p>
                                        <div class="progress identity-progress">
                                            <div class="progress-bar {{ $identityCompletion === 100 ? 'bg-success' : 'bg-warning' }}"
                                                style="width: {{ $identityCompletion }}%"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                                        @if (!empty($student->qr_token))
                                            <div class="mb-2 d-inline-block" style="line-height: 0;">
                                                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(1)->generate(route('students.qr.show', ['token' => $student->qr_token])) !!}
                                            </div>
                                        @endif
                                        <br>
                                        <a href="{{ route('siswa.identity.edit') }}" class="btn btn-primary">
                                            <i class="fas fa-id-card mr-1"></i>
                                            Buka Identitas
                                        </a>
                                        <div class="btn-group mt-2" role="group" aria-label="Format download QR siswa">
                                            <a href="{{ route('siswa.identity.qr.download', ['format' => 'png']) }}"
                                                class="btn btn-outline-primary">
                                                <i class="fas fa-download mr-1"></i>
                                                Download QR (PNG)
                                            </a>
                                            <button type="button"
                                                class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <span class="sr-only">Pilih format lain</span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item"
                                                    href="{{ route('siswa.identity.qr.download', ['format' => 'png']) }}">
                                                    PNG (Disarankan)
                                                </a>
                                                <a class="dropdown-item"
                                                    href="{{ route('siswa.identity.qr.download', ['format' => 'jpg']) }}">
                                                    JPG
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                        @endif

                    </div>
                @endif
                <li><strong>Jam Masuk Sekolah:</strong>
                    {{ $schoolSetting?->jam_masuk ? \Carbon\Carbon::parse($schoolSetting->jam_masuk)->format('H:i') : '-' }}
                </li>
                <li><strong>Batas Terlambat:</strong> {{ $schoolSetting?->batas_terlambat ?? '-' }} menit</li>
                <li><strong>Kontak Sekolah:</strong> {{ $schoolSetting?->telepon ?? '-' }}</li>
                </ul>

                @if ($latestAttendance)
                    <div class="alert alert-light border mb-0">
                        <strong>Absensi Terakhir:</strong><br>
                        {{ $latestAttendance->teacherAttendance?->tanggal ? \Carbon\Carbon::parse($latestAttendance->teacherAttendance->tanggal)->format('d M Y') : '-' }}
                        -
                        {{ $latestAttendance->status }}
                        @if ($latestAttendance->teacherAttendance?->subject)
                            ({{ $latestAttendance->teacherAttendance->subject->nama_mapel }})
                        @endif
                    </div>
                @else
                    <div class="alert alert-light border mb-0">
                        Belum ada riwayat absensi.
                    </div>
                @endif
            </div>
        </div>
    </div>
    </div>

    <div class="card info-card">
        @if ($student)
            <div class="card-header">
                <h3 class="card-title mb-0">Identitas Siswa</h3>
            </div>
            <div class="card-body border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <p class="mb-2">
                            Lengkapi identitas Anda secara mandiri melalui halaman identitas siswa.
                            @if ($missingIdentityFields->isEmpty())
                                <span class="badge badge-success ml-1">Lengkap</span>
                            @else
                                <span class="badge badge-warning ml-1">Belum Lengkap</span>
                            @endif
                        </p>
                        <p class="mb-1"><strong>Orang Tua / Wali:</strong> {{ $student->nama_orang_tua_wali ?: '-' }}
                        </p>
                        <p class="mb-1"><strong>No HP Siswa:</strong> {{ $student->no_hp ?: '-' }}</p>
                        <p class="mb-1"><strong>No HP Orang Tua:</strong> {{ $student->no_hp_orang_tua ?: '-' }}</p>
                        <p class="mb-1"><strong>Progress:</strong> {{ $identityCompletion }}%</p>
                        <div class="progress identity-progress">
                            <div class="progress-bar {{ $identityCompletion === 100 ? 'bg-success' : 'bg-warning' }}"
                                style="width: {{ $identityCompletion }}%"></div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                        @if (!empty($student->qr_token))
                            <div class="mb-2 d-inline-block" style="line-height: 0;">
                                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(1)->generate(route('students.qr.show', ['token' => $student->qr_token])) !!}
                            </div>
                        @endif
                        <br>
                        <a href="{{ route('siswa.identity.edit') }}" class="btn btn-primary">
                            <i class="fas fa-id-card mr-1"></i>
                            Buka Identitas
                        </a>
                        <div class="btn-group mt-2" role="group" aria-label="Format download QR siswa">
                            <a href="{{ route('siswa.identity.qr.download', ['format' => 'png']) }}"
                                class="btn btn-outline-primary">
                                <i class="fas fa-download mr-1"></i>
                                Download QR (PNG)
                            </a>
                            <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="sr-only">Pilih format lain</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item"
                                    href="{{ route('siswa.identity.qr.download', ['format' => 'png']) }}">
                                    PNG (Disarankan)
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('siswa.identity.qr.download', ['format' => 'jpg']) }}">
                                    JPG
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

@stop
