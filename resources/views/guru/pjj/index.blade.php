@extends('adminlte::page')

@section('title', 'Tugas PJJ Guru')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">Pembelajaran PJJ</h1>
            <p class="text-muted mb-0">{{ $teacher->nama_lengkap }} · Kelola tugas dan penilaian siswa.</p>
        </div>
        <a href="{{ route('guru.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Dashboard Guru</a>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><strong>Periksa kembali data:</strong><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if (!$isPjjActiveToday)
        <div class="alert alert-warning"><i class="fas fa-info-circle mr-1"></i> Mode PJJ tidak aktif hari ini. Tugas baru hanya dapat dipublikasikan pada hari PJJ.</div>
    @endif

    <div class="card card-primary card-outline">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Publikasikan Tugas</h3></div>
        <form method="POST" action="{{ route('guru.pjj.assignments.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-lg-4">
                        <label for="teacher_subject_id">Kelas dan Mata Pelajaran</label>
                        <select id="teacher_subject_id" name="teacher_subject_id" class="form-control form-control-sm" required>
                            <option value="">Pilih kelas dan mata pelajaran</option>
                            @foreach ($teacherSubjects as $teacherSubject)
                                <option value="{{ $teacherSubject->id }}" @selected(old('teacher_subject_id') == $teacherSubject->id)>{{ $teacherSubject->classroom->nama_kelas }} — {{ $teacherSubject->subject->nama_mapel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-4">
                        <label for="title">Judul tugas</label>
                        <input id="title" name="title" class="form-control form-control-sm" maxlength="180" value="{{ old('title') }}" required>
                    </div>
                    <div class="form-group col-lg-4">
                        <label for="due_at">Batas pengumpulan (opsional)</label>
                        <input id="due_at" type="datetime-local" name="due_at" class="form-control form-control-sm" value="{{ old('due_at') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-lg-8">
                        <label for="instructions">Materi / instruksi teks</label>
                        <textarea id="instructions" name="instructions" class="form-control form-control-sm" rows="3" maxlength="10000" required>{{ old('instructions') }}</textarea>
                    </div>
                    <div class="form-group col-lg-4">
                        <label for="attachment">Lampiran PDF atau gambar (maks. 10 MB)</label>
                        <div class="custom-file">
                            <input id="attachment" type="file" name="attachment" class="custom-file-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <label class="custom-file-label" for="attachment">Pilih file</label>
                        </div>
                        <small class="form-text text-muted">Konten teks dapat digunakan tanpa lampiran.</small>
                    </div>
                </div>
                @if ($teacherSubjects->isEmpty())
                    <div class="alert alert-info mb-0">Akun ini belum memiliki penugasan kelas dan mata pelajaran. Hubungi admin untuk memperbarui data guru pengampu.</div>
                @endif
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">Tugas akan terlihat oleh seluruh siswa di kelas yang dipilih.</small>
                <button class="btn btn-primary btn-sm" @disabled($teacherSubjects->isEmpty() || !$isPjjActiveToday)><i class="fas fa-paper-plane mr-1"></i> Publikasikan tugas</button>
            </div>
        </form>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-list mr-1"></i> Tugas yang Saya Buat</h3></div>
        <div class="card-body">
            <form id="pjj-teacher-filter-form" class="form-row align-items-end">
                <div class="form-group col-md-2">
                    <label for="teacher-date-from">Dibuat dari</label>
                    <select id="teacher-date-from" name="date_from" class="form-control form-control-sm">
                        <option value="">Semua tanggal PJJ</option>
                        @foreach ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}">{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="teacher-date-to">Sampai</label>
                    <select id="teacher-date-to" name="date_to" class="form-control form-control-sm">
                        <option value="">Semua tanggal PJJ</option>
                        @foreach ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}">{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="teacher-subject-filter">Kelas & mata pelajaran</label>
                    <select id="teacher-subject-filter" name="teacher_subject_id" class="form-control form-control-sm">
                        <option value="">Semua penugasan</option>
                        @foreach ($teacherSubjects as $teacherSubject)<option value="{{ $teacherSubject->id }}">{{ $teacherSubject->classroom->nama_kelas }} — {{ $teacherSubject->subject->nama_mapel }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="teacher-submission-status">Status pengumpulan</label>
                    <select id="teacher-submission-status" name="submission_status" class="form-control form-control-sm">
                        <option value="">Semua</option><option value="submitted">Sudah ada pengumpulan</option><option value="pending">Belum ada pengumpulan</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <button type="button" id="teacher-filter-apply" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                    <button type="button" id="teacher-filter-reset" class="btn btn-outline-secondary btn-sm">Reset</button>
                </div>
            </form>
            <div class="table-responsive">
                <table id="pjj-teacher-assignments" class="table table-bordered table-striped table-hover w-100">
                    <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mata Pelajaran</th><th>Judul Tugas</th><th>Deadline</th><th>Pengumpulan</th><th>Aksi</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function () {
            const form = $('#pjj-teacher-filter-form');
            const table = $('#pjj-teacher-assignments').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: @json(route('guru.pjj.index')),
                    data: function (data) { form.serializeArray().forEach(function (item) { data[item.name] = item.value; }); }
                },
                columns: [
                    { data: 'date', name: 'date' },
                    { data: 'classroom', name: 'classroom', orderable: false },
                    { data: 'subject', name: 'subject', orderable: false },
                    { data: 'title', name: 'title' },
                    { data: 'deadline', name: 'deadline' },
                    { data: 'submissions', name: 'submissions', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [],
                language: {
                    processing: 'Memuat data...', search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan', emptyTable: 'Belum ada tugas',
                    paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                }
            });
            $('#teacher-filter-apply').on('click', function () { table.ajax.reload(); });
            $('#teacher-filter-reset').on('click', function () { form[0].reset(); table.ajax.reload(); });
            form.on('change', 'input, select', function () { table.ajax.reload(); });
            $(document).on('change', '.custom-file-input', function () {
                $(this).next('.custom-file-label').text(this.files[0]?.name || 'Pilih file');
            });
        });
    </script>
@stop
