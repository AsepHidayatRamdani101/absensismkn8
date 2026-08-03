@extends('adminlte::page')

@include('guru.partials.mobile-ux')

@section('title', 'Absensi Siswa Oleh Guru')

@section('plugins.Datatables', true)

@section('css')
    <style>
        #guruAttendancePage .btn {
            border-radius: .5rem;
            font-weight: 600;
        }

        #bulkActionBar {
            background: #f8fafc;
            border-left: 4px solid #007bff;
        }

        #selectionQuickTools {
            background: #ffffff;
        }

        #selectionQuickTools .btn {
            border-radius: .45rem;
            font-weight: 600;
        }

        #statusSummaryBar {
            background: #f8fafc;
        }

        #statusSummaryBar .summary-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 600;
            border: 1px solid transparent;
        }

        #statusSummaryBar .summary-chip strong {
            font-size: .82rem;
        }

        #statusSummaryBar .summary-hadir {
            color: #166534;
            background: #ecfdf3;
            border-color: #bbf7d0;
        }

        #statusSummaryBar .summary-sakit {
            color: #92400e;
            background: #fffbeb;
            border-color: #fde68a;
        }

        #statusSummaryBar .summary-izin {
            color: #1e3a8a;
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        #statusSummaryBar .summary-dispen {
            color: #312e81;
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        #statusSummaryBar .summary-alpa {
            color: #991b1b;
            background: #fef2f2;
            border-color: #fecaca;
        }

        #statusSummaryBar .summary-terlambat {
            color: #9a3412;
            background: #fff7ed;
            border-color: #fed7aa;
        }

        #statusSummaryBar .summary-belum {
            color: #334155;
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        #tableGuruAttendanceDetails thead th {
            white-space: nowrap;
            vertical-align: middle;
            background: #f8fafc;
        }

        #tableGuruAttendanceDetails tbody td {
            vertical-align: middle;
        }

        #tableGuruAttendanceDetails .col-check {
            width: 56px;
            min-width: 56px;
            text-align: center;
        }

        #tableGuruAttendanceDetails input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        #tableGuruAttendanceDetails .attendance-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        #tableGuruAttendanceDetails .attendance-actions .btn {
            min-width: 76px;
            width: 100%;
        }

        #tableGuruAttendanceDetails .status-badge {
            min-width: 96px;
            text-align: center;
            font-weight: 600;
        }

        #guruAttendanceTableWrap {
            position: relative;
            min-height: 220px;
        }

        #guruAttendanceTableWrap.table-hidden-initial .table-responsive {
            visibility: hidden;
        }

        #guruAttendanceTableLoading {
            position: absolute;
            inset: 0;
            z-index: 15;
            background: rgba(255, 255, 255, .9);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            font-weight: 600;
            color: #334155;
        }

        #guruAttendanceTableLoading.d-none {
            display: none !important;
        }

        #guruAttendancePage .mobile-only {
            display: none;
        }

        @media (max-width: 767.98px) {
            #tableGuruAttendanceDetails .dtr-details .dtr-title {
                display: none !important;
            }

            #guruAttendancePage .mobile-only {
                display: block;
            }

            #guruAttendancePage .desktop-only {
                display: none !important;
            }

            #guruAttendancePage .btn {
                min-height: 38px;
                font-size: .79rem;
                letter-spacing: .01em;
                padding: .38rem .62rem;
            }

            #guruAttendancePage .btn.btn-xs {
                min-height: 36px;
                font-size: .76rem;
                padding: .34rem .56rem;
            }

            #guruAttendancePage .filter-submit-btn {
                width: 100%;
            }

            #bulkActionBar {
                position: sticky;
                bottom: 0;
                z-index: 30;
                border-left: none;
                border-top: 2px solid #007bff;
                box-shadow: 0 -6px 16px rgba(15, 23, 42, .12);
            }

            #bulkActionBar small {
                width: 100%;
                margin-bottom: .35rem;
            }

            #bulkActionBar .bulk-status-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
                gap: .45rem;
            }

            #bulkActionBar .bulk-status-grid .btn {
                width: 100%;
            }

            #selectionQuickTools {
                position: sticky;
                top: 0;
                z-index: 20;
            }

            #selectionQuickTools .quick-tools-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
                gap: .45rem;
            }

            #selectionQuickTools .quick-tools-grid .btn {
                width: 100%;
            }

            #guruAttendancePage .table-responsive {
                padding: .75rem !important;
            }

            #tableGuruAttendanceDetails {
                border-collapse: separate;
                border-spacing: 0 .55rem;
            }

            #tableGuruAttendanceDetails thead {
                display: none;
            }

            #tableGuruAttendanceDetails tbody {
                display: block;
            }

            #tableGuruAttendanceDetails tbody tr {
                display: block;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: .75rem;
                box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
                margin-bottom: .65rem;
                overflow: hidden;
            }

            #tableGuruAttendanceDetails tbody td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                padding: .6rem .75rem;
                border: 0;
                border-bottom: 1px solid #f1f5f9;
                min-height: 42px;
            }

            #tableGuruAttendanceDetails tbody td::before {
                color: #64748b;
                font-size: .73rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .03em;
                flex-shrink: 0;
            }

            #tableGuruAttendanceDetails tbody td:nth-child(1)::before {
                content: 'Pilih';
            }

            #tableGuruAttendanceDetails tbody td:nth-child(2)::before {
                content: 'No';
            }

            #tableGuruAttendanceDetails tbody td:nth-child(3)::before {
                content: 'Nama';
            }

            #tableGuruAttendanceDetails tbody td:nth-child(4)::before {
                content: 'Kelas';
            }

            #tableGuruAttendanceDetails tbody td:nth-child(5)::before {
                content: 'Status';
            }

            #tableGuruAttendanceDetails tbody td:nth-child(6)::before {
                content: 'Aksi';
            }

            #tableGuruAttendanceDetails tbody td:last-child {
                border-bottom: 0;
            }

            #tableGuruAttendanceDetails .col-check {
                width: auto;
                min-width: 0;
                text-align: left;
            }

            #tableGuruAttendanceDetails .col-check input[type='checkbox'] {
                margin-left: auto;
            }

            #tableGuruAttendanceDetails .attendance-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(88px, 1fr));
                width: 100%;
                gap: .4rem;
            }

            #tableGuruAttendanceDetails .status-badge {
                min-width: 120px;
                margin-left: auto;
            }
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Absensi Siswa Oleh Guru</h1>
            <p class="text-muted mb-0">Tampilan minimalis untuk input cepat status siswa per kelas hari ini.</p>
        </div>
        <span class="badge badge-light border px-3 py-2 mt-2 mt-md-0">
            <span id="guruRealtimeDate">{{ $today->format('d M Y') }}</span>
            -
            <span id="guruRealtimeDay">{{ $todayDayName }}</span>
            <span class="ml-2" id="guruRealtimeClock"></span>
        </span>
    </div>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($isWeekendHoliday)
        <div class="alert alert-info">
            Hari {{ $todayDayName }} otomatis libur. Absensi siswa tidak dibuka.
        </div>
    @endif

    <div id="guruAttendancePage">

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('guru.attendance-details.index') }}" id="guruAttendanceFilterForm">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label for="tanggal" class="mb-1">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control"
                                value="{{ $hasFilter ? $tanggalFilter : now()->toDateString() }}"
                                max="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label for="classroom_id" class="mb-1">Filter Kelas (sesuai jadwal)</label>
                            <select name="classroom_id" id="classroom_id" class="form-control">
                                <option value="0">Semua Kelas</option>
                                @foreach ($classOptions as $classroom)
                                    <option value="{{ $classroom->id }}" @selected($selectedClassroomId === (int) $classroom->id)>
                                        {{ $classroom->nama_kelas }} ({{ $classroom->kode_kelas }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block mb-2">Filter berjalan otomatis: tanggal -> kelas ->
                                tabel.</small>
                            <a href="{{ route('guru.attendance-details.index') }}"
                                class="btn btn-outline-secondary ml-1">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-info" id="filterHintAlert" @if ($hasFilter) style="display:none;" @endif>
            <i class="fas fa-filter mr-1"></i>
            Pilih tanggal untuk menampilkan data absensi siswa.
        </div>

        <div class="card">
            <div class="card-body p-0">
                <form id="bulkAttendanceForm" method="POST" action="{{ route('guru.attendance-details.bulk-submit') }}"
                    class="d-none">
                    @csrf
                    <input type="hidden" name="tanggal" id="bulk_tanggal" value="{{ $hasFilter ? $tanggalFilter : '' }}">
                    <input type="hidden" name="classroom_id" id="bulk_classroom_id" value="{{ $selectedClassroomId }}">
                    <input type="hidden" name="bulk_status" id="bulk_status" value="">
                </form>

                <form id="countPresentForm" method="POST" action="{{ route('guru.attendance-details.count-hadir') }}"
                    class="d-none">
                    @csrf
                    <input type="hidden" name="tanggal" id="count_tanggal" value="{{ $hasFilter ? $tanggalFilter : '' }}">
                    <input type="hidden" name="classroom_id" id="count_classroom_id" value="{{ $selectedClassroomId }}">
                </form>

                <div id="statusSummaryBar" class="px-3 py-2 border-bottom"
                    @if (!$hasFilter || $isWeekendHoliday) style="display:none;" @endif>
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.55rem;">
                        <small class="text-muted mb-0">Ringkasan status (seluruh data terfilter)</small>
                        <div class="d-flex align-items-center" style="gap:.55rem;">
                            <small class="text-muted mb-0 d-none" id="summaryLoadingState">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Menghitung total terfilter...
                            </small>
                            <small class="text-muted mb-0">Total terfilter: <strong id="summaryTotalRows">0</strong></small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap mt-2" style="gap:.4rem;">
                        <span class="summary-chip summary-hadir">Hadir <strong id="summaryHadir">0</strong></span>
                        <span class="summary-chip summary-sakit">Sakit <strong id="summarySakit">0</strong></span>
                        <span class="summary-chip summary-izin">Izin <strong id="summaryIzin">0</strong></span>
                        <span class="summary-chip summary-alpa">Alpa <strong id="summaryAlpa">0</strong></span>
                        <span class="summary-chip summary-terlambat">Terlambat <strong
                                id="summaryTerlambat">0</strong></span>
                        <span class="summary-chip summary-belum">Belum <strong id="summaryBelum">0</strong></span>
                    </div>
                </div>

                <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap"
                    id="bulkActionBar" style="gap:.5rem; display: none;">
                    <small class="text-muted">
                        <span id="selectedCountLabel">0</span> siswa dipilih. Pilih aksi massal:
                        Hadir / Sakit / Izin / Dispen / Alpa / Terlambat.
                    </small>
                    <div class="d-flex flex-wrap bulk-status-grid" style="gap: .4rem;">
                        <button type="button" class="btn btn-success btn-xs btn-bulk-status js-attendance-action"
                            data-status="Hadir">
                            Hadir
                        </button>
                        <button type="button" class="btn btn-warning btn-xs btn-bulk-status js-attendance-action"
                            data-status="Sakit">
                            Sakit
                        </button>
                        <button type="button" class="btn btn-info btn-xs btn-bulk-status js-attendance-action"
                            data-status="Izin">
                            Izin
                        </button>
                        <button type="button" class="btn btn-primary btn-xs btn-bulk-status js-attendance-action"
                            data-status="Dispen">
                            Dispen
                        </button>
                        <button type="button" class="btn btn-danger btn-xs btn-bulk-status js-attendance-action"
                            data-status="Alpa">
                            Alpa
                        </button>
                        <button type="button" class="btn btn-warning btn-xs btn-bulk-status js-attendance-action"
                            data-status="Terlambat">
                            Terlambat
                        </button>
                    </div>
                </div>

                <div class="px-3 pt-2 mobile-only">
                    <small class="text-muted">Mode mobile aktif: pilih beberapa siswa lalu gunakan aksi massal di bagian
                        bawah.</small>
                </div>

                <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center flex-wrap"
                    id="selectionQuickTools" style="gap:.5rem;">
                    <small class="text-muted mb-0">Pemilihan cepat siswa</small>
                    <div class="d-flex flex-wrap quick-tools-grid" style="gap: .4rem;">
                        <button type="button" id="btnSelectVisibleRows"
                            class="btn btn-outline-primary btn-xs js-attendance-action">
                            Pilih Semua Terlihat
                        </button>
                        <button type="button" id="btnClearSelectedRows"
                            class="btn btn-outline-secondary btn-xs js-attendance-action">
                            Bersihkan Pilihan
                        </button>
                        <button type="button" id="btnCountPresent"
                            class="btn btn-outline-success btn-xs js-attendance-action">
                            Hitung Hadir
                        </button>
                    </div>
                </div>

                <div id="guruAttendanceTableWrap" class="p-3 pt-2">
                    <div id="guruAttendanceTableLoading">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Memuat data absensi siswa...
                    </div>
                    <div class="table-responsive">
                        <table id="tableGuruAttendanceDetails" class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th class="col-check">
                                        <input type="checkbox" id="check_all_students" class="js-attendance-action">
                                    </th>
                                    <th width="5%">No</th>
                                    <th>Nama Siswa</th>
                                    <th width="20%">Kelas</th>
                                    <th width="18%">Status Saat Ini</th>
                                    <th width="30%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function() {
            const dateElement = document.getElementById('guruRealtimeDate');
            const dayElement = document.getElementById('guruRealtimeDay');
            const clockElement = document.getElementById('guruRealtimeClock');

            if (!dateElement && !dayElement && !clockElement) {
                return;
            }

            const dateFormatter = new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                timeZone: 'Asia/Jakarta'
            });

            const timeFormatter = new Intl.DateTimeFormat('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Jakarta'
            });

            const dayFormatter = new Intl.DateTimeFormat('id-ID', {
                weekday: 'long',
                timeZone: 'Asia/Jakarta'
            });

            const updateRealtimeHeader = () => {
                const currentDate = new Date();

                if (dateElement) {
                    dateElement.textContent = dateFormatter.format(currentDate);
                }

                if (dayElement) {
                    dayElement.textContent = dayFormatter.format(currentDate);
                }

                if (clockElement) {
                    clockElement.textContent = timeFormatter.format(currentDate) + ' WIB';
                }
            };

            updateRealtimeHeader();
            setInterval(updateRealtimeHeader, 1000);
        })();

        $(function() {
            const $tableWrap = $('#guruAttendanceTableWrap');
            const $tableLoading = $('#guruAttendanceTableLoading');
            const $tanggal = $('#tanggal');
            const $classroom = $('#classroom_id');
            const $bulkTanggal = $('#bulk_tanggal');
            const $bulkClassroom = $('#bulk_classroom_id');
            const $countTanggal = $('#count_tanggal');
            const $countClassroom = $('#count_classroom_id');
            const $filterHintAlert = $('#filterHintAlert');
            const $statusSummaryBar = $('#statusSummaryBar');
            const $summaryLoadingState = $('#summaryLoadingState');

            const hasFilterInitial = @json($hasFilter);
            const weekendFilterMessage = 'Tanggal jatuh pada hari libur (Sabtu/Minggu).';
            const noFilterMessage = 'Pilih tanggal untuk menampilkan data absensi siswa.';
            const noDataMessage = 'Tidak ada data siswa dari kelas yang memiliki jadwal Anda pada tanggal ini.';

            let isWeekendDate = false;
            let isSyncingClassOptions = false;
            let latestServerSummary = null;
            let firstTableLoaded = false;

            if ($tableWrap.length) {
                $tableWrap.addClass('table-hidden-initial');
            }

            let table = $('#tableGuruAttendanceDetails').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                scrollX: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('guru.attendance-details.datatable') }}",
                    data: function(d) {
                        d.tanggal = $tanggal.val() || '';
                        d.classroom_id = $classroom.val() || '0';
                    },
                    dataSrc: function(json) {
                        latestServerSummary = (json && json.summary) ? json.summary : null;
                        return (json && json.data) ? json.data : [];
                    }
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'no',
                        name: 'no',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'kelas',
                        name: 'kelas'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        searchable: false,
                        orderable: false
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json',
                    emptyTable: hasFilterInitial ? noDataMessage : noFilterMessage
                },
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }, {
                    orderable: false,
                    targets: 5
                }],
                initComplete: function() {
                    firstTableLoaded = true;
                    $tableWrap.removeClass('table-hidden-initial');
                    $tableLoading.addClass('d-none');
                }
            });

            table.on('processing.dt', function(e, settings, isProcessing) {
                $tableLoading.toggleClass('d-none', !isProcessing);
                toggleSummaryLoadingState(isProcessing);
            });

            function updateHiddenFilterInputs() {
                const tanggalValue = $tanggal.val() || '';
                const classroomValue = $classroom.val() || '0';

                $bulkTanggal.val(tanggalValue);
                $bulkClassroom.val(classroomValue);
                $countTanggal.val(tanggalValue);
                $countClassroom.val(classroomValue);
            }

            function toggleAttendanceActions() {
                const hasDate = ($tanggal.val() || '') !== '';
                const disabled = !hasDate || isWeekendDate;

                $('.js-attendance-action').prop('disabled', disabled);

                if (disabled) {
                    $('#check_all_students').prop('checked', false);
                    $('.check-student').prop('checked', false);
                    updateBulkActionBar();
                }
            }

            function resetStatusSummary() {
                $('#summaryTotalRows').text('0');
                $('#summaryHadir').text('0');
                $('#summarySakit').text('0');
                $('#summaryIzin').text('0');
                $('#summaryAlpa').text('0');
                $('#summaryTerlambat').text('0');
                $('#summaryBelum').text('0');
            }

            function toggleSummaryLoadingState(isLoading) {
                const canShow = ($tanggal.val() || '').trim() !== '' && !isWeekendDate &&
                    $statusSummaryBar.is(':visible');

                $summaryLoadingState.toggleClass('d-none', !(isLoading && canShow));
            }

            function updateStatusSummaryFromTable() {
                if (latestServerSummary) {
                    $('#summaryTotalRows').text(String(latestServerSummary.total ?? 0));
                    $('#summaryHadir').text(String(latestServerSummary.hadir ?? 0));
                    $('#summarySakit').text(String(latestServerSummary.sakit ?? 0));
                    $('#summaryIzin').text(String(latestServerSummary.izin ?? 0));
                    $('#summaryAlpa').text(String(latestServerSummary.alpa ?? 0));
                    $('#summaryTerlambat').text(String(latestServerSummary.terlambat ?? 0));
                    $('#summaryBelum').text(String(latestServerSummary.belum ?? 0));
                    return;
                }

                const rows = table.rows({
                    page: 'current'
                }).data().toArray();

                const counts = {
                    Hadir: 0,
                    Sakit: 0,
                    Izin: 0,
                    Alpa: 0,
                    Terlambat: 0,
                    Belum: 0,
                };

                rows.forEach((row) => {
                    const rawStatus = (row && row.raw_status) ? String(row.raw_status).trim() : '';

                    if (rawStatus === 'Hadir') {
                        counts.Hadir++;
                    } else if (rawStatus === 'Sakit') {
                        counts.Sakit++;
                    } else if (rawStatus === 'Izin') {
                        counts.Izin++;
                    } else if (rawStatus === 'Dispen') {
                        counts.Hadir++; // Dispen dianggap Hadir
                    } else if (rawStatus === 'Alpha' || rawStatus === 'Alpa') {
                        counts.Alpa++;
                    } else if (rawStatus === 'Terlambat') {
                        counts.Terlambat++;
                    } else {
                        counts.Belum++;
                    }
                });

                $('#summaryTotalRows').text(String(rows.length));
                $('#summaryHadir').text(String(counts.Hadir));
                $('#summarySakit').text(String(counts.Sakit));
                $('#summaryIzin').text(String(counts.Izin));
                $('#summaryAlpa').text(String(counts.Alpa));
                $('#summaryTerlambat').text(String(counts.Terlambat));
                $('#summaryBelum').text(String(counts.Belum));
            }

            function toggleStatusSummary() {
                const hasDate = ($tanggal.val() || '').trim() !== '';

                if (!hasDate || isWeekendDate) {
                    latestServerSummary = null;
                    $statusSummaryBar.hide();
                    toggleSummaryLoadingState(false);
                    resetStatusSummary();
                    return;
                }

                $statusSummaryBar.show();
                toggleSummaryLoadingState(false);
            }

            function updateFilterHint() {
                const tanggalValue = ($tanggal.val() || '').trim();

                if (tanggalValue === '') {
                    $filterHintAlert.removeClass('alert-warning').addClass('alert-info').html(
                        '<i class="fas fa-filter mr-1"></i> ' + noFilterMessage
                    ).show();
                    return;
                }

                if (isWeekendDate) {
                    $filterHintAlert.removeClass('alert-info').addClass('alert-warning').html(
                        '<i class="fas fa-calendar-times mr-1"></i> ' + weekendFilterMessage
                    ).show();
                    return;
                }

                $filterHintAlert.hide();
            }

            function reloadTableWithLoading() {
                if ($tableWrap.length) {
                    $tableWrap.addClass('table-hidden-initial');
                    $tableLoading.removeClass('d-none');
                }

                table.ajax.reload(function() {
                    $tableWrap.removeClass('table-hidden-initial');
                    $tableLoading.addClass('d-none');
                    forceDesktopTableMode();
                }, true);
            }

            function syncAttendanceFilterState() {
                updateHiddenFilterInputs();
                toggleAttendanceActions();
                toggleStatusSummary();
                updateFilterHint();
            }

            function loadClassOptions(preferredClassroomId = null) {
                const tanggalValue = ($tanggal.val() || '').trim();

                if (tanggalValue === '') {
                    latestServerSummary = null;
                    isWeekendDate = false;
                    isSyncingClassOptions = true;
                    $classroom.html('<option value="0">Semua Kelas</option>').val('0');
                    isSyncingClassOptions = false;
                    table.settings()[0].oLanguage.emptyTable = noFilterMessage;
                    syncAttendanceFilterState();
                    reloadTableWithLoading();
                    return;
                }

                const query = new URLSearchParams({
                    tanggal: tanggalValue,
                    classroom_id: preferredClassroomId ?? ($classroom.val() || '0')
                });

                fetch("{{ route('guru.attendance-details.class-options') }}?" + query.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then((response) => response.json())
                    .then((payload) => {
                        latestServerSummary = null;
                        isWeekendDate = !!payload.is_weekend_holiday;

                        const options = Array.isArray(payload.options) ? payload.options : [];
                        const selected = String(payload.selected ?? 0);

                        isSyncingClassOptions = true;
                        $classroom.empty();
                        $classroom.append('<option value="0">Semua Kelas</option>');

                        options.forEach((item) => {
                            const optionLabel = `${item.nama_kelas} (${item.kode_kelas})`;
                            $classroom.append(new Option(optionLabel, String(item.id), false, false));
                        });

                        $classroom.val(selected);
                        isSyncingClassOptions = false;

                        table.settings()[0].oLanguage.emptyTable = isWeekendDate ? weekendFilterMessage :
                            noDataMessage;
                        syncAttendanceFilterState();
                        reloadTableWithLoading();
                    })
                    .catch(() => {
                        latestServerSummary = null;
                        isWeekendDate = false;
                        table.settings()[0].oLanguage.emptyTable = noDataMessage;
                        syncAttendanceFilterState();
                        reloadTableWithLoading();
                    });
            }

            $('#guruAttendanceFilterForm').on('submit', function(event) {
                event.preventDefault();
                loadClassOptions();
            });

            $tanggal.on('change', function() {
                loadClassOptions();
            });

            $classroom.on('change', function() {
                if (isSyncingClassOptions) {
                    return;
                }

                syncAttendanceFilterState();
                table.settings()[0].oLanguage.emptyTable = isWeekendDate ? weekendFilterMessage :
                    noDataMessage;
                reloadTableWithLoading();
            });

            $('#btnCountPresent').on('click', function() {
                if (($tanggal.val() || '').trim() === '') {
                    alert('Pilih tanggal terlebih dahulu.');
                    return;
                }

                $('#countPresentForm').submit();
            });

            function forceDesktopTableMode() {
                let $table = $('#tableGuruAttendanceDetails');
                $table.removeClass('dtr-inline collapsed');
                $table.find('tbody tr.child').remove();
                $table.find('tbody tr').removeClass('parent');

                if (window.matchMedia('(max-width: 767.98px)').matches) {
                    $table.closest('.dataTables_wrapper').find('.dtr-title').filter(function() {
                        return $(this).text().trim().toLowerCase() === 'kolom 1';
                    }).remove();
                }
            }

            table.on('draw', forceDesktopTableMode);
            table.on('draw', updateStatusSummaryFromTable);
            forceDesktopTableMode();

            // Hindari state checkbox tersimpan dari browser agar aksi massal tidak muncul saat awal load.
            $('#check_all_students, .check-student').prop('checked', false);

            $('#check_all_students').on('change', function() {
                let checked = $(this).is(':checked');
                $('.check-student').prop('checked', checked);
                updateBulkActionBar();
            });

            $('#btnSelectVisibleRows').on('click', function() {
                table.rows({
                    search: 'applied'
                }).nodes().to$().find('.check-student:not(:disabled)').prop('checked', true);

                $('#check_all_students').prop(
                    'checked',
                    $('.check-student:not(:disabled)').length > 0 &&
                    $('.check-student:not(:disabled):checked').length === $(
                        '.check-student:not(:disabled)').length
                );

                updateBulkActionBar();
            });

            $('#btnClearSelectedRows').on('click', function() {
                $('.check-student').prop('checked', false);
                $('#check_all_students').prop('checked', false);
                updateBulkActionBar();
            });

            $(document).on('change', '.check-student', function() {
                let total = $('.check-student').length;
                let checked = $('.check-student:checked').length;

                $('#check_all_students').prop('checked', total > 0 && total === checked);
                updateBulkActionBar();
            });

            function updateBulkActionBar() {
                let selectedCount = $('.check-student:checked').length;

                if (selectedCount > 0) {
                    $('#selectedCountLabel').text(selectedCount);
                    $('#bulkActionBar').show();
                } else {
                    $('#bulkActionBar').hide();
                }
            }

            $('.btn-bulk-status').on('click', function() {
                let selectedCount = $('.check-student:checked').length;

                if (selectedCount === 0) {
                    alert('Pilih minimal satu siswa terlebih dahulu.');
                    return;
                }

                $('#bulk_status').val($(this).data('status'));
                $('#bulkAttendanceForm').submit();
            });

            updateBulkActionBar();
            resetStatusSummary();
            syncAttendanceFilterState();

            // Tanggal selalu terisi (hari ini), langsung muat opsi kelas dan tabel
            loadClassOptions("{{ (int) $selectedClassroomId }}");
        });
    </script>

    @if (session('success') && session('offer_agenda_redirect'))
        <script>
            $(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: @json(session('success')),
                    timer: 2000,
                    showConfirmButton: false,
                });
            });
        </script>
    @endif
@stop
