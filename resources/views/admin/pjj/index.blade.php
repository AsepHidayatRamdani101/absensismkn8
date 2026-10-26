@extends('adminlte::page')

@section('title', 'Dashboard PJJ')

@section('plugins.Datatables', true)

@section('css')
    <style>
        .pjj-stat-row > [class*="col-"] {
            display: flex;
        }

        .pjj-stat-card {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 150px;
            width: 100%;
        }

        .pjj-stat-card .inner {
            flex: 1 1 auto;
            padding-right: 64px;
        }

        .pjj-stat-card .inner h3 {
            font-size: clamp(1.25rem, 1.7vw, 2rem);
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .pjj-stat-card .inner p {
            display: flex;
            align-items: flex-start;
            font-size: clamp(.72rem, .85vw, .88rem);
            line-height: 1.25;
            min-height: 2.5em;
        }

        .pjj-stat-card .icon {
            font-size: clamp(2.5rem, 4vw, 3.5rem);
            right: .65rem;
            top: .75rem;
        }

        .pjj-stat-card .small-box-footer {
            align-items: center;
            display: flex;
            justify-content: center;
            line-height: 1.2;
            min-height: 2.6rem;
            padding: .4rem .5rem;
            text-align: center;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">Pembelajaran Jarak Jauh</h1>
            <p class="text-muted mb-0">Pantau tugas, kehadiran, dan efektivitas pembelajaran PJJ.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Dashboard Admin</a>
    </div>
@stop

@section('content')
    @php
        $tableRoute = null;
        $datatableColumns = [];
    @endphp
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (!$isPjjActiveToday)
        <div class="alert alert-warning"><i class="fas fa-info-circle mr-1"></i> Mode PJJ tidak aktif hari ini. Menu PJJ akan tampil otomatis saat tanggal hari ini masuk rentang PJJ.</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Periode Dashboard</h3></div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pjj.dashboard') }}" class="form-row align-items-end">
                <div class="form-group col-md-3 mb-2">
                    <label for="period">Periode</label>
                    <select id="period" name="period" class="form-control form-control-sm">
                        <option value="day" @selected($filters['period'] === 'day')>Harian</option>
                        <option value="week" @selected($filters['period'] === 'week')>Mingguan</option>
                        <option value="month" @selected($filters['period'] === 'month')>Bulanan</option>
                    </select>
                </div>
                <div class="form-group col-md-3 mb-2">
                    <label for="date">Tanggal acuan</label>
                    <select id="date" name="date" class="form-control form-control-sm" @disabled(empty($pjjDates)) required>
                        @forelse ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}" @selected($filters['date']->toDateString() === $pjjDate)>{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                        @empty
                            <option value="">Belum ada tanggal PJJ yang diatur</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group col-md-3 mb-2">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Tampilkan</button>
                    <a href="{{ route('admin.pjj.dashboard') }}" class="btn btn-secondary btn-sm">Reset</a>
                </div>
                <div class="col-md-3 mb-2 text-md-right text-muted small">{{ $filters['start']->format('d-m-Y') }} s.d. {{ $filters['end']->format('d-m-Y') }}</div>
            </form>
        </div>
    </div>

    <div class="row pjj-stat-row">
        <div class="col-xl col-md-4 col-sm-6">
            <div class="small-box pjj-stat-card bg-info"><div class="inner"><h3>{{ $assignmentsCount }}</h3><p>Tugas guru diunggah</p></div><div class="icon"><i class="fas fa-book"></i></div><a href="{{ route('admin.pjj.tasks', request()->query()) }}" class="small-box-footer">Data tugas <i class="fas fa-arrow-circle-right"></i></a></div>
        </div>
        <div class="col-xl col-md-4 col-sm-6">
            <div class="small-box pjj-stat-card bg-success"><div class="inner"><h3>{{ $teacherAttendanceCount }}</h3><p>Guru hadir melalui upload tugas</p></div><div class="icon"><i class="fas fa-chalkboard-teacher"></i></div><a href="{{ route('admin.pjj.teacher-attendance', request()->query()) }}" class="small-box-footer">Lihat guru <i class="fas fa-arrow-circle-right"></i></a></div>
        </div>
        <div class="col-xl col-md-4 col-sm-6">
            <div class="small-box pjj-stat-card bg-primary"><div class="inner"><h3>{{ $studentAttendanceCount }}</h3><p>Siswa hadir selfie (unik)</p></div><div class="icon"><i class="fas fa-camera"></i></div><a href="{{ route('admin.pjj.student-attendance', request()->query()) }}" class="small-box-footer">Lihat absensi <i class="fas fa-arrow-circle-right"></i></a></div>
        </div>
        <div class="col-xl col-md-4 col-sm-6">
            <div class="small-box pjj-stat-card bg-warning"><div class="inner"><h3>{{ $submittedTaskCount }} / {{ $studentTaskCount }}</h3><p>Tugas terkumpul / target</p></div><div class="icon"><i class="fas fa-user-graduate"></i></div><a href="{{ route('admin.pjj.effectiveness', request()->query()) }}" class="small-box-footer">Efektivitas <i class="fas fa-arrow-circle-right"></i></a></div>
        </div>
        <div class="col-xl col-md-4 col-sm-6">
            <div class="small-box pjj-stat-card bg-purple"><div class="inner"><h3>{{ number_format($effectiveness, 2) }}%</h3><p>Efektivitas pengumpulan</p></div><div class="icon"><i class="fas fa-chart-line"></i></div><a href="{{ route('admin.pjj.effectiveness', request()->query()) }}" class="small-box-footer">Lihat perhitungan <i class="fas fa-arrow-circle-right"></i></a></div>
        </div>
    </div>

    @if ($section !== 'dashboard')
        @php
            $sectionLabels = [
                'tasks' => 'Data Tugas Guru',
                'teacher-attendance' => 'Absensi Guru melalui Upload Tugas',
                'student-attendance' => 'Absensi Siswa dengan Selfie',
                'effectiveness' => 'Efektivitas Belajar PJJ',
            ];
            $tableRoute = [
                'tasks' => 'admin.pjj.tasks',
                'teacher-attendance' => 'admin.pjj.teacher-attendance',
                'student-attendance' => 'admin.pjj.student-attendance',
            ][$section] ?? null;
            $datatableColumns = match ($section) {
                'tasks' => [
                    ['data' => 'date', 'name' => 'date'],
                    ['data' => 'teacher', 'name' => 'teacher'],
                    ['data' => 'classroom', 'name' => 'classroom'],
                    ['data' => 'subject', 'name' => 'subject'],
                    ['data' => 'title', 'name' => 'title'],
                    ['data' => 'deadline', 'name' => 'deadline'],
                    ['data' => 'submissions', 'name' => 'submissions'],
                    ['data' => 'attachment', 'name' => 'attachment', 'orderable' => false, 'searchable' => false],
                ],
                'teacher-attendance' => [
                    ['data' => 'teacher', 'name' => 'teacher'],
                    ['data' => 'nip', 'name' => 'nip'],
                    ['data' => 'tasks', 'name' => 'tasks'],
                    ['data' => 'status', 'name' => 'status', 'orderable' => false, 'searchable' => false],
                ],
                default => [
                    ['data' => 'date', 'name' => 'date'],
                    ['data' => 'student', 'name' => 'student'],
                    ['data' => 'nis', 'name' => 'nis'],
                    ['data' => 'classroom', 'name' => 'classroom'],
                    ['data' => 'check_in', 'name' => 'check_in', 'orderable' => false, 'searchable' => false],
                    ['data' => 'check_out', 'name' => 'check_out', 'orderable' => false, 'searchable' => false],
                    ['data' => 'photo', 'name' => 'photo', 'orderable' => false, 'searchable' => false],
                    ['data' => 'location', 'name' => 'location', 'orderable' => false, 'searchable' => false],
                ],
            };
        @endphp

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i> {{ $sectionLabels[$section] }}</h3>
                <div class="card-tools"><a href="{{ route('admin.pjj.dashboard', request()->query()) }}" class="btn btn-tool" title="Kembali ke dashboard"><i class="fas fa-times"></i></a></div>
            </div>
            @if ($section === 'effectiveness')
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.pjj.effectiveness') }}" class="form-row align-items-end mb-3">
                        <div class="form-group col-md-3 mb-2">
                            <label for="effectiveness-period">Periode</label>
                            <select id="effectiveness-period" name="period" class="form-control form-control-sm">
                                <option value="day" @selected($filters['period'] === 'day')>Harian</option>
                                <option value="week" @selected($filters['period'] === 'week')>Mingguan</option>
                                <option value="month" @selected($filters['period'] === 'month')>Bulanan</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3 mb-2">
                            <label for="effectiveness-date">Tanggal acuan</label>
                            <select id="effectiveness-date" name="date" class="form-control form-control-sm" @disabled(empty($pjjDates)) required>
                                @forelse ($pjjDates as $pjjDate)
                                    <option value="{{ $pjjDate }}" @selected($filters['date']->toDateString() === $pjjDate)>{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                                @empty
                                    <option value="">Belum ada tanggal PJJ yang diatur</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="form-group col-md-3 mb-2">
                            <button class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                            <a href="{{ route('admin.pjj.effectiveness') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </form>
                    <p>Persentase dihitung dari jumlah pengumpulan siswa dibagi seluruh target pengumpulan tugas untuk kelas yang dituju pada periode terpilih.</p>
                    <div class="progress progress-sm"><div class="progress-bar bg-success" role="progressbar" style="width: {{ min($effectiveness, 100) }}%"></div></div>
                    <div class="mt-2 font-weight-bold">{{ number_format($effectiveness, 2) }}% <span class="text-muted font-weight-normal">({{ $submittedTaskCount }} dari {{ $studentTaskCount }} target pengumpulan)</span></div>
                </div>
            @else
                <div class="card-body">
                    <form id="pjj-admin-filter-form" class="form-row align-items-end mb-3">
                        <div class="form-group col-xl-2 col-md-4 mb-2">
                            <label for="admin-period">Periode</label>
                            <select id="admin-period" name="period" class="form-control form-control-sm">
                                <option value="day" @selected($filters['period'] === 'day')>Harian</option>
                                <option value="week" @selected($filters['period'] === 'week')>Mingguan</option>
                                <option value="month" @selected($filters['period'] === 'month')>Bulanan</option>
                            </select>
                        </div>
                        <div class="form-group col-xl-2 col-md-4 mb-2">
                            <label for="admin-date">Tanggal acuan</label>
                            <select id="admin-date" name="date" class="form-control form-control-sm" @disabled(empty($pjjDates)) required>
                                @forelse ($pjjDates as $pjjDate)
                                    <option value="{{ $pjjDate }}" @selected($filters['date']->toDateString() === $pjjDate)>{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                                @empty
                                    <option value="">Belum ada tanggal PJJ yang diatur</option>
                                @endforelse
                            </select>
                        </div>
                        @if (in_array($section, ['tasks', 'teacher-attendance'], true))
                            <div class="form-group col-xl-3 col-md-4 mb-2">
                                <label for="admin-teacher">Guru</label>
                                <select id="admin-teacher" name="teacher_id" class="form-control form-control-sm">
                                    <option value="">Semua guru</option>
                                    @foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(request('teacher_id') == $teacher->id)>{{ $teacher->nama_lengkap }}</option>@endforeach
                                </select>
                            </div>
                        @endif
                        @if (in_array($section, ['tasks', 'teacher-attendance'], true))
                            <div class="form-group col-xl-2 col-md-4 mb-2">
                                <label for="admin-classroom">Kelas</label>
                                <select id="admin-classroom" name="classroom_id" class="form-control form-control-sm">
                                    <option value="">Semua kelas</option>
                                    @foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->nama_kelas }}</option>@endforeach
                                </select>
                            </div>
                        @endif
                        @if ($section === 'tasks')
                            <div class="form-group col-xl-2 col-md-4 mb-2">
                                <label for="admin-subject">Mata pelajaran</label>
                                <select id="admin-subject" name="subject_id" class="form-control form-control-sm">
                                    <option value="">Semua mapel</option>
                                    @foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected(request('subject_id') == $subject->id)>{{ $subject->nama_mapel }}</option>@endforeach
                                </select>
                            </div>
                        @elseif ($section === 'student-attendance')
                            <div class="form-group col-xl-3 col-md-4 mb-2">
                                <label for="attendance-classroom">Kelas</label>
                                <select id="attendance-classroom" name="classroom_id" class="form-control form-control-sm">
                                    <option value="">Semua kelas</option>
                                    @foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->nama_kelas }}</option>@endforeach
                                </select>
                            </div>
                        @endif
                        <div class="form-group col-md-auto mb-2">
                            <button type="button" id="apply-pjj-admin-filters" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                            <button type="button" id="reset-pjj-admin-filters" class="btn btn-outline-secondary btn-sm">Reset</button>
                        </div>
                    </form>

                    @if ($section === 'tasks')
                        <div class="table-responsive"><table id="pjj-admin-table" class="table table-bordered table-striped table-hover w-100">
                            <thead><tr><th>Tanggal</th><th>Guru</th><th>Kelas</th><th>Mata Pelajaran</th><th>Tugas</th><th>Deadline</th><th>Pengumpulan</th><th>Lampiran</th></tr></thead>
                        </table></div>
                    @elseif ($section === 'teacher-attendance')
                        <div class="table-responsive"><table id="pjj-admin-table" class="table table-bordered table-striped table-hover w-100">
                            <thead><tr><th>Nama Guru</th><th>NIP</th><th>Jumlah Tugas</th><th>Status Kehadiran</th></tr></thead>
                        </table></div>
                    @elseif ($section === 'student-attendance')
                        <div class="table-responsive"><table id="pjj-admin-table" class="table table-bordered table-striped table-hover w-100">
                            <thead><tr><th>Tanggal</th><th>Nama Siswa</th><th>NIS</th><th>Kelas</th><th>Jam Datang</th><th>Jam Pulang</th><th>Bukti Selfie</th><th>Lokasi Foto</th></tr></thead>
                        </table></div>
                    @endif
                </div>
            @endif
        </div>
    @endif
@stop

@section('js')
    @if ($tableRoute)
        <script>
            $(function () {
                const form = $('#pjj-admin-filter-form');
                const table = $('#pjj-admin-table').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 25,
                    lengthMenu: [10, 25, 50, 100],
                    ajax: {
                        url: @json(route($tableRoute)),
                        data: function (data) {
                            form.serializeArray().forEach(function (item) { data[item.name] = item.value; });
                        }
                    },
                    columns: @json($datatableColumns),
                    order: [],
                    language: {
                        processing: 'Memuat data...',
                        search: 'Cari:',
                        lengthMenu: 'Tampilkan _MENU_ data',
                        info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                        infoEmpty: 'Tidak ada data',
                        zeroRecords: 'Data tidak ditemukan',
                        emptyTable: 'Belum ada data',
                        paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                    }
                });
                $('#apply-pjj-admin-filters').on('click', function () { table.ajax.reload(); });
                $('#reset-pjj-admin-filters').on('click', function () {
                    form.find('select').val('');
                    form.find('select[name="date"]').val(@json($filters['date']->toDateString()));
                    form.find('select[name="period"]').val(@json($filters['period']));
                    table.ajax.reload();
                });
                form.on('change', 'select, input', function () { table.ajax.reload(); });
            });
        </script>
    @endif
@stop
