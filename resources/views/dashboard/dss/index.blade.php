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
