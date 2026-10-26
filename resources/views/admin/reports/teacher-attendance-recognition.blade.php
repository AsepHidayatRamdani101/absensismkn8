@extends('adminlte::page')

@section('title', 'Absensi Guru (Kombinasi)')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Absensi Guru (Kombinasi)</h1>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('wa_notification'))
                <div class="alert alert-{{ session('wa_notification.status') === 'success' ? 'success' : (session('wa_notification.status') === 'warning' ? 'warning' : 'danger') }} alert-dismissible fade show"
                    role="alert">
                    <strong>Status WhatsApp:</strong> {{ session('wa_notification.message') }}
                    @if (session('wa_notification.results'))
                        <ul class="mb-1 mt-2">
                            @foreach (session('wa_notification.results') as $result)
                                <li>
                                    <strong>{{ $result['name'] }}:</strong>
                                    {{ $result['status'] === 'accepted' ? 'Diterima Fonnte' : ($result['status'] === 'skipped' ? 'Dilewati' : 'Gagal') }}
                                    — {{ $result['message'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (session('wa_notification.delivery_note'))
                        <small class="d-block">{{ session('wa_notification.delivery_note') }}</small>
                    @endif
                    <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="border rounded p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="mb-2 mb-md-0">
                        <strong>Mode pengingat WhatsApp:</strong>
                        @if ($automaticReminderMode)
                            <span class="badge badge-primary">Otomatis</span>
                            <span class="text-muted">Setiap hari efektif pukul 15.00; tombol kirim manual nonaktif.</span>
                        @else
                            <span class="badge badge-secondary">Manual</span>
                            <span class="text-muted">Pengiriman otomatis nonaktif.</span>
                        @endif
                    </div>
                    <div class="d-flex">
                        @if ($automaticReminderMode)
                            <form method="POST" action="{{ route('app-settings.fonnte.reminder-mode') }}">
                                @csrf
                                <input type="hidden" name="mode" value="manual">
                                <button class="btn btn-outline-secondary btn-sm mr-2" type="submit">
                                    Gunakan Manual
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('app-settings.fonnte.reminder-mode') }}">
                                @csrf
                                <input type="hidden" name="mode" value="automatic">
                                <button class="btn btn-primary btn-sm" type="submit"
                                    {{ !$fonnteReady ? 'disabled' : '' }}>
                                    Aktifkan Otomatis
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @if (!$fonnteReady)
                    <small class="text-danger d-block mt-2">Aktifkan integrasi Fonnte dan isi token di Pengaturan Aplikasi untuk mengirim pesan.</small>
                @endif
            </div>
            @if ($automaticReminderMode && !$fonnteReady)
                <div class="alert alert-danger py-2">
                    Mode otomatis dipilih tetapi integrasi Fonnte belum siap; pengingat belum dapat dikirim.
                </div>
            @endif

            @if ($automaticReminderStatus)
                <div class="alert alert-{{ $automaticReminderStatus['status'] === 'success' || $automaticReminderStatus['status'] === 'info' ? 'info' : ($automaticReminderStatus['status'] === 'error' ? 'danger' : 'warning') }} py-2">
                    <strong>Status otomatis terakhir:</strong>
                    {{ $automaticReminderStatus['message'] }}
                    <small class="d-block">{{ $automaticReminderStatus['at'] }}</small>
                </div>
            @endif
            @if ($manualReminderStatus && !$automaticReminderMode && !session('wa_notification'))
                <div class="alert alert-{{ $manualReminderStatus['status'] === 'success' ? 'success' : ($manualReminderStatus['status'] === 'warning' ? 'warning' : 'danger') }} py-2">
                    <strong>Status kirim manual terakhir:</strong>
                    {{ $manualReminderStatus['message'] }}
                    @if (!empty($manualReminderStatus['results']))
                        <ul class="mb-1 mt-2">
                            @foreach ($manualReminderStatus['results'] as $result)
                                <li>
                                    <strong>{{ $result['name'] }}:</strong>
                                    {{ $result['status'] === 'accepted' ? 'Diterima Fonnte' : ($result['status'] === 'skipped' ? 'Dilewati' : 'Gagal') }}
                                    — {{ $result['message'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (!empty($manualReminderStatus['delivery_note']))
                        <small class="d-block">{{ $manualReminderStatus['delivery_note'] }}</small>
                    @endif
                    <small class="d-block">{{ $manualReminderStatus['at'] }}</small>
                </div>
            @endif

            <div class="card card-outline card-success mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fab fa-whatsapp mr-1"></i> Pengingat Aktivitas Hari Ini
                    </h3>
                </div>
                <div class="card-body p-2">
                    @if ($scheduledTeacherSummary['is_holiday'])
                        <span class="text-warning">
                            {{ $scheduledTeacherSummary['holiday_message'] ?? 'Hari ini ditetapkan sebagai hari libur.' }}
                            Pengingat WhatsApp tidak dikirim pada hari libur.
                        </span>
                    @elseif ($scheduledTeacherSummary['date']->isWeekend())
                        <span class="text-muted">
                            Hari ini akhir pekan; pengingat WhatsApp hanya tersedia pada hari efektif.
                        </span>
                    @elseif ($scheduledTeacherSummary['is_pjj'])
                        <span class="text-info">
                            Mode belajar PJJ aktif. Pengingat guru tetap tersedia; pengingat foto fisik pengurus dinonaktifkan.
                        </span>
                    @elseif (!$scheduledTeacherSummary['date']->isToday())
                        <span class="text-muted">
                            Tombol kirim hanya tersedia untuk jadwal hari ini.
                        </span>
                    @elseif ($automaticReminderMode)
                        <span class="text-info">Mode otomatis aktif; pengiriman manual dinonaktifkan.</span>
                    @elseif (!$fonnteReady)
                        <span class="text-danger">Fonnte belum aktif atau token belum diisi.</span>
                    @elseif ($scheduledTeacherRows->isEmpty())
                        <span class="text-muted">Tidak ada jadwal aktif hari ini untuk filter yang dipilih.</span>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Guru / Jadwal</th>
                                        <th>Aktivitas tertunda</th>
                                        <th>Kirim pesan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $hasReminderAction = false;
                                    @endphp
                                    @foreach ($scheduledTeacherRows as $teacherRow)
                                        @foreach ($teacherRow['schedules'] as $scheduleRow)
                                            @php
                                                $teacherNeedsReminder = $scheduleRow['is_teacher_present']
                                                    && (!$scheduleRow['has_agenda'] || !$scheduleRow['has_student_attendance']);
                                                $officersNeedReminder = !$scheduledTeacherSummary['is_pjj']
                                                    && $scheduleRow['is_teacher_present']
                                                    && !$scheduleRow['has_camera_attendance'];
                                            @endphp
                                            @if ($teacherNeedsReminder || $officersNeedReminder)
                                                @php
                                                    $hasReminderAction = true;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <strong>{{ $teacherRow['teacher_name'] }}</strong><br>
                                                        <small>
                                                            {{ $scheduleRow['schedule']->teacherSubject->subject->nama_mapel ?? '-' }}
                                                            — {{ $scheduleRow['schedule']->teacherSubject->classroom->nama_kelas ?? '-' }}
                                                            ({{ $scheduleRow['schedule']->jam_mulai }}–{{ $scheduleRow['schedule']->jam_selesai }})
                                                        </small>
                                                    </td>
                                                    <td>
                                                        @if ($teacherNeedsReminder)
                                                            @if (!$scheduleRow['has_student_attendance'])
                                                                <span class="badge badge-warning">Absensi siswa</span>
                                                            @endif
                                                            @if (!$scheduleRow['has_agenda'])
                                                                <span class="badge badge-warning">Agenda</span>
                                                            @endif
                                                        @endif
                                                        @if ($officersNeedReminder)
                                                            <span class="badge badge-info">Foto guru oleh pengurus</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($teacherNeedsReminder)
                                                            <form method="POST"
                                                                action="{{ route('reports.teacher-attendance-recognition.send-reminder', $scheduleRow['schedule']->id) }}"
                                                                class="d-inline">
                                                                @csrf
                                                                <input type="hidden" name="recipient" value="teacher">
                                                                <button class="btn btn-success btn-sm mb-1" type="submit">
                                                                    <i class="fab fa-whatsapp"></i> Kirim pesan ke guru
                                                                </button>
                                                            </form>
                                                        @endif
                                                        @if ($officersNeedReminder)
                                                            <form method="POST"
                                                                action="{{ route('reports.teacher-attendance-recognition.send-reminder', $scheduleRow['schedule']->id) }}"
                                                                class="d-inline">
                                                                @csrf
                                                                <input type="hidden" name="recipient" value="students">
                                                                <button class="btn btn-outline-success btn-sm mb-1" type="submit">
                                                                    <i class="fab fa-whatsapp"></i> Kirim pesan ke pengurus
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endforeach
                                    @unless ($hasReminderAction)
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                Tidak ada aktivitas tertunda yang perlu diingatkan untuk jadwal hari ini.
                                            </td>
                                        </tr>
                                    @endunless
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <form method="GET" action="{{ route('reports.teacher-attendance-recognition') }}" class="mb-3">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Tipe Periode</label>
                        <select name="period_type" id="period_type" class="form-control form-control-sm">
                            <option value="">Semua</option>
                            <option value="tanggal" {{ ($filters['period_type'] ?? '') === 'tanggal' ? 'selected' : '' }}>
                                Tanggal</option>
                            <option value="mingguan" {{ ($filters['period_type'] ?? '') === 'mingguan' ? 'selected' : '' }}>
                                Mingguan</option>
                            <option value="bulanan" {{ ($filters['period_type'] ?? '') === 'bulanan' ? 'selected' : '' }}>
                                Bulanan</option>
                            <option value="tahunan" {{ ($filters['period_type'] ?? '') === 'tahunan' ? 'selected' : '' }}>
                                Tahunan</option>
                        </select>
                    </div>

                    <div class="form-group col-md-3 period-field" data-type="tanggal">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm"
                            value="{{ $filters['tanggal'] ?? '' }}">
                    </div>

                    <div class="form-group col-md-3 period-field" data-type="mingguan">
                        <label>Minggu</label>
                        <input type="week" name="minggu" class="form-control form-control-sm"
                            value="{{ $filters['minggu'] ?? '' }}">
                    </div>

                    <div class="form-group col-md-3 period-field" data-type="bulanan">
                        <label>Bulan</label>
                        <input type="month" name="bulan" class="form-control form-control-sm"
                            value="{{ $filters['bulan'] ?? '' }}">
                    </div>

                    <div class="form-group col-md-3 period-field" data-type="tahunan">
                        <label>Tahun</label>
                        <input type="number" name="tahun" class="form-control form-control-sm"
                            value="{{ $filters['tahun'] ?? '' }}" min="2000" max="2100" placeholder="Contoh: 2026">
                    </div>

                    <div class="form-group col-md-3">
                        <label>Nama Guru</label>
                        <select name="teacher_id" class="form-control form-control-sm">
                            <option value="">Semua Guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}"
                                    {{ (string) ($filters['teacher_id'] ?? '') === (string) $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Jurusan</label>
                        <select name="major_id" id="major_id" class="form-control form-control-sm">
                            <option value="">Semua Jurusan</option>
                            @foreach ($majors as $major)
                                <option value="{{ $major->id }}"
                                    {{ (string) ($filters['major_id'] ?? '') === (string) $major->id ? 'selected' : '' }}>
                                    {{ $major->nama_jurusan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Kelas</label>
                        <select name="classroom_id" id="classroom_id" class="form-control form-control-sm">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" data-major-id="{{ $classroom->major_id }}"
                                    {{ (string) ($filters['classroom_id'] ?? '') === (string) $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-filter"></i> Terapkan
                        </button>
                        <a href="{{ route('reports.teacher-attendance-recognition') }}"
                            class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            <p class="text-muted mb-2"><strong>Periode:</strong> {{ $periodLabel }}</p>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $summary['total_sesi'] }}</h3>
                            <p>Total Sesi</p>
                        </div>
                        <div class="icon"><i class="fas fa-layer-group"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $summary['total_diakui'] }}</h3>
                            <p>Total Diakui (Semua Komponen Lengkap)</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ number_format($summary['persentase_diakui'], 2, ',', '.') }}%</h3>
                            <p>Persentase Diakui</p>
                        </div>
                        <div class="icon"><i class="fas fa-percent"></i></div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <a href="{{ route('reports.teacher-attendance-recognition.missing-teachers', array_merge(['type' => 'absen-siswa'], request()->query())) }}"
                        class="text-decoration-none">
                        <div class="small-box bg-danger mb-0">
                            <div class="inner">
                                <h3>{{ $missingTeacherCards['absen_siswa_belum'] ?? 0 }}</h3>
                                <p>Guru Belum Melakukan Absen Siswa</p>
                            </div>
                            <div class="icon"><i class="fas fa-user-times"></i></div>
                            <div class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('reports.teacher-attendance-recognition.missing-teachers', array_merge(['type' => 'agenda'], request()->query())) }}"
                        class="text-decoration-none">
                        <div class="small-box bg-secondary mb-0">
                            <div class="inner">
                                <h3>{{ $missingTeacherCards['agenda_belum'] ?? 0 }}</h3>
                                <p>Guru Belum Mengisi Agenda</p>
                            </div>
                            <div class="icon"><i class="fas fa-book-dead"></i></div>
                            <div class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('reports.teacher-attendance-recognition.missing-teachers', array_merge(['type' => 'foto'], request()->query())) }}"
                        class="text-decoration-none">
                        <div class="small-box bg-warning mb-0">
                            <div class="inner">
                                <h3>{{ $missingTeacherCards['foto_belum'] ?? 0 }}</h3>
                                <p>Guru Belum Diabsen Siswa (Kamera)</p>
                            </div>
                            <div class="icon"><i class="fas fa-camera-retro"></i></div>
                            <div class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></div>
                        </div>
                    </a>
                </div>
            </div>

            <p class="text-muted mb-2">
                <strong>Ringkasan jadwal:</strong>
                {{ $scheduledTeacherSummary['date']->locale('id')->translatedFormat('l, d F Y') }}
                @if ($scheduledTeacherSummary['is_holiday'])
                    — hari libur, tidak ada guru yang dijadwalkan.
                @endif
            </p>
            <div class="row mb-3">
                <div class="col-md-6">
                    <a href="{{ route('reports.teacher-attendance-recognition.scheduled-teachers', array_merge(['type' => 'without-activity'], request()->query())) }}"
                        class="text-decoration-none">
                    <div class="small-box bg-danger mb-0">
                        <div class="inner">
                            <h3>{{ $scheduledTeacherSummary['teachers_without_activity'] }}</h3>
                            <p>Guru Terjadwal Belum Melakukan Aktivitas Apa Pun</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-clock"></i></div>
                        <div class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></div>
                    </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('reports.teacher-attendance-recognition.scheduled-teachers', array_merge(['type' => 'expected'], request()->query())) }}"
                        class="text-decoration-none">
                    <div class="small-box bg-info mb-0">
                        <div class="inner">
                            <h3>{{ $scheduledTeacherSummary['total_teachers'] }}</h3>
                            <p>Guru Seharusnya Hadir di Kelas</p>
                        </div>
                        <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></div>
                    </div>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tableTeacherRecognition" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Guru</th>
                            <th>Mapel</th>
                            <th>Jurusan</th>
                            <th>Kelas</th>
                            <th>Absensi Guru Oleh Siswa (Kamera)</th>
                            <th>Agenda Oleh Guru</th>
                            <th>Absensi Siswa Oleh Guru</th>
                            <th>Skor Diakui</th>
                            <th>Kirim WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row['item']->tanggal }}</td>
                                <td>{{ $row['item']->teacher->nama_lengkap ?? '-' }}</td>
                                <td>{{ $row['item']->subject->nama_mapel ?? '-' }}</td>
                                <td>{{ $row['item']->classroom->major->nama_jurusan ?? '-' }}</td>
                                <td>{{ $row['item']->classroom->nama_kelas ?? '-' }}</td>
                                <td>
                                    @if (!empty($row['item']->foto_guru_path))
                                        <a class="btn btn-info btn-xs"
                                            href="{{ asset('storage/' . $row['item']->foto_guru_path) }}"
                                            target="_blank" rel="noopener noreferrer">
                                            <i class="fas fa-image"></i> Lihat Foto
                                        </a>
                                    @elseif (!empty($row['is_pjj_session']))
                                        <span class="badge badge-info">PJJ (tanpa foto)</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['item']->status === 'Selesai')
                                        <span class="badge badge-success">Terisi</span>
                                    @elseif (!empty($row['has_pjj_task']))
                                        <span class="badge badge-info">Otomatis (Tugas PJJ)</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if (($row['item']->kehadiran_guru ?? null) === 'Hadir' && (int) $row['item']->attendance_details_count > 0)
                                        <span class="badge badge-success">Terisi</span>
                                    @elseif (!empty($row['has_pjj_task']))
                                        <span class="badge badge-info">Otomatis (Tugas PJJ)</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['recognized_point'] === 1)
                                        <span class="badge badge-primary">1</span>
                                    @else
                                        <span class="badge badge-light">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if (\Illuminate\Support\Carbon::parse($row['item']->tanggal)->isToday())
                                        @if ($automaticReminderMode)
                                            <span class="badge badge-info">Mode otomatis aktif</span>
                                        @elseif (!$fonnteReady)
                                        <span class="badge badge-secondary">Fonnte nonaktif</span>
                                        @else
                                            @if (($row['item']->kehadiran_guru ?? 'Hadir') === 'Hadir'
                                                && (!$row['has_agenda_guru'] || !$row['has_absensi_siswa_oleh_guru']))
                                                <form method="POST"
                                                    action="{{ route('reports.teacher-attendance-recognition.send-reminder', $row['item']->schedule_id) }}"
                                                    class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="recipient" value="teacher">
                                                    <button class="btn btn-success btn-xs mb-1" type="submit">
                                                        <i class="fab fa-whatsapp"></i> Ingatkan Guru
                                                    </button>
                                                </form>
                                            @endif
                                            @if (!$scheduledTeacherSummary['is_pjj']
                                                && !$row['has_absensi_guru_siswa_kamera']
                                                && ($row['item']->kehadiran_guru ?? 'Hadir') === 'Hadir')
                                                <form method="POST"
                                                    action="{{ route('reports.teacher-attendance-recognition.send-reminder', $row['item']->schedule_id) }}"
                                                    class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="recipient" value="students">
                                                    <button class="btn btn-outline-success btn-xs mb-1" type="submit">
                                                        <i class="fab fa-whatsapp"></i> Ingatkan Pengurus
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($row['has_agenda_guru']
                                                && $row['has_absensi_siswa_oleh_guru']
                                                && $row['has_absensi_guru_siswa_kamera'])
                                                <span class="badge badge-success">Aktivitas lengkap</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-muted">Tersedia untuk tanggal hari ini</span>
                                    @endif
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
            $('#tableTeacherRecognition').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });

            function togglePeriodField() {
                let periodType = $('#period_type').val();

                $('.period-field').hide();

                if (periodType) {
                    $('.period-field[data-type="' + periodType + '"]').show();
                }
            }

            function filterClassroomsByMajor() {
                let majorId = $('#major_id').val();
                let selectedClassroom = $('#classroom_id').val();

                $('#classroom_id option').each(function() {
                    let optionMajorId = $(this).data('major-id');
                    let isPlaceholder = !$(this).val();

                    if (isPlaceholder) {
                        $(this).prop('hidden', false);
                        return;
                    }

                    $(this).prop('hidden', majorId ? String(optionMajorId) !== String(majorId) : false);
                });

                if ($('#classroom_id option:selected').prop('hidden')) {
                    $('#classroom_id').val('');
                } else {
                    $('#classroom_id').val(selectedClassroom);
                }
            }

            $('#period_type').on('change', togglePeriodField);
            $('#major_id').on('change', filterClassroomsByMajor);

            togglePeriodField();
            filterClassroomsByMajor();
        });
    </script>
@stop
