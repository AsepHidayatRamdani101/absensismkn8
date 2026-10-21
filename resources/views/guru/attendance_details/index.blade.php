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

        #tableGuruAttendanceDetails th:first-child,
        #tableGuruAttendanceDetails td:first-child {
            text-align: center;
        }

        #tableGuruAttendanceDetails .attendance-actions {
            display: inline-block;
        }

        #tableGuruAttendanceDetails .attendance-actions .btn {
            min-width: 88px;
            width: auto;
            text-align: left;
        }

        #tableGuruAttendanceDetails .attendance-actions .dropdown-menu {
            min-width: 10rem;
            z-index: 50;
        }

        #tableGuruAttendanceDetails .attendance-actions .dropdown-item {
            padding: .55rem 1rem;
            white-space: nowrap;
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

            #guruAttendancePage .filter-actions {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: .5rem;
            }

            #guruAttendancePage .filter-actions .btn {
                width: 100%;
                margin: 0 !important;
            }

            #guruAttendancePage .filter-help {
                margin-bottom: .25rem;
            }

            #guruAttendancePage .table-responsive {
                padding: .5rem !important;
                overflow-x: auto !important;
            }

            #tableGuruAttendanceDetails {
                display: table !important;
                width: 100% !important;
                min-width: 420px;
                table-layout: fixed;
                border-collapse: collapse;
            }

            #tableGuruAttendanceDetails thead {
                display: table-header-group !important;
            }

            #tableGuruAttendanceDetails tbody tr {
                display: table-row !important;
            }

            #tableGuruAttendanceDetails th,
            #tableGuruAttendanceDetails td {
                display: table-cell !important;
                vertical-align: middle;
            }

            #tableGuruAttendanceDetails th:first-child,
            #tableGuruAttendanceDetails td:first-child {
                width: 56px;
                white-space: nowrap;
                text-align: center;
            }

            #tableGuruAttendanceDetails th:last-child,
            #tableGuruAttendanceDetails td:last-child {
                width: 140px;
                white-space: nowrap;
            }

            #tableGuruAttendanceDetails tbody td {
                padding: .5rem .4rem;
            }

            #tableGuruAttendanceDetails tbody td:nth-child(2) {
                white-space: normal;
                overflow-wrap: break-word;
            }

            #tableGuruAttendanceDetails .attendance-actions {
                display: inline-block;
            }

            #tableGuruAttendanceDetails .attendance-actions .btn {
                min-height: 30px;
                min-width: 88px;
                max-width: 100%;
                font-size: .72rem;
                padding: .2rem .4rem;
                white-space: nowrap;
            }

            #tableGuruAttendanceDetails .attendance-actions .dropdown-menu {
                min-width: 11rem;
            }
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Absensi Siswa Oleh Guru</h1>
            <p class="text-muted mb-0">Pilih tanggal dan kelas untuk mengisi absensi siswa.</p>
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
            {{ $schoolHolidayMessage ?? "Hari {$todayDayName} otomatis libur." }}
            Absensi siswa tidak dibuka.
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
                                <option value="0">Pilih kelas</option>
                                @foreach ($classOptions as $classroom)
                                    <option value="{{ $classroom->id }}" @selected($selectedClassroomId === (int) $classroom->id)>
                                        {{ $classroom->nama_kelas }} ({{ $classroom->kode_kelas }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 filter-actions">
                            <small class="text-muted d-block mb-2 filter-help">
                                Pilih tanggal dan kelas untuk menampilkan maksimal 50 siswa.
                            </small>
                            <a href="{{ route('guru.attendance-details.index') }}"
                                class="btn btn-outline-secondary ml-1">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-info" id="filterHintAlert" @if ($hasFilter) style="display:none;" @endif>
            <i class="fas fa-filter mr-1"></i>
            Pilih tanggal dan kelas untuk menampilkan data absensi siswa.
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div id="statusSummaryBar" class="px-3 py-2 border-bottom"
                    @if (!$hasFilter || $selectedClassroomId === 0 || $isWeekendHoliday) style="display:none;" @endif>
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

                <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center flex-wrap"
                    id="selectionQuickTools" style="gap:.5rem; display:none;">
                    <small class="text-muted mb-0">Pilih status setiap siswa, lalu simpan perubahan.</small>
                    <button type="button" id="btnSaveAttendance" class="btn btn-success btn-sm js-attendance-action" disabled>
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>

                <div id="guruAttendanceTableWrap" class="p-3 pt-2"
                    @if ($selectedClassroomId === 0) style="display:none;" @endif>
                    <div id="guruAttendanceTableLoading">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Memuat data absensi siswa...
                    </div>
                    <div class="table-responsive">
                        <table id="tableGuruAttendanceDetails" class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">No</th>
                                    <th>Nama</th>
                                    <th style="width: 20%;">Aksi</th>
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
                const selectedDateValue = document.getElementById('tanggal')?.value;
                const selectedDate = selectedDateValue ?
                    new Date(`${selectedDateValue}T00:00:00`) :
                    currentDate;

                if (dateElement) {
                    dateElement.textContent = dateFormatter.format(selectedDate);
                }

                if (dayElement) {
                    dayElement.textContent = dayFormatter.format(selectedDate);
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
            const $btnSaveAttendance = $('#btnSaveAttendance');
            const $filterHintAlert = $('#filterHintAlert');
            const $statusSummaryBar = $('#statusSummaryBar');
            const $summaryLoadingState = $('#summaryLoadingState');

            const selectedClassroomInitial = @json($selectedClassroomId > 0);
            const weekendFilterMessage = 'Tanggal jatuh pada hari libur (Sabtu/Minggu).';
            const noFilterMessage = 'Pilih tanggal dan kelas untuk menampilkan data absensi siswa.';
            const noDataMessage = 'Tidak ada data siswa dari kelas yang memiliki jadwal Anda pada tanggal ini.';

            let isWeekendDate = false;
            let nonSchoolDayMessage = @json($schoolHolidayMessage ?? 'Tanggal jatuh pada hari libur (Sabtu/Minggu).');
            let isSyncingClassOptions = false;
            let isLoadingClassOptions = false;
            let isSavingAttendance = false;
            let latestServerSummary = null;
            let activeDraftKey = '';
            let stagedStatuses = new Map();
            let originalStatuses = new Map();
            let dirtyStudentIds = new Set();
            const stagedStatusesByFilter = new Map();
            if ($tableWrap.length) {
                $tableWrap.addClass('table-hidden-initial');
            }

            let table = $('#tableGuruAttendanceDetails').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                autoWidth: false,
                searching: true,
                pageLength: 50,
                lengthChange: false,
                ajax: {
                    url: "{{ route('guru.attendance-details.datatable') }}",
                    data: function(d) {
                        d.tanggal = $tanggal.val() || '';
                        d.classroom_id = $classroom.val() || '0';
                    },
                    dataSrc: function(json) {
                        latestServerSummary = (json && json.summary) ? json.summary : null;
                        const rows = (json && json.data) ? json.data : [];
                        rows.forEach((row) => {
                            const studentId = String(row.student_id);
                            if (!originalStatuses.has(studentId)) {
                                originalStatuses.set(studentId, row.raw_status
                                    ? normalizeAttendanceStatus(row.raw_status)
                                    : 'Belum');
                            }
                            if (!stagedStatuses.has(studentId)) {
                                stagedStatuses.set(studentId, normalizeAttendanceStatus(row.raw_status));
                            }
                        });
                        updateSaveButtonState();
                        return rows;
                    }
                },
                columns: [{
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
                        data: 'aksi',
                        name: 'aksi',
                        searchable: false,
                        orderable: false
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json',
                    emptyTable: selectedClassroomInitial ? noDataMessage : noFilterMessage
                },
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }, {
                    orderable: false,
                    targets: 2
                }],
                initComplete: function() {
                    $tableWrap.removeClass('table-hidden-initial');
                    $tableLoading.addClass('d-none');
                }
            });

            table.on('processing.dt', function(e, settings, isProcessing) {
                $tableLoading.toggleClass('d-none', !isProcessing);
                toggleSummaryLoadingState(isProcessing);
            });

            function normalizeAttendanceStatus(status) {
                if (!status) {
                    return 'Hadir';
                }

                return status === 'Alpha' ? 'Alpa' : String(status);
            }

            function activateDraftForCurrentFilter() {
                const key = `${$tanggal.val() || ''}:${$classroom.val() || '0'}`;
                if (key === activeDraftKey) {
                    return;
                }

                if (activeDraftKey) {
                    stagedStatusesByFilter.set(activeDraftKey, {
                        statuses: stagedStatuses,
                        originals: originalStatuses,
                        dirty: dirtyStudentIds,
                    });
                }

                activeDraftKey = key;
                const draft = stagedStatusesByFilter.get(key) || {
                    statuses: new Map(),
                    originals: new Map(),
                    dirty: new Set(),
                };
                stagedStatuses = draft.statuses;
                originalStatuses = draft.originals;
                dirtyStudentIds = draft.dirty;
                stagedStatusesByFilter.set(key, draft);
                updateSaveButtonState();
            }

            function updateSaveButtonState() {
                const hasFilter = ($tanggal.val() || '') !== '' && Number($classroom.val() || 0) > 0;
                $btnSaveAttendance.prop('disabled',
                    !hasFilter || isWeekendDate || isSavingAttendance || dirtyStudentIds.size === 0
                );
            }

            function updateActionButton($button, status) {
                const label = normalizeAttendanceStatus(status);
                const statusClass = {
                    Hadir: 'btn-success',
                    Sakit: 'btn-warning',
                    Izin: 'btn-info',
                    Dispen: 'btn-primary',
                    Alpa: 'btn-danger',
                    Terlambat: 'btn-warning',
                }[label] || 'btn-success';

                $button
                    .removeClass('btn-success btn-warning btn-info btn-primary btn-danger')
                    .addClass(statusClass)
                    .text(label);
            }

            function renderStagedStatuses() {
                $('#tableGuruAttendanceDetails tbody tr').each(function() {
                    const row = table.row(this).data();
                    if (!row) {
                        return;
                    }

                    const status = stagedStatuses.get(String(row.student_id));
                    if (status) {
                        updateActionButton($(this).find('.attendance-actions .dropdown-toggle'), status);
                    }
                });
            }

            function toggleAttendanceActions() {
                const hasDate = ($tanggal.val() || '') !== '';
                const hasClassroom = Number($classroom.val() || 0) > 0;
                const disabled = !hasDate || !hasClassroom || isWeekendDate || isLoadingClassOptions ||
                    isSavingAttendance;

                $('.js-attendance-action').prop('disabled', disabled);
                updateSaveButtonState();
                $('#statusSummaryBar').toggle(hasDate && hasClassroom && !isWeekendDate);
                $('#selectionQuickTools').toggle(hasDate && hasClassroom && !isWeekendDate);
                $('#guruAttendanceTableWrap').toggle(hasDate && hasClassroom && !isWeekendDate);
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
                const canShow = ($tanggal.val() || '').trim() !== '' &&
                    Number($classroom.val() || 0) > 0 && !isWeekendDate &&
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
                const hasClassroom = Number($classroom.val() || 0) > 0;

                if (!hasDate || !hasClassroom || isWeekendDate) {
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
                const hasClassroom = Number($classroom.val() || 0) > 0;

                if (tanggalValue === '') {
                    $filterHintAlert.removeClass('alert-warning').addClass('alert-info').html(
                        '<i class="fas fa-filter mr-1"></i> ' + noFilterMessage
                    ).show();
                    return;
                }

                if (!hasClassroom) {
                    $filterHintAlert.removeClass('alert-warning').addClass('alert-info').html(
                        '<i class="fas fa-filter mr-1"></i> Pilih kelas untuk menampilkan maksimal 50 siswa.'
                    ).show();
                    return;
                }

                if (isWeekendDate) {
                    $filterHintAlert.removeClass('alert-info').addClass('alert-warning').html(
                        '<i class="fas fa-calendar-times mr-1"></i> ' + nonSchoolDayMessage
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
                }, true);
            }

            function syncAttendanceFilterState() {
                activateDraftForCurrentFilter();
                toggleAttendanceActions();
                toggleStatusSummary();
                updateFilterHint();
            }

            function loadClassOptions(preferredClassroomId = null) {
                const tanggalValue = ($tanggal.val() || '').trim();

                if (tanggalValue === '') {
                    isLoadingClassOptions = false;
                    latestServerSummary = null;
                    isWeekendDate = false;
                    isSyncingClassOptions = true;
                    $classroom.html('<option value="0">Pilih kelas</option>').val('0');
                    isSyncingClassOptions = false;
                    table.settings()[0].oLanguage.emptyTable = noFilterMessage;
                    syncAttendanceFilterState();
                    reloadTableWithLoading();
                    return;
                }

                isLoadingClassOptions = true;
                syncAttendanceFilterState();
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
                        isLoadingClassOptions = false;
                        latestServerSummary = null;
                        isWeekendDate = !!payload.is_weekend_holiday;
                        nonSchoolDayMessage = payload.holiday_message || weekendFilterMessage;

                        const options = Array.isArray(payload.options) ? payload.options : [];
                        const selected = String(payload.selected ?? 0);

                        isSyncingClassOptions = true;
                        $classroom.empty();
                        $classroom.append('<option value="0">Pilih kelas</option>');

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
                        isLoadingClassOptions = false;
                        latestServerSummary = null;
                        isWeekendDate = false;
                        isSyncingClassOptions = true;
                        $classroom.val('0');
                        isSyncingClassOptions = false;
                        table.settings()[0].oLanguage.emptyTable = noDataMessage;
                        syncAttendanceFilterState();
                        reloadTableWithLoading();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal memuat kelas',
                            text: 'Daftar kelas untuk tanggal tersebut tidak dapat dimuat. Silakan coba lagi.'
                        });
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

                activateDraftForCurrentFilter();
                syncAttendanceFilterState();
                table.settings()[0].oLanguage.emptyTable = isWeekendDate ? weekendFilterMessage :
                    noDataMessage;
                reloadTableWithLoading();
            });

            $('#tableGuruAttendanceDetails tbody').on('click', '.js-status-option', function() {
                const row = table.row($(this).closest('tr')).data();
                if (!row) {
                    return;
                }

                const status = normalizeAttendanceStatus($(this).data('status'));
                const studentId = String(row.student_id);
                stagedStatuses.set(studentId, status);
                if (status === originalStatuses.get(studentId)) {
                    dirtyStudentIds.delete(studentId);
                } else {
                    dirtyStudentIds.add(studentId);
                }
                updateActionButton($(this).closest('.attendance-actions').find('.dropdown-toggle'), status);
                updateSaveButtonState();
                $(this).closest('.dropdown').find('.dropdown-toggle').dropdown('toggle');
            });

            $btnSaveAttendance.on('click', async function() {
                if (dirtyStudentIds.size === 0 || isSavingAttendance) {
                    return;
                }

                isSavingAttendance = true;
                toggleAttendanceActions();
                $btnSaveAttendance.html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Menyimpan...');

                try {
                    const response = await fetch("{{ route('guru.attendance-details.save') }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            tanggal: $tanggal.val(),
                            classroom_id: Number($classroom.val()),
                            attendances: Array.from(dirtyStudentIds, (studentId) => ({
                                student_id: Number(studentId),
                                status: stagedStatuses.get(studentId),
                            })),
                        }),
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        const validationErrors = payload.errors ?
                            Object.values(payload.errors).flat().join(' ') :
                            '';
                        throw new Error(validationErrors || payload.message || 'Absensi gagal disimpan.');
                    }

                    dirtyStudentIds.forEach((studentId) => {
                        originalStatuses.set(studentId, stagedStatuses.get(studentId));
                    });
                    dirtyStudentIds.clear();
                    updateSaveButtonState();
                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: payload.message || 'Absensi siswa berhasil disimpan.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    reloadTableWithLoading();
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal menyimpan absensi',
                        text: error.message || 'Terjadi kesalahan saat menyimpan absensi.',
                    });
                } finally {
                    isSavingAttendance = false;
                    $btnSaveAttendance.html('<i class="fas fa-save mr-1"></i> Simpan');
                    toggleAttendanceActions();
                }
            });

            table.on('draw', function() {
                updateStatusSummaryFromTable();
                renderStagedStatuses();
                updateSaveButtonState();
            });
            resetStatusSummary();
            syncAttendanceFilterState();

            // Muat opsi kelas tanggal terpilih; data siswa baru diminta setelah kelas dipilih.
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
