@extends('adminlte::page')

@section('title', $title)
@section('plugins.Chartjs', true)
@section('plugins.Datatables', true)
@section('plugins.Select2', true)

@section('css')
    <style>
        :root {
            --dss-bg: #f4f6f9;
            --dss-card-shadow: 0 0.4rem 1rem rgba(22, 28, 45, 0.08);
            --dss-text: #2f3640;
        }

        [data-theme='dark'] {
            --dss-bg: #1f232b;
            --dss-text: #e2e8f0;
        }

        .content-wrapper {
            background: var(--dss-bg);
        }

        .dss-kpi-card {
            border-radius: 0.85rem;
            box-shadow: var(--dss-card-shadow);
            min-height: 126px;
        }

        .dss-kpi-card .metric {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--dss-text);
        }

        .dss-kpi-card .label {
            color: #6c757d;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .counter-anim {
            transition: all .4s ease;
        }

        .timeline-item {
            border-left: 2px solid #dee2e6;
            padding-left: 0.85rem;
            margin-bottom: 0.75rem;
        }

        .timeline-date {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .attendance-status-card {
            border-width: 0 0 0 4px;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }

        .attendance-status-card:hover,
        .attendance-status-card:focus {
            box-shadow: var(--dss-card-shadow);
            transform: translateY(-2px);
        }

        .attendance-status-count {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.2;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-0">{{ $title }}</h1>
            <p class="text-muted mb-0">Pusat Dukungan Keputusan - Analitik Pancawaluya</p>
        </div>
        <div class="mt-2 mt-md-0 d-flex flex-wrap align-items-center" style="gap:.5rem;">
            @if (!empty($absensiRouteName))
                <a href="{{ route($absensiRouteName, ['mode' => $mode ?? 'ringkas']) }}"
                    class="btn btn-sm btn-outline-secondary">
                    Dashboard Absensi
                </a>
            @endif
            <div class="btn-group btn-group-sm" role="group" aria-label="Mode tampilan dashboard pancawaluya">
                <a href="{{ route($roleRouteName, ['mode' => 'ringkas']) }}"
                    class="btn {{ ($mode ?? 'ringkas') === 'ringkas' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Ringkas
                </a>
                <a href="{{ route($roleRouteName, ['mode' => 'detail']) }}"
                    class="btn {{ ($mode ?? 'ringkas') === 'detail' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Detail
                </a>
            </div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Export analytics">
                <button type="button" class="btn btn-outline-success" id="btnExportPng">PNG</button>
                <button type="button" class="btn btn-outline-success" id="btnExportCsv">CSV</button>
                <button type="button" class="btn btn-outline-success" id="btnExportXlsx">Excel</button>
                <button type="button" class="btn btn-outline-success" id="btnExportPdf">PDF</button>
                <button type="button" class="btn btn-outline-success" id="btnPrintAnalytics">Print</button>
            </div>
            <span class="badge badge-info" id="badgeAutoRefresh">Pembaruan otomatis 60 dtk</span>
            <span class="badge badge-light" id="lastRefresh">-</span>
        </div>
    </div>
@stop

@section('content')
    @php
        $isDetail = ($mode ?? 'ringkas') === 'detail';
    @endphp

    @include('components.dashboard.filter-bar')

    @if ($role === 'kesiswaan')
        <div class="card card-outline card-primary mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Absensi Siswa Hari Ini</h3>
            </div>
            <div class="card-body pb-2">
                <p class="text-muted">
                    Jumlah adalah siswa unik dari absensi sekolah dan absensi kelas oleh guru. Alpa/Bolos adalah satu
                    kategori; siswa dapat muncul pada lebih dari satu kategori jika tercatat dengan status berbeda.
                </p>
                <div class="row">
                    @foreach ($todayAttendanceStatusCards as $statusCard)
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <button type="button"
                                class="card attendance-status-card border-{{ $statusCard['color'] }} w-100 text-left"
                                data-status-key="{{ $statusCard['key'] }}"
                                data-status-label="{{ $statusCard['label'] }}"
                                aria-label="Lihat daftar siswa {{ $statusCard['label'] }}, {{ $statusCard['count'] }} siswa">
                                <span class="card-body p-3">
                                    <span class="d-block font-weight-bold">{{ $statusCard['label'] }}</span>
                                    <span class="d-block attendance-status-count text-{{ $statusCard['color'] }}">
                                        {{ $statusCard['count'] }}
                                    </span>
                                    <small class="text-muted">Klik untuk lihat siswa</small>
                                </span>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="modal fade" id="todayAttendanceStudentsModal" tabindex="-1" role="dialog"
            aria-labelledby="todayAttendanceStudentsTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="todayAttendanceStudentsTitle">Daftar Siswa</h5>
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
                                <tbody id="todayAttendanceStudentsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-outline card-secondary mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Aksi Cepat</h3>
        </div>
        <div class="card-body d-flex flex-wrap" style="gap:.5rem;">
            @if (in_array($role, ['admin', 'guru', 'wali_kelas', 'kesiswaan'], true))
                <a href="{{ route('pancawaluya.reward-transactions.create') }}" class="btn btn-sm btn-success">Tambah
                    Penghargaan</a>
            @endif
            @if (in_array($role, ['admin', 'wali_kelas', 'bk', 'kesiswaan'], true))
                <a href="{{ route('pancawaluya.violation-transactions.create') }}" class="btn btn-sm btn-danger">Tambah
                    Pelanggaran</a>
            @endif
            @if ($role === 'kesiswaan')
                <a href="{{ route('kesiswaan.reports.student-attendance') }}" class="btn btn-sm btn-primary">
                    Laporan Absensi Siswa
                </a>
                <a href="{{ route('kesiswaan.reports.class-effectiveness') }}" class="btn btn-sm btn-outline-primary">
                    Efektivitas Belajar Kelas
                </a>
            @endif
            <a href="{{ route('pancawaluya.transaction-histories.index') }}" class="btn btn-sm btn-info">Buka Linimasa</a>
            @if ($role === 'siswa')
                <a href="{{ route('siswa.identity.edit') }}" class="btn btn-sm btn-primary">Perbarui Profil</a>
            @endif
        </div>
    </div>

    @include('components.dashboard.kpi-cards')
    @include('components.dashboard.alerts-and-recommendations')
    @if ($isDetail)
        @include('components.dashboard.chart-grid')
        @include('components.dashboard.ranking-grid')
        @include('components.dashboard.advanced-analytics')

        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title mb-0">Aktivitas Terbaru</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" id="tblActivities" width="100%">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Siswa</th>
                            <th>Aksi</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Sumber</th>
                            <th>Pelaku</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    @endif
@stop

@section('js')
    <script>
        window.dashboardRole = @json($role);
        window.dashboardMode = @json($mode ?? 'ringkas');
        @if ($role === 'kesiswaan')
            window.todayAttendanceStatusLists = @json(collect($todayAttendanceStatusCards)->mapWithKeys(function ($card) {
                    return [$card['key'] => $card['students']];
                })->all());

            $(function() {
                $('.attendance-status-card').on('click', function() {
                    const students = window.todayAttendanceStatusLists[this.dataset.statusKey] || [];
                    const tableBody = document.getElementById('todayAttendanceStudentsBody');
                    tableBody.replaceChildren();
                    document.getElementById('todayAttendanceStudentsTitle').textContent =
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

                    $('#todayAttendanceStudentsModal').modal('show');
                });
            });
        @endif
        window.dashboardEndpoints = {
            data: @json(route('dashboard.dss.data')),
            options: @json(route('dashboard.dss.options')),
            activities: @json(route('dashboard.dss.activities')),
            exportCsv: @json(route('dashboard.dss.export', ['format' => 'csv'])),
            exportXlsx: @json(route('dashboard.dss.export', ['format' => 'xlsx'])),
            exportPdf: @json(route('dashboard.dss.export', ['format' => 'pdf'])),
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('dashboard.dss.script')
@stop

@section('footer')
    @include('components.app-footer')
@stop
