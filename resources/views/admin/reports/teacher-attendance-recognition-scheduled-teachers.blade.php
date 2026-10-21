@extends('adminlte::page')

@section('title', $title)

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $title }}</h1>
        <a href="{{ route('reports.teacher-attendance-recognition', request()->query()) }}"
            class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            @if (session('wa_notification'))
                <div class="alert alert-{{ session('wa_notification.status') === 'success' ? 'success' : (session('wa_notification.status') === 'warning' ? 'warning' : 'danger') }} alert-dismissible fade show"
                    role="alert">
                    <strong>Status WhatsApp:</strong> {{ session('wa_notification.message') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            <p class="text-muted mb-3">
                <strong>Tanggal:</strong> {{ $date->locale('id')->translatedFormat('l, d F Y') }}
                @if ($isHoliday)
                    <span class="badge badge-warning ml-2">Hari Libur</span>
                @endif
            </p>

            @if ($isHoliday)
                <div class="alert alert-info">
                    Tidak ada jadwal guru karena tanggal tersebut merupakan hari libur.
                </div>
            @elseif ($rows->isEmpty())
                <div class="alert alert-info">
                    Tidak ada guru dengan jadwal aktif pada tanggal ini dan filter yang dipilih.
                </div>
            @endif

            <div class="table-responsive">
                <table id="tableScheduledTeachers" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Guru</th>
                            <th>Jadwal Mapel dan Kelas</th>
                            <th>Absensi Siswa Oleh Guru</th>
                            <th>Agenda Guru</th>
                            <th>Absensi Guru Oleh Siswa (Kamera)</th>
                            <th>Status Aktivitas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row['teacher_name'] }}</td>
                                <td>
                                    @foreach ($row['schedules'] as $scheduleRow)
                                        @php($schedule = $scheduleRow['schedule'])
                                        <div class="mb-2">
                                            <strong>{{ $schedule->teacherSubject->subject->nama_mapel ?? '-' }}</strong>
                                            — {{ $schedule->teacherSubject->classroom->nama_kelas ?? '-' }}
                                            <small class="text-muted d-block">
                                                {{ $schedule->jam_mulai }}–{{ $schedule->jam_selesai }}
                                            </small>
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge {{ $row['has_student_attendance'] ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $row['has_student_attendance'] ? 'Terisi' : 'Belum' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $row['has_agenda'] ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $row['has_agenda'] ? 'Terisi' : 'Belum' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($row['has_camera_attendance'])
                                        <a class="btn btn-info btn-xs" href="{{ $row['photo_url'] }}"
                                            target="_blank" rel="noopener noreferrer">
                                            <i class="fas fa-image"></i> Lihat Foto
                                        </a>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['has_any_activity'])
                                        <span class="badge badge-primary">Sudah ada aktivitas</span>
                                    @else
                                        <span class="badge badge-danger">Belum ada aktivitas</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($automaticReminderMode)
                                        <span class="badge badge-info">Pengingat otomatis</span>
                                    @elseif (!$fonnteReady)
                                        <span class="badge badge-secondary">Fonnte nonaktif</span>
                                    @elseif (!$date->isToday() || $date->isWeekend() || $isHoliday)
                                        <span class="text-muted">Kirim pengingat hanya tersedia untuk hari efektif ini.</span>
                                    @else
                                        @foreach ($row['schedules'] as $scheduleRow)
                                            @if ($scheduleRow['is_teacher_present']
                                                && (!$scheduleRow['has_agenda'] || !$scheduleRow['has_student_attendance']))
                                                <form method="POST"
                                                    action="{{ route('reports.teacher-attendance-recognition.send-reminder', $scheduleRow['schedule']->id) }}"
                                                    class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="recipient" value="teacher">
                                                    <button class="btn btn-success btn-xs mb-1" type="submit">
                                                        <i class="fab fa-whatsapp"></i> Ingatkan Guru
                                                    </button>
                                                </form>
                                            @endif
                                            @if (!$isPjj && $scheduleRow['is_teacher_present'] && !$scheduleRow['has_camera_attendance'])
                                                <form method="POST"
                                                    action="{{ route('reports.teacher-attendance-recognition.send-reminder', $scheduleRow['schedule']->id) }}"
                                                    class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="recipient" value="students">
                                                    <button class="btn btn-outline-success btn-xs mb-1" type="submit">
                                                        <i class="fab fa-whatsapp"></i> Ingatkan Pengurus
                                                    </button>
                                                </form>
                                            @endif
                                        @endforeach
                                    @endif
                                    @foreach ($row['schedules'] as $scheduleRow)
                                        @if ($scheduleRow['attendance'])
                                            <form method="POST"
                                                action="{{ route('reports.teacher-attendance-recognition.scheduled-attendance.destroy', $scheduleRow['attendance']->id) }}"
                                                class="d-inline form-delete-attendance">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="type" value="{{ $type }}">
                                                @foreach (request()->query() as $key => $value)
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endforeach
                                                <button type="submit" class="btn btn-danger btn-xs mb-1"
                                                    title="Hapus sesi absensi ini beserta detail siswa dan file terkait">
                                                    <i class="fas fa-trash"></i> Hapus Sesi
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
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
            $('.form-delete-attendance').on('submit', function(event) {
                if (!window.confirm('Hapus seluruh sesi absensi guru ini beserta detail absensi siswa dan file terkait? Tindakan ini tidak dapat dibatalkan.')) {
                    event.preventDefault();
                }
            });

            $('#tableScheduledTeachers').DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        });
    </script>
@stop
