@extends('adminlte::page')

@section('title', 'Dashboard Guru')

@section('css')
    <style>
        .guru-subtitle {
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
            <h1 class="mb-1">Dashboard Guru</h1>
            <p class="guru-subtitle">Ringkasan performa kehadiran mengajar Anda</p>
        </div>
        <span class="badge badge-light border px-3 py-2">{{ $today->format('d M Y') }}</span>
    </div>
@stop

@section('content')
    @if (!$teacher)
        <div class="alert alert-warning">
            Data guru untuk akun ini belum ditemukan. Pastikan username login menggunakan NIP yang sesuai data guru.
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
            <div class="small-box bg-primary summary-card">
                <div class="inner">
                    <h3>{{ $targetTeachingMonth }}</h3>
                    <p>Target Mengajar (Bulan Ini)</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="small-box bg-warning summary-card">
                <div class="inner">
                    <h3>{{ $statusCountsMonth['Selesai'] }}</h3>
                    <p>Absensi Selesai (Bulan Ini)</p>
                </div>
                <div class="icon"><i class="fas fa-check-double"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="small-box bg-danger summary-card">
                <div class="inner">
                    <h3>{{ $statusCountsMonth['Belum Absen'] }}</h3>
                    <p>Belum Absen (Bulan Ini)</p>
                </div>
                <div class="icon"><i class="fas fa-user-clock"></i></div>
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
                    <p class="label-small">Persentase Selesai</p>
                    <div class="percent-value text-primary">{{ $statusPercentsMonth['Selesai'] }}%</div>
                    <div class="progress">
                        <div class="progress-bar bg-primary"
                            style="width: {{ min(100, $statusPercentsMonth['Selesai']) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card info-card h-100">
                <div class="card-body">
                    <p class="label-small">Persentase Draft</p>
                    <div class="percent-value text-warning">{{ $statusPercentsMonth['Draft'] }}%</div>
                    <div class="progress">
                        <div class="progress-bar bg-warning" style="width: {{ min(100, $statusPercentsMonth['Draft']) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card info-card h-100">
                <div class="card-body">
                    <p class="label-small">Persentase Belum Absen</p>
                    <div class="percent-value text-danger">{{ $statusPercentsMonth['Belum Absen'] }}%</div>
                    <div class="progress">
                        <div class="progress-bar bg-danger"
                            style="width: {{ min(100, $statusPercentsMonth['Belum Absen']) }}%">
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
                    <h3 class="card-title mb-0">Ringkasan Kehadiran Mengajar</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>Total Absensi Bulan Ini:</strong> {{ $totalRecordsMonth }}</p>
                            <p class="mb-1"><strong>Total Hari Mengajar Tercatat:</strong> {{ $attendanceDaysMonth }}</p>
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
                                <td>Selesai</td>
                                <td>{{ $statusCountsMonth['Selesai'] }}</td>
                                <td>{{ $statusCountsTotal['Selesai'] }}</td>
                            </tr>
                            <tr>
                                <td>Draft</td>
                                <td>{{ $statusCountsMonth['Draft'] }}</td>
                                <td>{{ $statusCountsTotal['Draft'] }}</td>
                            </tr>
                            <tr>
                                <td>Belum Absen</td>
                                <td>{{ $statusCountsMonth['Belum Absen'] }}</td>
                                <td>{{ $statusCountsTotal['Belum Absen'] }}</td>
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
                        <li><strong>NIP:</strong> {{ $teacher?->nip ?? '-' }}</li>
                        <li><strong>Jumlah Kelas Diampu:</strong> {{ $teachingClassCount }}</li>
                        <li><strong>Jumlah Mapel Diampu:</strong> {{ $teachingSubjectCount }}</li>
                        <li><strong>Jadwal Hari Ini:</strong> {{ $todayScheduleCount }}</li>
                        <li><strong>Absensi Hari Ini:</strong> {{ $todayAttendanceCount }}</li>
                        <li><strong>Kontak Sekolah:</strong> {{ $schoolSetting?->telepon ?? '-' }}</li>
                    </ul>

                    @if ($latestTeacherAttendance)
                        <div class="alert alert-light border mb-0">
                            <strong>Absensi Terakhir:</strong><br>
                            {{ $latestTeacherAttendance->tanggal ? \Carbon\Carbon::parse($latestTeacherAttendance->tanggal)->format('d M Y') : '-' }}
                            - {{ $latestTeacherAttendance->status }}
                            @if ($latestTeacherAttendance->subject)
                                ({{ $latestTeacherAttendance->subject->nama_mapel }})
                            @endif
                        </div>
                    @else
                        <div class="alert alert-light border mb-0">
                            Belum ada riwayat absensi mengajar.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card info-card">
        <div class="card-header">
            <h3 class="card-title mb-0">Tentang Aplikasi</h3>
        </div>
        <div class="card-body">
            <p class="mb-2">
                Aplikasi ini digunakan untuk manajemen absensi sekolah, mencakup absensi guru,
                absensi siswa oleh guru, dan absensi siswa berbasis IoT agar monitoring kehadiran lebih
                terintegrasi.
            </p>
            <p class="mb-0"><strong>Deploy by:</strong> Asep Hidayat Ramdani, S.T.</p>
        </div>
    </div>

@stop
