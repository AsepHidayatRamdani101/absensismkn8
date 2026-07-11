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

        .app-info-card {
            border-radius: 0.85rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 0.35rem 1rem rgba(33, 37, 41, 0.07);
        }

        .app-info-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.45rem;
            color: #343a40;
        }

        .app-info-text {
            margin-bottom: 0.25rem;
            color: #495057;
            line-height: 1.55;
        }

        .contact-wa-btn {
            border-radius: 999px;
            padding: 0.5rem 0.95rem;
            font-weight: 600;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end">
        <div>
            <h1 class="mb-0">Dashboard Admin</h1>
            <p class="dashboard-subtitle">Ringkasan performa sistem absensi sekolah</p>
        </div>
        <span class="badge badge-light border px-3 py-2">{{ now()->format('d M Y') }}</span>
    </div>
@stop

@section('content')
    <div class="card app-info-card mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="pr-md-3 mb-3 mb-md-0">
                    <h2 class="app-info-title">Tentang Aplikasi</h2>
                    <p class="app-info-text">Aplikasi ini digunakan untuk manajemen absensi sekolah, mencakup absensi guru,
                        absensi siswa oleh guru, absensi siswa berbasis IoT, serta absensi guru oleh siswa pengurus kelas
                        (KM, Sekretaris, dan Bendahara) agar monitoring kehadiran lebih terintegrasi.</p>
                    <p class="app-info-text mb-0"><strong>Deploy by:</strong> Asep Hidayat Ramdani, S.T.</p>
                </div>
                <div>
                    <a href="https://wa.me/6282126574516?text=Halo%20Pak%20Asep%2C%20saya%20membutuhkan%20bantuan%20terkait%20aplikasi%20absensi%20sekolah."
                        target="_blank" rel="noopener" class="btn btn-success contact-wa-btn">
                        <i class="fab fa-whatsapp mr-1"></i>
                        Hubungi 082126574516
                    </a>
                </div>
            </div>
        </div>
    </div>

    <p class="section-title">Master Data</p>
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

    <p class="section-title mt-2">Aktivitas Absensi Hari Ini</p>
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

    <p class="section-title mt-2">Statistik Tambahan</p>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-light stat-card">
                <div class="inner">
                    <h3>{{ $totalSubjects }}</h3>
                    <p>Total Mata Pelajaran</p>
                </div>
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-lightblue stat-card">
                <div class="inner">
                    <h3>{{ $todayStudentAttendanceByTeacher + $todayStudentAttendanceIoT }}</h3>
                    <p>Total Aktivitas Absensi Siswa Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-info stat-card">
                <div class="inner">
                    <h3>{{ $studentClassOfficers }}</h3>
                    <p>Total Siswa Pengurus Kelas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-indigo stat-card">
                <div class="inner">
                    <h3>{{ $todayOfficerAttendanceActions }}</h3>
                    <p>Absensi Guru oleh Pengurus (Hari Ini)</p>
                    <small>KM: {{ $ketuaKelasCount }}, Sek: {{ $sekretarisCount }}, Ben: {{ $bendaharaCount }}</small>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
        </div>
    </div>

    <p class="section-title mt-2">Ranking Kehadiran Bulan Ini</p>
    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-success table-card">
                <div class="card-header">
                    <h3 class="card-title">Guru Terbanyak Hadir (Bulan Ini)</h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-hover table-clean mb-0">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th>Nama Guru</th>
                                <th width="20%" style="text-align: center">Hadir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topTeachersPresent as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row['nama_lengkap'] }}</td>
                                    <td style="text-align: center">{{ $row['hadir'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-outline card-danger table-card">
                <div class="card-header">
                    <h3 class="card-title">Guru Terbanyak Tidak Hadir (Bulan Ini)</h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-hover table-clean mb-0">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th>Nama Guru</th>
                                <th width="30%" style="text-align: center">Tidak Hadir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topTeachersAbsent as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row['nama_lengkap'] }}</td>
                                    <td style="text-align: center">{{ $row['tidak_hadir'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-info table-card">
                <div class="card-header">
                    <h3 class="card-title">Siswa Terbanyak Hadir (Bulan Ini)</h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-hover table-clean mb-0">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th>Nama Siswa</th>
                                <th width="20%" style="text-align: center">Hadir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topStudentsPresent as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->nama_lengkap }}</td>
                                    <td style="text-align: center">{{ $row->hadir }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-outline card-warning table-card">
                <div class="card-header">
                    <h3 class="card-title">Siswa Terbanyak Tidak Hadir (Bulan Ini)</h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-hover table-clean mb-0">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th>Nama Siswa</th>
                                <th width="30%" style="text-align: center">Tidak Hadir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topStudentsAbsent as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->nama_lengkap }}</td>
                                    <td style="text-align: center">{{ $row->tidak_hadir }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function() {
            const trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
            const statusCtx = document.getElementById('studentStatusChart').getContext('2d');

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: @json($dailyLabels),
                    datasets: [{
                            label: 'Absensi Guru',
                            data: @json($dailyTeacherCounts),
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.15)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'Siswa Hadir',
                            data: @json($dailyStudentPresentCounts),
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

            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($statusLabels),
                    datasets: [{
                        data: @json($statusData),
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
        });
    </script>
@stop
