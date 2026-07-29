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

        @if ((!$isWeekendHoliday && count($teachersWithAttendance) > 0) || count($teachersWithoutAttendance) > 0)
            <!-- Teacher Attendance Status Cards -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <a href="{{ route('guru.attendance-details.teacher-attendance-detail', ['filter' => 'sudah']) }}"
                        class="text-decoration-none">
                        <div class="info-box bg-success" style="cursor:pointer;">
                            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Guru Sudah Absen</span>
                                <span class="info-box-number">{{ count($teachersWithAttendance) }}</span>
                                <span class="progress-description"><i class="fas fa-eye"></i> Lihat Detail</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('guru.attendance-details.teacher-attendance-detail', ['filter' => 'belum']) }}"
                        class="text-decoration-none">
                        <div class="info-box bg-danger" style="cursor:pointer;">
                            <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Guru Belum Absen</span>
                                <span class="info-box-number">{{ count($teachersWithoutAttendance) }}</span>
                                <span class="progress-description"><i class="fas fa-eye"></i> Lihat Detail</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('guru.attendance-details.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-8 col-lg-6">
                            <label for="classroom_id" class="mb-1">Filter Kelas (sesuai jadwal hari ini)</label>
                            <select name="classroom_id" id="classroom_id" class="form-control">
                                <option value="0">Semua Kelas Hari Ini</option>
                                @foreach ($classOptions as $classroom)
                                    <option value="{{ $classroom->id }}" @selected($selectedClassroomId === (int) $classroom->id)>
                                        {{ $classroom->nama_kelas }} ({{ $classroom->kode_kelas }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-3 mt-2 mt-md-0">
                            <button type="submit" class="btn btn-primary filter-submit-btn">
                                <i class="fas fa-filter"></i>
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <form id="bulkAttendanceForm" method="POST" action="{{ route('guru.attendance-details.bulk-submit') }}"
                    class="d-none">
                    @csrf
                    <input type="hidden" name="classroom_id" value="{{ $selectedClassroomId }}">
                    <input type="hidden" name="bulk_status" id="bulk_status" value="">
                </form>

                <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap"
                    id="bulkActionBar" style="gap:.5rem; display: none;">
                    <small class="text-muted">
                        <span id="selectedCountLabel">0</span> siswa dipilih. Pilih aksi massal:
                        Hadir / Sakit / Izin / Alpa / Terlambat.
                    </small>
                    <div class="d-flex flex-wrap bulk-status-grid" style="gap: .4rem;">
                        <button type="button" class="btn btn-success btn-xs btn-bulk-status" data-status="Hadir"
                            @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                            Hadir
                        </button>
                        <button type="button" class="btn btn-warning btn-xs btn-bulk-status" data-status="Sakit"
                            @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                            Sakit
                        </button>
                        <button type="button" class="btn btn-info btn-xs btn-bulk-status" data-status="Izin"
                            @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                            Izin
                        </button>
                        <button type="button" class="btn btn-danger btn-xs btn-bulk-status" data-status="Alpa"
                            @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                            Alpa
                        </button>
                        <button type="button" class="btn btn-warning btn-xs btn-bulk-status" data-status="Terlambat"
                            @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
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
                        <button type="button" id="btnSelectVisibleRows" class="btn btn-outline-primary btn-xs"
                            @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                            Pilih Semua Terlihat
                        </button>
                        <button type="button" id="btnClearSelectedRows" class="btn btn-outline-secondary btn-xs"
                            @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                            Bersihkan Pilihan
                        </button>
                    </div>
                </div>

                <div class="table-responsive p-3 pt-2">
                    <table id="tableGuruAttendanceDetails" class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th class="col-check">
                                    <input type="checkbox" id="check_all_students"
                                        @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                                </th>
                                <th width="5%">No</th>
                                <th>Nama Siswa</th>
                                <th width="20%">Kelas</th>
                                <th width="18%">Status Saat Ini</th>
                                <th width="30%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                @php
                                    $rawStatus = $statusByStudentId[$student->id] ?? null;
                                    $displayStatus = $rawStatus === 'Alpha' ? 'Alpa' : $rawStatus ?? 'Belum Absen';
                                @endphp
                                <tr>
                                    <td class="col-check">
                                        <input type="checkbox" class="check-student" name="student_ids[]"
                                            value="{{ $student->id }}" form="bulkAttendanceForm"
                                            @if ($isWeekendHoliday) disabled @endif>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $student->nama_lengkap }}</td>
                                    <td>{{ $student->classroom->nama_kelas ?? '-' }}</td>
                                    <td>
                                        @if ($displayStatus === 'Hadir')
                                            <span class="badge badge-success status-badge">Hadir</span>
                                        @elseif ($displayStatus === 'Sakit')
                                            <span class="badge badge-warning status-badge">Sakit</span>
                                        @elseif ($displayStatus === 'Izin')
                                            <span class="badge badge-info status-badge">Izin</span>
                                        @elseif ($displayStatus === 'Alpa')
                                            <span class="badge badge-danger status-badge">Alpa</span>
                                        @elseif ($displayStatus === 'Terlambat')
                                            <span class="badge badge-warning status-badge">Terlambat</span>
                                        @else
                                            <span class="badge badge-secondary status-badge">Belum Absen</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="attendance-actions">
                                            <form method="POST"
                                                action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="classroom_id"
                                                    value="{{ $student->classroom_id }}">
                                                <input type="hidden" name="status" value="Hadir">
                                                <button type="submit" class="btn btn-success btn-xs"
                                                    @if ($isWeekendHoliday) disabled @endif>Hadir</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="classroom_id"
                                                    value="{{ $student->classroom_id }}">
                                                <input type="hidden" name="status" value="Sakit">
                                                <button type="submit" class="btn btn-warning btn-xs"
                                                    @if ($isWeekendHoliday) disabled @endif>Sakit</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="classroom_id"
                                                    value="{{ $student->classroom_id }}">
                                                <input type="hidden" name="status" value="Izin">
                                                <button type="submit" class="btn btn-info btn-xs"
                                                    @if ($isWeekendHoliday) disabled @endif>Izin</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="classroom_id"
                                                    value="{{ $student->classroom_id }}">
                                                <input type="hidden" name="status" value="Alpa">
                                                <button type="submit" class="btn btn-danger btn-xs"
                                                    @if ($isWeekendHoliday) disabled @endif>Alpa</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="classroom_id"
                                                    value="{{ $student->classroom_id }}">
                                                <input type="hidden" name="status" value="Terlambat">
                                                <button type="submit" class="btn btn-warning btn-xs"
                                                    @if ($isWeekendHoliday) disabled @endif>Terlambat</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3">
                                        @if ($isWeekendHoliday)
                                            Hari {{ $todayDayName }} libur otomatis.
                                        @else
                                            Tidak ada data siswa dari kelas yang memiliki jadwal Anda hari ini.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
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
            let table = $('#tableGuruAttendanceDetails').DataTable({
                responsive: false,
                scrollX: true,
                autoWidth: false,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                },
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }, {
                    orderable: false,
                    targets: 5
                }]
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
        });
    </script>
@stop
