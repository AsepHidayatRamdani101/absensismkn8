@extends('adminlte::page')

@section('title', 'Absen Guru - Siswa')

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Absensi Guru</h1>
            <p class="text-muted mb-0">Jadwal hari ini untuk kelas Anda, pilih aksi Hadir atau Tugas.</p>
        </div>
        <span class="badge badge-light border px-3 py-2 mt-2 mt-md-0">
            {{ $today->format('d M Y') }} - {{ $todayDayName }}
        </span>
    </div>
@stop

@section('content')
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

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <strong>Nama Siswa:</strong> {{ $student->nama_lengkap }}<br>
                <strong>Kelas:</strong> {{ $student->classroom->nama_kelas ?? '-' }}
                ({{ $student->classroom->kode_kelas ?? '-' }})
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">Hari</th>
                            <th width="15%">Jam</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th>Ruangan</th>
                            <th width="15%">Status Anda</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scheduleRows as $row)
                            @php
                                $schedule = $row['schedule'];
                                $selectedAction = $row['selected_action'];
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $schedule->hari }}</td>
                                <td>{{ substr($schedule->jam_mulai, 0, 5) }} - {{ substr($schedule->jam_selesai, 0, 5) }}
                                </td>
                                <td>{{ $schedule->teacherSubject->subject->nama_mapel ?? '-' }}</td>
                                <td>{{ $schedule->teacherSubject->teacher->nama_lengkap ?? '-' }}</td>
                                <td>{{ $schedule->ruangan ?? '-' }}</td>
                                <td>
                                    @if ($selectedAction === 'Hadir')
                                        <span class="badge badge-success">Hadir</span>
                                    @elseif ($selectedAction === 'Tugas')
                                        <span class="badge badge-warning">Tugas</span>
                                    @else
                                        <span class="badge badge-secondary">Belum Absen</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex" style="gap: 0.4rem;">
                                        <form action="{{ route('siswa.teacher-attendances.submit', $schedule->id) }}"
                                            method="POST">
                                            @csrf
                                            <input type="hidden" name="action" value="Hadir">
                                            <button type="submit" class="btn btn-success btn-sm">Hadir</button>
                                        </form>

                                        <form action="{{ route('siswa.teacher-attendances.submit', $schedule->id) }}"
                                            method="POST">
                                            @csrf
                                            <input type="hidden" name="action" value="Tugas">
                                            <button type="submit" class="btn btn-warning btn-sm">Tugas</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    @if ($isWeekendHoliday)
                                        Hari {{ $todayDayName }} libur otomatis.
                                    @else
                                        Tidak ada jadwal untuk hari ini.
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
