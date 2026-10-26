@extends('adminlte::page')

@section('title', 'Monitoring PJJ Kelas')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">Monitoring PJJ — {{ $classroom->nama_kelas }}</h1>
            <p class="text-muted mb-0">Pantau absensi selfie dan pengumpulan tugas siswa selama PJJ.</p>
        </div>
        <a href="{{ route('guru.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Dashboard Guru</a>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Tanggal PJJ</h3></div>
        <div class="card-body">
            <form method="GET" action="{{ route('guru.wali-kelas.pjj.monitoring') }}" class="form-row align-items-end">
                <div class="form-group col-md-3 mb-2">
                    <label for="monitoring-date">Tanggal PJJ</label>
                    <select id="monitoring-date" name="date" class="form-control form-control-sm" @disabled(empty($pjjDates))>
                        @forelse ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}" @selected($date->toDateString() === $pjjDate)>{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                        @empty
                            <option value="">Belum ada tanggal PJJ</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group col-md-3 mb-2">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Tampilkan</button>
                </div>
                @unless ($isToday)
                    <div class="col-md-6 mb-2 text-md-right">
                        <span class="badge badge-info">Mode riwayat — tombol pengingat WA hanya aktif untuk hari ini.</span>
                    </div>
                @endunless
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-2 col-6"><div class="small-box bg-secondary"><div class="inner"><h3>{{ $summary['total'] }}</h3><p>Total Siswa</p></div><div class="icon"><i class="fas fa-users"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ $summary['attended'] }}</h3><p>Sudah Absen</p></div><div class="icon"><i class="fas fa-user-check"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ $summary['late'] }}</h3><p>Terlambat</p></div><div class="icon"><i class="fas fa-clock"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-danger"><div class="inner"><h3>{{ $summary['not_attended'] }}</h3><p>Belum Absen</p></div><div class="icon"><i class="fas fa-user-times"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ $summary['assignments'] }}</h3><p>Tugas Hari Ini</p></div><div class="icon"><i class="fas fa-book"></i></div></div></div>
        <div class="col-md-2 col-6"><div class="small-box bg-purple"><div class="inner"><h3>{{ $summary['pending_submissions'] }}</h3><p>Tugas Belum Kumpul</p></div><div class="icon"><i class="fas fa-hourglass-half"></i></div></div></div>
    </div>

    @if ($assignments->isNotEmpty())
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-book mr-1"></i> Tugas PJJ untuk Kelas Ini ({{ $date->format('d-m-Y') }})</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-sm mb-0">
                    <thead><tr><th>Mata Pelajaran</th><th>Guru</th><th>Tugas</th><th>Deadline</th><th>Terkumpul</th></tr></thead>
                    <tbody>
                        @foreach ($assignments as $assignment)
                            <tr>
                                <td>{{ $assignment->teacherSubject?->subject?->nama_mapel ?? '—' }}</td>
                                <td>{{ $assignment->teacher?->nama_lengkap ?? '—' }}</td>
                                <td>{{ $assignment->title }}</td>
                                <td>
                                    @if ($assignment->due_at)
                                        {{ $assignment->due_at->format('d-m-Y H:i') }}
                                        @if ($assignment->due_at->isPast())
                                            <span class="badge badge-danger">Lewat</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $assignment->submissions->count() }} / {{ $summary['total'] }} siswa</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-list-check mr-1"></i> Monitoring Siswa ({{ $date->format('d-m-Y') }})</h3></div>
        <div class="card-body table-responsive">
            <table id="pjj-monitoring-table" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>NIS</th>
                        <th>Absen Datang</th>
                        <th>Absen Pulang</th>
                        <th>Status Absen</th>
                        <th>Tugas Belum Dikumpulkan</th>
                        <th>Pengingat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php
                            $attendance = $row['attendance'];
                            $checkIn = $attendance?->check_in_at ?? $attendance?->created_at;
                            $checkOut = $attendance?->check_out_at;
                        @endphp
                        <tr>
                            <td>{{ $row['student']->nama_lengkap }}</td>
                            <td>{{ $row['student']->nis }}</td>
                            <td>{{ $checkIn?->format('H:i') ?? '—' }}</td>
                            <td>{{ $checkOut?->format('H:i') ?? '—' }}</td>
                            <td>
                                @if (!$attendance)
                                    <span class="badge badge-danger">Belum absen</span>
                                @elseif ($attendance->status === 'Terlambat')
                                    <span class="badge badge-warning">Terlambat</span>
                                @else
                                    <span class="badge badge-success">Hadir</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['pending_assignments']->isEmpty())
                                    <span class="badge badge-success">Semua terkumpul</span>
                                @else
                                    <ul class="mb-0 pl-3">
                                        @foreach ($row['pending_assignments'] as $assignment)
                                            <li>
                                                {{ $assignment->title }}
                                                <small class="text-muted">
                                                    ({{ $assignment->teacherSubject?->subject?->nama_mapel ?? '-' }}
                                                    · deadline {{ $assignment->due_at?->format('d-m-Y H:i') ?? '—' }})
                                                </small>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td>
                                @if ($row['whatsapp_url'])
                                    <a class="btn btn-xs btn-success" target="_blank" rel="noopener noreferrer" href="{{ $row['whatsapp_url'] }}">
                                        <i class="fab fa-whatsapp"></i> Ingatkan via WA
                                    </a>
                                @elseif ($isToday && ($attendance === null || $row['pending_assignments']->isNotEmpty()))
                                    <span class="text-muted small">No. HP tidak tersedia</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function () {
            $('#pjj-monitoring-table').DataTable({
                pageLength: 50,
                lengthMenu: [25, 50, 100],
                order: [],
                columnDefs: [{ orderable: false, targets: [5, 6] }],
                language: {
                    search: 'Cari siswa:', lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ siswa', infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Siswa tidak ditemukan', emptyTable: 'Belum ada siswa di kelas ini',
                    paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                }
            });
            $('#monitoring-date').on('change', function () { this.form.submit(); });
        });
    </script>
@stop
