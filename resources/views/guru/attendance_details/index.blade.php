@extends('adminlte::page')

@section('title', 'Absensi Siswa Oleh Guru')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Absensi Siswa Oleh Guru</h1>
            <p class="text-muted mb-0">Tampilan minimalis untuk input cepat status siswa per kelas hari ini.</p>
        </div>
        <span class="badge badge-light border px-3 py-2 mt-2 mt-md-0">
            {{ $today->format('d M Y') }} - {{ $todayDayName }}
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
                        <button type="submit" class="btn btn-primary">
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

            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap" id="bulkActionBar"
                style="gap:.5rem; display: none;">
                <small class="text-muted">
                    <span id="selectedCountLabel">0</span> siswa dipilih. Pilih aksi massal:
                    Hadir / Sakit / Izin / Alpa.
                </small>
                <div class="d-flex flex-wrap" style="gap: .4rem;">
                    <button type="button" class="btn btn-success btn-sm btn-bulk-status" data-status="Hadir"
                        @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                        Hadir
                    </button>
                    <button type="button" class="btn btn-warning btn-sm btn-bulk-status" data-status="Sakit"
                        @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                        Sakit
                    </button>
                    <button type="button" class="btn btn-info btn-sm btn-bulk-status" data-status="Izin"
                        @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                        Izin
                    </button>
                    <button type="button" class="btn btn-danger btn-sm btn-bulk-status" data-status="Alpa"
                        @if ($isWeekendHoliday || $students->isEmpty()) disabled @endif>
                        Alpa
                    </button>
                </div>
            </div>

            <div class="table-responsive p-3 pt-2">
                <table id="tableGuruAttendanceDetails" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">
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
                                <td class="text-center">
                                    <input type="checkbox" class="check-student" name="student_ids[]"
                                        value="{{ $student->id }}" form="bulkAttendanceForm"
                                        @if ($isWeekendHoliday) disabled @endif>
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $student->nama_lengkap }}</td>
                                <td>{{ $student->classroom->nama_kelas ?? '-' }}</td>
                                <td>
                                    @if ($displayStatus === 'Hadir')
                                        <span class="badge badge-success">Hadir</span>
                                    @elseif ($displayStatus === 'Sakit')
                                        <span class="badge badge-warning">Sakit</span>
                                    @elseif ($displayStatus === 'Izin')
                                        <span class="badge badge-info">Izin</span>
                                    @elseif ($displayStatus === 'Alpa')
                                        <span class="badge badge-danger">Alpa</span>
                                    @else
                                        <span class="badge badge-secondary">Belum Absen</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap" style="gap:.35rem;">
                                        <form method="POST"
                                            action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="classroom_id" value="{{ $student->classroom_id }}">
                                            <input type="hidden" name="status" value="Hadir">
                                            <button type="submit" class="btn btn-success btn-sm"
                                                @if ($isWeekendHoliday) disabled @endif>Hadir</button>
                                        </form>

                                        <form method="POST"
                                            action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="classroom_id" value="{{ $student->classroom_id }}">
                                            <input type="hidden" name="status" value="Sakit">
                                            <button type="submit" class="btn btn-warning btn-sm"
                                                @if ($isWeekendHoliday) disabled @endif>Sakit</button>
                                        </form>

                                        <form method="POST"
                                            action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="classroom_id"
                                                value="{{ $student->classroom_id }}">
                                            <input type="hidden" name="status" value="Izin">
                                            <button type="submit" class="btn btn-info btn-sm"
                                                @if ($isWeekendHoliday) disabled @endif>Izin</button>
                                        </form>

                                        <form method="POST"
                                            action="{{ route('guru.attendance-details.submit', $student->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="classroom_id"
                                                value="{{ $student->classroom_id }}">
                                            <input type="hidden" name="status" value="Alpa">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                @if ($isWeekendHoliday) disabled @endif>Alpa</button>
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
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script>
        $(function() {
            let table = $('#tableGuruAttendanceDetails').DataTable({
                responsive: true,
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

            // Hindari state checkbox tersimpan dari browser agar aksi massal tidak muncul saat awal load.
            $('#check_all_students, .check-student').prop('checked', false);

            $('#check_all_students').on('change', function() {
                let checked = $(this).is(':checked');
                $('.check-student').prop('checked', checked);
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
