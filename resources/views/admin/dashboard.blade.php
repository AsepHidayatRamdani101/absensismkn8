@extends('adminlte::page')

@section('title', 'Dashboard Admin')

@section('plugins.Chartjs', true)

@section('css')
    <style>
        .dashboard-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-top: 0.15rem;
            margin-bottom: 0;
        }

        .stat-card {
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 0.35rem 1rem rgba(33, 37, 41, 0.08);
            min-height: 128px;
        }

        .stat-card .inner h3 {
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .stat-card .inner p {
            margin-bottom: 0;
            font-size: 0.92rem;
            opacity: 0.95;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 0.85rem;
            letter-spacing: 0.2px;
        }

        .kpi-card {
            border-radius: 0.75rem;
            box-shadow: 0 0.35rem 1rem rgba(33, 37, 41, 0.08);
            height: 100%;
        }

        .kpi-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .kpi-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.35rem;
        }

        .metric-stack {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.65rem;
            padding: 0.85rem 0.95rem;
            margin-bottom: 0.65rem;
        }

        .metric-stack:last-child {
            margin-bottom: 0;
        }

        .chart-card,
        .table-card {
            border-radius: 0.75rem;
            box-shadow: 0 0.35rem 1rem rgba(33, 37, 41, 0.08);
        }

        .table-clean th {
            font-size: 0.8rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            color: #6c757d;
            border-top: none;
        }

        .table-clean td {
            vertical-align: middle;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end">
        <div>
            <h1 class="mb-0">Dashboard Admin</h1>
            <p class="dashboard-subtitle">Ringkasan performa sistem absensi sekolah</p>
        </div>
        <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
            <a href="{{ route('admin.dashboard.dss', ['mode' => $mode ?? 'ringkas']) }}"
                class="btn btn-sm btn-outline-secondary">
                Dashboard Pancawaluya
            </a>
            <div class="btn-group btn-group-sm" role="group" aria-label="Mode tampilan dashboard admin">
                <a href="{{ route('admin.dashboard', ['mode' => 'ringkas']) }}"
                    class="btn {{ ($mode ?? 'ringkas') === 'ringkas' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Ringkas
                </a>
                <a href="{{ route('admin.dashboard', ['mode' => 'detail']) }}"
                    class="btn {{ ($mode ?? 'ringkas') === 'detail' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Detail
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    @php
        $isDetail = ($mode ?? 'ringkas') === 'detail';
    @endphp

    <p class="section-title">Aktivitas Absensi Hari Ini</p>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary stat-card">
                <div class="inner">
                    <h3>{{ $todayTeacherAttendances }}</h3>
                    <p>Absensi Guru Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-teal stat-card">
                <div class="inner">
                    <h3>{{ $todayStudentAttendanceByTeacher }}</h3>
                    <p>Absensi Siswa Kelas Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary stat-card">
                <div class="inner">
                    <h3>{{ $todayStudentAttendanceIoT }}</h3>
                    <p>Absensi Siswa IoT Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-microchip"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-indigo stat-card">
                <div class="inner">
                    <h3>{{ $totalDevices }}</h3>
                    <p>Total Perangkat IoT</p>
                </div>
                <div class="icon">
                    <i class="fas fa-satellite-dish"></i>
                </div>
            </div>
        </div>
    </div>

    <p class="section-title mt-2">Master Data</p>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info stat-card">
                <div class="inner">
                    <h3>{{ $totalStudents }}</h3>
                    <p>Total Siswa</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success stat-card">
                <div class="inner">
                    <h3>{{ $totalTeachers }}</h3>
                    <p>Total Guru</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning stat-card">
                <div class="inner">
                    <h3>{{ $totalClassrooms }}</h3>
                    <p>Total Kelas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-school"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger stat-card">
                <div class="inner">
                    <h3>{{ $totalMajors }}</h3>
                    <p>Total Jurusan</p>
                </div>
                <div class="icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
            </div>
        </div>
    </div>

    <p class="section-title mt-2">Persentase Kehadiran</p>
    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-success kpi-card">
                <div class="card-header">
                    <h3 class="card-title">Guru</h3>
                </div>
                <div class="card-body">
                    <div class="metric-stack">
                        <div class="kpi-label">Hari Ini</div>
                        <div class="kpi-value text-success">{{ $teacherPresencePercent }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar"
                                style="width: {{ min(100, $teacherPresencePercent) }}%"
                                aria-valuenow="{{ $teacherPresencePercent }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <div class="metric-stack">
                        <div class="kpi-label">Minggu Ini</div>
                        <div class="kpi-value text-primary">{{ $teacherPresencePercentWeek }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar"
                                style="width: {{ min(100, $teacherPresencePercentWeek) }}%"
                                aria-valuenow="{{ $teacherPresencePercentWeek }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <div class="metric-stack">
                        <div class="kpi-label">Bulan Ini</div>
                        <div class="kpi-value text-indigo">{{ $teacherPresencePercentMonth }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-indigo" role="progressbar"
                                style="width: {{ min(100, $teacherPresencePercentMonth) }}%"
                                aria-valuenow="{{ $teacherPresencePercentMonth }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-outline card-info kpi-card">
                <div class="card-header">
                    <h3 class="card-title">Siswa</h3>
                </div>
                <div class="card-body">
                    <div class="metric-stack">
                        <div class="kpi-label">Hari Ini</div>
                        <div class="kpi-value text-info">{{ $studentPresencePercent }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar"
                                style="width: {{ min(100, $studentPresencePercent) }}%"
                                aria-valuenow="{{ $studentPresencePercent }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <div class="metric-stack">
                        <div class="kpi-label">Minggu Ini</div>
                        <div class="kpi-value text-secondary">{{ $studentPresencePercentWeek }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-secondary" role="progressbar"
                                style="width: {{ min(100, $studentPresencePercentWeek) }}%"
                                aria-valuenow="{{ $studentPresencePercentWeek }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <div class="metric-stack">
                        <div class="kpi-label">Bulan Ini</div>
                        <div class="kpi-value text-warning">{{ $studentPresencePercentMonth }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar"
                                style="width: {{ min(100, $studentPresencePercentMonth) }}%"
                                aria-valuenow="{{ $studentPresencePercentMonth }}" aria-valuemin="0"
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($isDetail)
        <p class="section-title mt-2">Visualisasi</p>
        <div class="row">
            <div class="col-md-8">
                <div class="card card-outline card-primary chart-card">
                    <div class="card-header">
                        <h3 class="card-title">Tren 7 Hari Terakhir</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceTrendChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-outline card-warning chart-card">
                    <div class="card-header">
                        <h3 class="card-title">Status Absensi Siswa Hari Ini</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="studentStatusChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif

@stop

@section('js')
    <script>
        $(function() {
            const trendCanvas = document.getElementById('attendanceTrendChart');
            const statusCanvas = document.getElementById('studentStatusChart');

            if (trendCanvas) {
                new Chart(trendCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($dailyLabels ?? []),
                        datasets: [{
                                label: 'Absensi Guru',
                                data: @json($dailyTeacherCounts ?? []),
                                borderColor: '#007bff',
                                backgroundColor: 'rgba(0, 123, 255, 0.15)',
                                tension: 0.35,
                                fill: true
                            },
                            {
                                label: 'Siswa Hadir',
                                data: @json($dailyStudentPresentCounts ?? []),
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.15)',
                                tension: 0.35,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

            if (statusCanvas) {
                new Chart(statusCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: @json($statusLabels ?? []),
                        datasets: [{
                            data: @json($statusData ?? []),
                            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6610f2']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
    </script>
@stop
