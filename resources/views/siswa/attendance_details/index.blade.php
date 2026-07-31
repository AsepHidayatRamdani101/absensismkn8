@extends('adminlte::page')

@section('title', 'Absensi Siswa Kelas')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Absensi Siswa Kelas</h1>
            <p class="text-muted mb-0">KM/Sekretaris/Bendahara dapat mengisi absensi siswa saat guru izin disetujui kurikulum
                dan izin petugas disetujui kurikulum.</p>
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
            <div class="mb-2">
                <strong>Petugas:</strong> {{ $officer->nama_lengkap }}<br>
                <strong>Kelas:</strong> {{ $officer->classroom->nama_kelas ?? '-' }}
                ({{ $officer->classroom->kode_kelas ?? '-' }})<br>
                <strong>Jabatan:</strong> {{ $officer->jabatan_kelas_label }}
            </div>

            <form method="GET" action="{{ route('siswa.attendance-details.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-8 col-lg-6">
                        <label for="schedule_id" class="mb-1">Pilih Jadwal Guru Dengan Izin Disetujui Kurikulum</label>
                        <select name="schedule_id" id="schedule_id" class="form-control">
                            <option value="0">Pilih Jadwal</option>
                            @foreach ($leaveSchedules as $schedule)
                                <option value="{{ $schedule->id }}" @selected($selectedScheduleId === (int) $schedule->id)>
                                    {{ substr($schedule->jam_mulai, 0, 5) }} - {{ substr($schedule->jam_selesai, 0, 5) }}
                                    |
                                    {{ $schedule->teacherSubject->subject->nama_mapel ?? '-' }} |
                                    {{ $schedule->teacherSubject->teacher->nama_lengkap ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-3 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($leaveSchedules->isEmpty() && !$isWeekendHoliday)
        <div class="alert alert-warning">
            Belum ada guru dengan izin yang disetujui kurikulum pada jadwal kelas Anda hari ini.
        </div>
    @endif

    @if ($selectedSchedule && $activeLeaveRequest)
        <div class="card mb-3 border-warning">
            <div class="card-body py-2">
                <strong>Status Pengajuan Guru:</strong>
                <span class="badge badge-warning ml-1">{{ $activeLeaveRequest->jenis_pengajuan }}</span>
                <span class="badge badge-success ml-1">{{ $activeLeaveRequest->status_pengajuan }}</span>

                @if (!empty($activeLeaveRequest->deskripsi_tugas))
                    <div class="mt-2"><strong>Tugas:</strong> {{ $activeLeaveRequest->deskripsi_tugas }}</div>
                @endif

                @if (!empty($activeLeaveRequest->lampiran_tugas_path))
                    <a href="{{ asset('storage/' . $activeLeaveRequest->lampiran_tugas_path) }}" target="_blank"
                        class="btn btn-outline-primary btn-xs mt-2">
                        <i class="fas fa-paperclip"></i> Lihat Lampiran Tugas
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if ($selectedSchedule && $activeLeaveRequest)
        <div class="card mb-3 border-info">
            <div class="card-body">
                <h6 class="mb-2">Pengajuan Izin Petugas Absen Kelas ke Kurikulum</h6>
                @if ($officerPermit)
                    <div class="mb-2">
                        <span
                            class="badge badge-{{ $officerPermit->status_pengajuan === 'Disetujui' ? 'success' : ($officerPermit->status_pengajuan === 'Ditolak' ? 'danger' : 'warning') }}">
                            Status: {{ $officerPermit->status_pengajuan }}
                        </span>
                    </div>
                    <div class="mb-2"><strong>Alasan:</strong> {{ $officerPermit->alasan }}</div>
                    @if (!empty($officerPermit->catatan_kurikulum))
                        <div class="mb-2"><strong>Catatan Kurikulum:</strong> {{ $officerPermit->catatan_kurikulum }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning mb-2 py-2">
                        Aksi absensi kelas dikunci sampai izin petugas disetujui kurikulum.
                    </div>
                @endif

                @if (!$officerPermit || in_array($officerPermit->status_pengajuan, ['Menunggu', 'Ditolak'], true))
                    <form method="POST" action="{{ route('siswa.attendance-details.permit.store') }}">
                        @csrf
                        <input type="hidden" name="schedule_id" value="{{ $selectedSchedule->id }}">
                        <div class="form-group mb-2">
                            <label class="mb-1">Alasan Pengajuan</label>
                            <textarea name="alasan" class="form-control" rows="2" required
                                placeholder="Contoh: Mengajukan izin sebagai petugas absen kelas pada jadwal ini."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane"></i>
                            {{ $officerPermit && $officerPermit->status_pengajuan === 'Ditolak' ? 'Ajukan Ulang ke Kurikulum' : 'Ajukan ke Kurikulum' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <form id="bulkAttendanceForm" method="POST" action="{{ route('siswa.attendance-details.bulk-submit') }}"
                class="d-none">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $officer->classroom_id }}">
                <input type="hidden" name="schedule_id" value="{{ $selectedScheduleId }}">
                <input type="hidden" name="bulk_status" id="bulk_status" value="">
            </form>

            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap" id="bulkActionBar"
                style="gap:.5rem; display: none;">
                <small class="text-muted">
                    <span id="selectedCountLabel">0</span> siswa dipilih. Aksi massal:
                    Hadir / Sakit / Izin / Alpa.
                </small>
                <div class="d-flex flex-wrap" style="gap: .4rem;">
                    <button type="button" class="btn btn-success btn-sm btn-bulk-status" data-status="Hadir"
                        @if ($isWeekendHoliday || $students->isEmpty() || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>
                        Hadir
                    </button>
                    <button type="button" class="btn btn-warning btn-sm btn-bulk-status" data-status="Sakit"
                        @if ($isWeekendHoliday || $students->isEmpty() || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>
                        Sakit
                    </button>
                    <button type="button" class="btn btn-info btn-sm btn-bulk-status" data-status="Izin"
                        @if ($isWeekendHoliday || $students->isEmpty() || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>
                        Izin
                    </button>
                    <button type="button" class="btn btn-danger btn-sm btn-bulk-status" data-status="Alpa"
                        @if ($isWeekendHoliday || $students->isEmpty() || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>
                        Alpa
                    </button>
                </div>
            </div>

            <div class="table-responsive p-3 pt-2">
                <table id="tableOfficerAttendanceDetails" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">
                                <input type="checkbox" id="check_all_students"
                                    @if ($isWeekendHoliday || $students->isEmpty() || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>
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
                                        @if ($isWeekendHoliday || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>
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
                                            action="{{ route('siswa.attendance-details.submit', $student->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="classroom_id"
                                                value="{{ $student->classroom_id }}">
                                            <input type="hidden" name="schedule_id" value="{{ $selectedScheduleId }}">
                                            <input type="hidden" name="status" value="Hadir">
                                            <button type="submit" class="btn btn-success btn-sm"
                                                @if ($isWeekendHoliday || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>Hadir</button>
                                        </form>

                                        <form method="POST"
                                            action="{{ route('siswa.attendance-details.submit', $student->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="classroom_id"
                                                value="{{ $student->classroom_id }}">
                                            <input type="hidden" name="schedule_id" value="{{ $selectedScheduleId }}">
                                            <input type="hidden" name="status" value="Sakit">
                                            <button type="submit" class="btn btn-warning btn-sm"
                                                @if ($isWeekendHoliday || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>Sakit</button>
                                        </form>

                                        <form method="POST"
                                            action="{{ route('siswa.attendance-details.submit', $student->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="classroom_id"
                                                value="{{ $student->classroom_id }}">
                                            <input type="hidden" name="schedule_id" value="{{ $selectedScheduleId }}">
                                            <input type="hidden" name="status" value="Izin">
                                            <button type="submit" class="btn btn-info btn-sm"
                                                @if ($isWeekendHoliday || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>Izin</button>
                                        </form>

                                        <form method="POST"
                                            action="{{ route('siswa.attendance-details.submit', $student->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="classroom_id"
                                                value="{{ $student->classroom_id }}">
                                            <input type="hidden" name="schedule_id" value="{{ $selectedScheduleId }}">
                                            <input type="hidden" name="status" value="Alpa">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                @if ($isWeekendHoliday || !$selectedSchedule || !$canOfficerFillAttendance) disabled @endif>Alpa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3">
                                    Tidak ada data siswa untuk kelas ini.
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
            $('#tableOfficerAttendanceDetails').DataTable({
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
