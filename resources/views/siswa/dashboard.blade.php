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
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end">
        <div>
            <h1 class="mb-1">Dashboard Siswa</h1>
            <p class="siswa-subtitle">Ringkasan kehadiran pribadi Anda</p>
        </div>
        <span class="badge badge-light border px-3 py-2">{{ $today->format('d M Y') }}</span>
    </div>
@stop

@section('content')
    @if (!$student)
        <div class="alert alert-warning">
            Data siswa untuk akun ini belum ditemukan. Pastikan username login menggunakan NISN/NIS yang sesuai data siswa.
        </div>
    @endif

    <div class="row">
        <div class="col-lg-3 col-6 mb-3">
            <div class="small-box bg-success summary-card">
                <div class="inner">
                    <h3>{{ $statusCountsMonth['Hadir'] }}</h3>
                    <p>Jumlah Hadir (Bulan Ini)</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="small-box bg-warning summary-card">
                <div class="inner">
                    <h3>{{ $statusCountsMonth['Sakit'] }}</h3>
                    <p>Jumlah Sakit (Bulan Ini)</p>
                </div>
                <div class="icon"><i class="fas fa-procedures"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="small-box bg-info summary-card">
                <div class="inner">
                    <h3>{{ $statusCountsMonth['Izin'] }}</h3>
                    <p>Jumlah Izin (Bulan Ini)</p>
                </div>
                <div class="icon"><i class="fas fa-file-signature"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="small-box bg-danger summary-card">
                <div class="inner">
                    <h3>{{ $statusCountsMonth['Alpa'] }}</h3>
                    <p>Jumlah Alpa (Bulan Ini)</p>
                </div>
                <div class="icon"><i class="fas fa-user-times"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card info-card h-100">
                <div class="card-body">
                    <p class="label-small">Persentase Hadir</p>
                    <div class="percent-value text-success">{{ $statusPercentsMonth['Hadir'] }}%</div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: {{ min(100, $statusPercentsMonth['Hadir']) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card info-card h-100">
                <div class="card-body">
                    <p class="label-small">Persentase Sakit</p>
                    <div class="percent-value text-warning">{{ $statusPercentsMonth['Sakit'] }}%</div>
                    <div class="progress">
                        <div class="progress-bar bg-warning" style="width: {{ min(100, $statusPercentsMonth['Sakit']) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card info-card h-100">
                <div class="card-body">
                    <p class="label-small">Persentase Izin</p>
                    <div class="percent-value text-info">{{ $statusPercentsMonth['Izin'] }}%</div>
                    <div class="progress">
                        <div class="progress-bar bg-info" style="width: {{ min(100, $statusPercentsMonth['Izin']) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card info-card h-100">
                <div class="card-body">
                    <p class="label-small">Persentase Alpa</p>
                    <div class="percent-value text-danger">{{ $statusPercentsMonth['Alpa'] }}%</div>
                    <div class="progress">
                        <div class="progress-bar bg-danger" style="width: {{ min(100, $statusPercentsMonth['Alpa']) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-3">
            <div class="card info-card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Ringkasan Kehadiran</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>Total Absensi Bulan Ini:</strong> {{ $totalRecordsMonth }}</p>
                            <p class="mb-1"><strong>Total Hari Tercatat Bulan Ini:</strong> {{ $attendanceDaysMonth }}
                            </p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>Total Absensi Keseluruhan:</strong> {{ $totalRecordsAll }}</p>
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

        <div class="col-lg-5 mb-3">
            <div class="card info-card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Informasi Penting</h3>
                </div>
                <div class="card-body">
                    <ul class="mb-3 info-list pl-3">
                        <li><strong>Nama:</strong> {{ auth()->user()->name }}</li>
                        <li><strong>Kelas:</strong> {{ $student?->classroom?->kode_kelas ?? '-' }}</li>
                        <li><strong>Jurusan:</strong> {{ $student?->classroom?->major?->nama_jurusan ?? '-' }}</li>
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
        <div class="card-header">
            <h3 class="card-title mb-0">Deskripsi Aplikasi</h3>
        </div>
        <div class="card-body">
            <p class="mb-2">
                Aplikasi absensi sekolah ini membantu pencatatan kehadiran siswa dan guru secara terintegrasi,
                termasuk absensi kelas oleh guru dan dukungan absensi berbasis perangkat IoT.
            </p>
            <p class="mb-1"><strong>Nama Aplikasi:</strong> {{ config('app_version.name', config('app.name')) }}</p>
            <p class="mb-1"><strong>Versi:</strong> {{ config('app_version.version', '-') }}</p>
            <p class="mb-0"><strong>Pengembang:</strong> {{ config('app_version.developer', '-') }}</p>
        </div>
    </div>

@stop
