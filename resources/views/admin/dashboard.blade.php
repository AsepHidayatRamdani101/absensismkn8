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

        .status-card {
            border-width: 0 0 0 4px;
            cursor: pointer;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }

        .status-card:hover,
        .status-card:focus {
            box-shadow: 0 0.5rem 1rem rgba(33, 37, 41, 0.14);
            transform: translateY(-2px);
        }

        .status-card .card-body {
            padding: 0.85rem;
        }

        .status-count {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0.35rem 0;
        }

        .status-card-label {
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
        <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
            <a href="{{ route('admin.dashboard.dss', ['mode' => $mode ?? 'ringkas']) }}"
                class="btn btn-sm btn-outline-secondary">
                Dashboard Pancawaluya
            </a>
            <a href="{{ route('admin.reports.class-effectiveness') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-chart-pie"></i> Efektivitas Kelas
            </a>
            <a href="{{ route('admin.reports.early-warning') }}" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-exclamation-triangle"></i> Early Warning
            </a>
            @if (\App\Support\SchoolCalendar::isPjj(today()))
                <a href="{{ route('admin.pjj.dashboard') }}" class="btn btn-sm btn-primary">
                    Dashboard PJJ
                </a>
            @endif
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
                    <p>Sesi Absensi Guru Hari Ini</p>
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

    <p class="section-title mt-2">Status Absensi Siswa Hari Ini</p>
    <p class="text-muted">
        Jumlah siswa unik berdasarkan absensi kelas oleh guru;
        siswa dapat muncul pada lebih dari satu status jika statusnya berbeda di beberapa sesi.
    </p>
    <div class="row">
        @foreach ($studentStatusCards as $statusCard)
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <button type="button"
                    class="card status-card border-{{ $statusCard['color'] }} w-100 text-left"
                    data-status-key="{{ $statusCard['key'] }}"
                    data-status-label="{{ $statusCard['label'] }}"
                    aria-label="Lihat nama siswa {{ $statusCard['label'] }}, {{ $statusCard['count'] }} siswa">
                    <span class="card-body">
                        <span class="d-block status-card-label">{{ $statusCard['label'] }}</span>
                        <span class="d-block status-count text-{{ $statusCard['color'] }}">{{ $statusCard['count'] }}</span>
                        <small class="text-muted">Klik untuk lihat nama</small>
                    </span>
                </button>
            </div>
        @endforeach
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

    <p class="section-title mt-2">Hari Efektif Bulan Ini</p>
    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-success kpi-card">
                <div class="card-body">
                    <div class="kpi-label">{{ $dashboardMonthLabel }}</div>
                    <div class="kpi-value text-success">{{ $effective_days }}</div>
                    <div class="text-muted">hari efektif sekolah</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-outline card-warning kpi-card">
                <div class="card-body">
                    <div class="kpi-label">{{ $dashboardMonthLabel }}</div>
                    <div class="kpi-value text-warning">{{ $holiday_weekdays }}</div>
                    <div class="text-muted">hari kerja yang ditetapkan sebagai libur</div>
                </div>
            </div>
        </div>
    </div>
    <div class="alert alert-light border">
        Hari efektif dihitung dari Senin sampai Jumat pada bulan ini, dikurangi tanggal yang tercatat di
        Master Data Hari Libur (<code>holidays</code>). Sabtu dan Minggu tidak dihitung sebagai hari efektif.
    </div>

    <div class="modal fade" id="studentStatusModal" tabindex="-1" role="dialog"
        aria-labelledby="studentStatusModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentStatusModalTitle">Daftar Siswa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th width="6%">No</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                </tr>
                            </thead>
                            <tbody id="studentStatusModalBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p class="section-title mt-2">Persentase Kehadiran</p>
    <div class="alert alert-light border">
        <strong>Sumber dan cara hitung:</strong>
        kehadiran guru diambil dari catatan agenda/absensi mengajar pada tabel <code>teacher_attendances</code> dengan status
        <code>kehadiran_guru = Hadir</code>. Kehadiran siswa diambil dari absensi sekolah
        (<code>attendances</code>, termasuk metode Manual/RFID/Face/QR) dan absensi kelas oleh guru
        (<code>attendance_details</code>); status
        <code>Hadir</code>, <code>Terlambat</code>, dan <code>Dispen</code> dihitung hadir. Orang yang sama dihitung satu kali pada setiap
        periode berdasarkan ID, termasuk jika tercatat di lebih dari satu sumber. Angka mingguan/bulanan berarti orang
        unik yang tercatat hadir setidaknya satu kali pada periode itu, bukan jumlah akumulasi kehadiran harian.
        Persentase = jumlah orang unik hadir dibagi total data guru/siswa, lalu dikali 100.
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-success kpi-card">
                <div class="card-header">
                    <h3 class="card-title">Guru</h3>
                </div>
                <div class="card-body">
                    <div class="metric-stack">
                        <div class="kpi-label">Hari Ini</div>
                        <div class="text-muted mb-1">{{ $teacherPresentToday }} dari {{ $totalTeachers }} guru hadir</div>
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
                        <div class="text-muted mb-1">{{ $teacherPresentThisWeek }} dari {{ $totalTeachers }} guru unik</div>
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
                        <div class="text-muted mb-1">{{ $teacherPresentThisMonth }} dari {{ $totalTeachers }} guru unik</div>
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
                        <div class="text-muted mb-1">{{ $studentPresentToday }} dari {{ $totalStudents }} siswa hadir</div>
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
                        <div class="text-muted mb-1">{{ $studentPresentThisWeek }} dari {{ $totalStudents }} siswa unik</div>
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
                        <div class="text-muted mb-1">{{ $studentPresentThisMonth }} dari {{ $totalStudents }} siswa unik</div>
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

    <p class="section-title mt-2">Keefektifan Belajar</p>
    <div class="alert alert-light border">
        Indikator ini menggunakan persentase siswa unik yang hadir sebagai gambaran keefektifan belajar. Sumbernya
        adalah absensi sekolah (<code>attendances</code>) dan absensi kelas (<code>attendance_details</code>);
        status <code>Hadir</code>, <code>Terlambat</code>, dan <code>Dispen</code> dihitung hadir. Rumus: jumlah siswa unik yang tercatat
        hadir pada periode tersebut dibagi total siswa, lalu dikali 100. Angka mingguan dan bulanan menghitung setiap
        siswa satu kali meskipun hadir pada beberapa hari.
    </div>
    <div class="card card-outline card-primary kpi-card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="metric-stack">
                        <div class="kpi-label">Harian</div>
                        <div class="text-muted mb-1">{{ $studentPresentToday }} dari {{ $totalStudents }} siswa hadir</div>
                        <div class="kpi-value text-info">{{ $studentPresencePercent }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar"
                                style="width: {{ min(100, $studentPresencePercent) }}%"
                                aria-valuenow="{{ $studentPresencePercent }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-stack">
                        <div class="kpi-label">Mingguan</div>
                        <div class="text-muted mb-1">{{ $studentPresentThisWeek }} dari {{ $totalStudents }} siswa unik</div>
                        <div class="kpi-value text-primary">{{ $studentPresencePercentWeek }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar"
                                style="width: {{ min(100, $studentPresencePercentWeek) }}%"
                                aria-valuenow="{{ $studentPresencePercentWeek }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-stack">
                        <div class="kpi-label">Bulanan</div>
                        <div class="text-muted mb-1">{{ $studentPresentThisMonth }} dari {{ $totalStudents }} siswa unik</div>
                        <div class="kpi-value text-warning">{{ $studentPresencePercentMonth }}%</div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar"
                                style="width: {{ min(100, $studentPresencePercentMonth) }}%"
                                aria-valuenow="{{ $studentPresencePercentMonth }}" aria-valuemin="0" aria-valuemax="100">
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
            const studentStatusLists = @json(collect($studentStatusCards)->mapWithKeys(function ($card) {
                    return [$card['key'] => $card['students']];
                })->all());

            $('.status-card').on('click', function() {
                const statusKey = this.dataset.statusKey;
                const students = studentStatusLists[statusKey] || [];
                const tableBody = document.getElementById('studentStatusModalBody');
                tableBody.replaceChildren();
                document.getElementById('studentStatusModalTitle').textContent =
                    `Daftar Siswa ${this.dataset.statusLabel} Hari Ini (${students.length})`;

                if (students.length === 0) {
                    const row = tableBody.insertRow();
                    const cell = row.insertCell();
                    cell.colSpan = 4;
                    cell.className = 'text-center text-muted';
                    cell.textContent = 'Tidak ada siswa dengan status ini hari ini.';
                } else {
                    students.forEach((student, index) => {
                        const row = tableBody.insertRow();
                        row.insertCell().textContent = index + 1;
                        row.insertCell().textContent = student.nis;
                        row.insertCell().textContent = student.name;
                        row.insertCell().textContent = student.classroom;
                    });
                }

                $('#studentStatusModal').modal('show');
            });

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
                            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#343a40', '#6610f2', '#6c757d']
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
