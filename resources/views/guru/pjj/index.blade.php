@extends('adminlte::page')

@section('title', 'Tugas PJJ Guru')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

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
                                <option value="{{ $teacherSubject->id }}" data-subject-id="{{ $teacherSubject->subject_id }}" data-tingkat="{{ $teacherSubject->classroom->tingkat }}" data-classroom="{{ $teacherSubject->classroom->nama_kelas }}" @selected(old('teacher_subject_id') == $teacherSubject->id)>{{ $teacherSubject->classroom->nama_kelas }} — {{ $teacherSubject->subject->nama_mapel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-4">
                        <label for="task_type">Metode tugas</label>
                        <select id="task_type" name="task_type" class="form-control form-control-sm" required>
                            @foreach (\App\Support\PjjTaskType::LABELS as $taskTypeValue => $taskTypeLabel)
                                <option value="{{ $taskTypeValue }}" @selected(old('task_type', 'essay') === $taskTypeValue)>{{ $taskTypeLabel }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted" id="task-type-hint"></small>
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
                    <div class="form-group col-12 d-none" id="extra-classes-group">
                        <label class="mb-1">Bagikan juga ke kelas lain (mata pelajaran dan jenjang sama)</label>
                        <div id="extra-classes-options" class="d-flex flex-wrap"></div>
                        <small class="form-text text-muted">Centang kelas lain yang Anda ampu dengan jenjang yang sama (contoh: X dengan X) agar tugas yang sama otomatis dipublikasikan ke kelas tersebut tanpa perlu mengunggah ulang.</small>
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

                {{-- ============ BUILDER SOAL PER METODE ============ --}}
                <input type="hidden" id="questions-json" name="questions" value="{{ old('questions') }}">

                <div id="builder-multiple_choice" class="pjj-builder d-none border rounded p-3 mb-2 bg-light">
                    <h6 class="mb-2"><i class="fas fa-list-ol mr-1"></i> Soal Pilihan Ganda</h6>
                    <div id="mc-questions"></div>
                    <button type="button" id="mc-add" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus"></i> Tambah soal</button>
                </div>

                <div id="builder-matching" class="pjj-builder d-none border rounded p-3 mb-2 bg-light">
                    <h6 class="mb-2"><i class="fas fa-random mr-1"></i> Pasangan yang Harus Dicocokkan</h6>
                    <small class="text-muted d-block mb-2">Isi pasangan kiri–kanan yang benar. Saat dikerjakan, sisi kanan akan diacak dan siswa menghubungkannya.</small>
                    <div id="match-pairs"></div>
                    <button type="button" id="match-add" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus"></i> Tambah pasangan</button>
                </div>

                <div id="builder-sequence" class="pjj-builder d-none border rounded p-3 mb-2 bg-light">
                    <h6 class="mb-2"><i class="fas fa-sort-numeric-down mr-1"></i> Urutan yang Benar</h6>
                    <div class="form-group">
                        <label>Instruksi pengurutan</label>
                        <input id="seq-instruction" class="form-control form-control-sm" maxlength="500" placeholder="contoh: Urutkan tahapan metamorfosis kupu-kupu">
                    </div>
                    <small class="text-muted d-block mb-2">Tuliskan langkah-langkah dalam <strong>urutan yang benar</strong>. Sistem akan mengacaknya saat dikerjakan siswa.</small>
                    <div id="seq-items"></div>
                    <button type="button" id="seq-add" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus"></i> Tambah langkah</button>
                </div>

                <div id="builder-fill_blank" class="pjj-builder d-none border rounded p-3 mb-2 bg-light">
                    <h6 class="mb-2"><i class="fas fa-i-cursor mr-1"></i> Teks Rumpang</h6>
                    <div class="form-group">
                        <label>Teks soal — tulis jawaban di dalam kurung siku <code>[jawaban]</code></label>
                        <textarea id="fb-text" class="form-control form-control-sm" rows="4" placeholder="contoh: Ibu kota Indonesia adalah [Jakarta] dan mata uangnya adalah [Rupiah]."></textarea>
                        <small class="text-muted">Setiap <code>[...]</code> menjadi satu bagian rumpang yang harus diisi siswa dari bank kata.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Kata pengecoh tambahan (opsional, pisahkan dengan koma)</label>
                        <input id="fb-distractors" class="form-control form-control-sm" placeholder="contoh: Bandung, Dolar, Surabaya">
                    </div>
                </div>

                <div id="builder-hotspot" class="pjj-builder d-none border rounded p-3 mb-2 bg-light">
                    <h6 class="mb-2"><i class="fas fa-map-marker-alt mr-1"></i> Titik Label pada Gambar</h6>
                    <small class="text-muted d-block mb-2">Unggah gambar pada kolom lampiran, lalu klik pada pratinjau untuk menandai titik dan beri nama label (contoh: nama provinsi pada peta buta).</small>
                    <div id="hotspot-preview-wrap" class="position-relative d-inline-block mb-2" style="max-width:100%;">
                        <img id="hotspot-preview" class="img-fluid border rounded d-none" style="max-height:420px; cursor: crosshair;" alt="Pratinjau gambar">
                        <div id="hotspot-placeholder" class="border rounded p-4 text-muted text-center">Pilih gambar pada kolom lampiran untuk mulai menandai.</div>
                    </div>
                    <ul id="hotspot-list" class="list-unstyled small mb-2"></ul>
                    <div class="form-group mb-0" style="max-width:260px;">
                        <label>Toleransi jarak klik siswa (% ukuran gambar)</label>
                        <input id="hotspot-tolerance" type="number" min="5" max="25" value="12" class="form-control form-control-sm">
                    </div>
                </div>

                <div id="builder-category_sort" class="pjj-builder d-none border rounded p-3 mb-2 bg-light">
                    <h6 class="mb-2"><i class="fas fa-layer-group mr-1"></i> Kategori dan Item</h6>
                    <small class="text-muted d-block mb-2">Buat kategori beserta item yang benar di dalamnya. Seluruh item akan diacak dan siswa mengelompokkannya kembali.</small>
                    <div id="cat-categories"></div>
                    <button type="button" id="cat-add" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus"></i> Tambah kategori</button>
                </div>

                @if ($teacherSubjects->isEmpty())
                    <div class="alert alert-info mb-0">Tidak ada kelas dan mata pelajaran yang Anda ampu pada jadwal hari ini. Tugas PJJ hanya dapat dipublikasikan sesuai jadwal pembelajaran biasa.</div>
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

    <div class="modal fade" id="pjj-edit-modal" tabindex="-1" role="dialog" aria-labelledby="pjj-edit-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form id="pjj-edit-form" method="POST" enctype="multipart/form-data" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="pjj-edit-modal-label"><i class="fas fa-edit mr-1"></i> Edit Tugas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit-title">Judul tugas</label>
                        <input id="edit-title" name="title" class="form-control form-control-sm" maxlength="180" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-instructions">Materi / instruksi teks</label>
                        <textarea id="edit-instructions" name="instructions" class="form-control form-control-sm" rows="4" maxlength="10000" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit-due-at">Batas pengumpulan (opsional)</label>
                            <input id="edit-due-at" type="datetime-local" name="due_at" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit-attachment">Ganti lampiran (maks. 10 MB)</label>
                            <div class="custom-file">
                                <input id="edit-attachment" type="file" name="attachment" class="custom-file-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                <label class="custom-file-label" for="edit-attachment">Pilih file</label>
                            </div>
                            <div id="edit-attachment-info" class="mt-1 d-none">
                                <a id="edit-attachment-link" href="#" class="btn btn-xs btn-outline-primary">Lihat lampiran saat ini</a>
                                <div class="custom-control custom-checkbox d-inline-block ml-2">
                                    <input type="checkbox" class="custom-control-input" id="edit-remove-attachment" name="remove_attachment" value="1">
                                    <label class="custom-control-label" for="edit-remove-attachment">Hapus lampiran</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-save mr-1"></i> Simpan perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <form id="pjj-delete-form" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@stop

@section('js')
    <script>
        $(function () {
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: @json(session('success')),
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            @endif
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
            const oldExtraIds = @json(collect(old('extra_teacher_subject_ids', []))->map(fn($id) => (int) $id)->values());
            function refreshExtraClasses() {
                const select = $('#teacher_subject_id');
                const selected = select.find('option:selected');
                const group = $('#extra-classes-group');
                const container = $('#extra-classes-options');
                container.empty();
                if (!selected.val()) {
                    group.addClass('d-none');
                    return;
                }
                const subjectId = selected.data('subject-id');
                const tingkat = selected.data('tingkat');
                let count = 0;
                select.find('option').each(function () {
                    const option = $(this);
                    if (!option.val() || option.val() === selected.val() || option.data('subject-id') !== subjectId || option.data('tingkat') !== tingkat) {
                        return;
                    }
                    count++;
                    const checked = oldExtraIds.includes(parseInt(option.val(), 10)) ? ' checked' : '';
                    container.append(
                        '<div class="custom-control custom-checkbox mr-3">'
                        + '<input type="checkbox" class="custom-control-input" id="extra-class-' + option.val() + '" name="extra_teacher_subject_ids[]" value="' + option.val() + '"' + checked + '>'
                        + '<label class="custom-control-label" for="extra-class-' + option.val() + '">' + $('<span>').text(option.data('classroom')).html() + '</label>'
                        + '</div>'
                    );
                });
                group.toggleClass('d-none', count === 0);
            }
            $('#teacher_subject_id').on('change', refreshExtraClasses);
            refreshExtraClasses();
            $('#pjj-teacher-assignments').on('click', '.pjj-edit-assignment', function () {
                const button = $(this);
                $('#pjj-edit-form').attr('action', button.data('url'));
                $('#edit-title').val(button.data('title'));
                $('#edit-instructions').val(button.data('instructions'));
                $('#edit-due-at').val(button.data('due-at'));
                $('#edit-attachment').val('');
                $('#edit-attachment').next('.custom-file-label').text('Pilih file');
                $('#edit-remove-attachment').prop('checked', false);
                const attachment = button.data('attachment');
                if (attachment) {
                    $('#edit-attachment-link').attr('href', attachment);
                    $('#edit-attachment-info').removeClass('d-none');
                } else {
                    $('#edit-attachment-info').addClass('d-none');
                }
                $('#pjj-edit-modal').modal('show');
            });
            $('#pjj-teacher-assignments').on('click', '.pjj-delete-assignment', function () {
                const button = $(this);
                const submissions = parseInt(button.data('submissions'), 10) || 0;
                let message = 'Tugas "' + button.data('title') + '" akan dihapus permanen.';
                if (submissions > 0) {
                    message += ' Terdapat ' + submissions + ' jawaban siswa yang juga akan terhapus.';
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus tugas?',
                    text: message,
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then(function (result) {
                    if (result.value) {
                        $('#pjj-delete-form').attr('action', button.data('url')).trigger('submit');
                    }
                });
            });

            // ================= BUILDER SOAL PER METODE =================
            const TASK_HINTS = {
                essay: 'Siswa menjawab dengan teks panjang; dinilai manual oleh guru.',
                upload: 'Siswa mengunggah file jawaban (PDF/gambar); dinilai manual oleh guru.',
                multiple_choice: 'Dinilai otomatis. Susun soal dan tentukan kunci jawabannya di bawah.',
                matching: 'Dinilai otomatis. Siswa mencocokkan pasangan kiri–kanan (drag & drop).',
                sequence: 'Dinilai otomatis. Siswa mengurutkan langkah yang diacak sistem.',
                fill_blank: 'Dinilai otomatis. Siswa mengisi bagian rumpang dari bank kata.',
                hotspot: 'Dinilai otomatis. Siswa menandai titik label pada gambar (wajib lampiran gambar).',
                category_sort: 'Dinilai otomatis. Siswa mengelompokkan item ke kategori yang benar.'
            };
            const INTERACTIVE_TYPES = ['multiple_choice', 'matching', 'sequence', 'fill_blank', 'hotspot', 'category_sort'];

            function escapeHtml(text) { return $('<span>').text(text == null ? '' : String(text)).html(); }

            function switchBuilder() {
                const type = $('#task_type').val();
                $('#task-type-hint').text(TASK_HINTS[type] || '');
                $('.pjj-builder').addClass('d-none');
                $('#builder-' + type).removeClass('d-none');
            }
            $('#task_type').on('change', switchBuilder);

            // --- Pilihan Ganda ---
            let mcCount = 0;
            function mcAdd(data) {
                const index = mcCount++;
                const options = (data && data.options) || ['', '', '', ''];
                let optionsHtml = '';
                options.forEach(function (opt, i) {
                    optionsHtml += '<div class="input-group input-group-sm mb-1">'
                        + '<div class="input-group-prepend"><div class="input-group-text"><input type="radio" name="mc-correct-' + index + '" value="' + i + '"' + (data && data.correct === i ? ' checked' : '') + ' title="Kunci jawaban"></div></div>'
                        + '<input class="form-control mc-option" value="' + escapeHtml(opt) + '" placeholder="Pilihan ' + String.fromCharCode(65 + i) + '">'
                        + '</div>';
                });
                $('#mc-questions').append(
                    '<div class="mc-question border rounded p-2 mb-2 bg-white" data-index="' + index + '">'
                    + '<div class="d-flex justify-content-between mb-1"><strong class="mc-number"></strong>'
                    + '<button type="button" class="btn btn-xs btn-outline-danger mc-remove"><i class="fas fa-times"></i></button></div>'
                    + '<input class="form-control form-control-sm mc-text mb-1" value="' + escapeHtml(data ? data.question : '') + '" placeholder="Tuliskan pertanyaan">'
                    + '<small class="text-muted d-block mb-1">Pilih tombol radio pada jawaban yang benar:</small>'
                    + optionsHtml
                    + '</div>'
                );
                mcRenumber();
            }
            function mcRenumber() {
                $('#mc-questions .mc-question').each(function (i) { $(this).find('.mc-number').text('Soal #' + (i + 1)); });
            }
            $('#mc-add').on('click', function () { mcAdd(); });
            $(document).on('click', '.mc-remove', function () { $(this).closest('.mc-question').remove(); mcRenumber(); });

            // --- Mencocokkan ---
            function matchAdd(left, right) {
                $('#match-pairs').append(
                    '<div class="form-row match-pair mb-1">'
                    + '<div class="col-5"><input class="form-control form-control-sm match-left" value="' + escapeHtml(left || '') + '" placeholder="Sisi kiri (soal)"></div>'
                    + '<div class="col-1 text-center pt-1"><i class="fas fa-arrows-alt-h"></i></div>'
                    + '<div class="col-5"><input class="form-control form-control-sm match-right" value="' + escapeHtml(right || '') + '" placeholder="Pasangan benar"></div>'
                    + '<div class="col-1"><button type="button" class="btn btn-xs btn-outline-danger match-remove mt-1"><i class="fas fa-times"></i></button></div>'
                    + '</div>'
                );
            }
            $('#match-add').on('click', function () { matchAdd(); });
            $(document).on('click', '.match-remove', function () { $(this).closest('.match-pair').remove(); });

            // --- Mengurutkan ---
            function seqAdd(value) {
                const number = $('#seq-items .seq-item').length + 1;
                $('#seq-items').append(
                    '<div class="input-group input-group-sm mb-1 seq-item">'
                    + '<div class="input-group-prepend"><span class="input-group-text seq-number">' + number + '</span></div>'
                    + '<input class="form-control seq-text" value="' + escapeHtml(value || '') + '" placeholder="Langkah urutan ke-' + number + '">'
                    + '<div class="input-group-append"><button type="button" class="btn btn-outline-danger seq-remove"><i class="fas fa-times"></i></button></div>'
                    + '</div>'
                );
            }
            function seqRenumber() {
                $('#seq-items .seq-item').each(function (i) { $(this).find('.seq-number').text(i + 1); });
            }
            $('#seq-add').on('click', function () { seqAdd(); });
            $(document).on('click', '.seq-remove', function () { $(this).closest('.seq-item').remove(); seqRenumber(); });

            // --- Hotspot ---
            let hotspotLabels = [];
            function hotspotRender() {
                $('#hotspot-preview-wrap .hotspot-dot').remove();
                $('#hotspot-list').empty();
                hotspotLabels.forEach(function (label, i) {
                    $('#hotspot-preview-wrap').append(
                        $('<span class="hotspot-dot badge badge-danger" style="position:absolute; transform:translate(-50%,-50%); z-index:5;">')
                            .css({ left: label.x + '%', top: label.y + '%' })
                            .text(i + 1)
                    );
                    $('#hotspot-list').append(
                        '<li>#' + (i + 1) + ' — <strong>' + escapeHtml(label.name) + '</strong> (' + label.x + '%, ' + label.y + '%) '
                        + '<button type="button" class="btn btn-xs btn-outline-danger hotspot-remove" data-i="' + i + '"><i class="fas fa-times"></i></button></li>'
                    );
                });
            }
            $('#attachment').on('change', function () {
                const file = this.files && this.files[0];
                if (file && file.type.indexOf('image/') === 0) {
                    $('#hotspot-preview').attr('src', URL.createObjectURL(file)).removeClass('d-none');
                    $('#hotspot-placeholder').addClass('d-none');
                    hotspotLabels = [];
                    hotspotRender();
                }
            });
            $('#hotspot-preview').on('click', function (event) {
                const rect = this.getBoundingClientRect();
                const x = Math.round(((event.clientX - rect.left) / rect.width) * 10000) / 100;
                const y = Math.round(((event.clientY - rect.top) / rect.height) * 10000) / 100;
                Swal.fire({
                    title: 'Nama label titik ini',
                    input: 'text',
                    inputPlaceholder: 'contoh: Jawa Barat',
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal'
                }).then(function (result) {
                    if (result.value && result.value.trim()) {
                        hotspotLabels.push({ name: result.value.trim(), x: x, y: y });
                        hotspotRender();
                    }
                });
            });
            $(document).on('click', '.hotspot-remove', function () {
                hotspotLabels.splice(parseInt($(this).data('i'), 10), 1);
                hotspotRender();
            });

            // --- Klasifikasi ---
            function catAdd(name, items) {
                $('#cat-categories').append(
                    '<div class="cat-category border rounded p-2 mb-2 bg-white">'
                    + '<div class="d-flex justify-content-between mb-1">'
                    + '<input class="form-control form-control-sm cat-name mr-2" value="' + escapeHtml(name || '') + '" placeholder="Nama kategori (contoh: Hewan Herbivora)">'
                    + '<button type="button" class="btn btn-xs btn-outline-danger cat-remove"><i class="fas fa-times"></i></button></div>'
                    + '<textarea class="form-control form-control-sm cat-items" rows="2" placeholder="Item di kategori ini, satu per baris">' + escapeHtml((items || []).join('\n')) + '</textarea>'
                    + '</div>'
                );
            }
            $('#cat-add').on('click', function () { catAdd(); });
            $(document).on('click', '.cat-remove', function () { $(this).closest('.cat-category').remove(); });

            // --- Serialize builder -> hidden input saat submit ---
            function collectQuestions(type) {
                if (type === 'multiple_choice') {
                    const items = [];
                    $('#mc-questions .mc-question').each(function () {
                        const q = $(this);
                        const options = q.find('.mc-option').map(function () { return $(this).val().trim(); }).get().filter(Boolean);
                        const correctRaw = q.find('input[type=radio]:checked').val();
                        items.push({
                            question: q.find('.mc-text').val().trim(),
                            options: options,
                            correct: correctRaw === undefined ? null : parseInt(correctRaw, 10)
                        });
                    });
                    return { items: items };
                }
                if (type === 'matching') {
                    const pairs = [];
                    $('#match-pairs .match-pair').each(function () {
                        pairs.push({ left: $(this).find('.match-left').val().trim(), right: $(this).find('.match-right').val().trim() });
                    });
                    return { pairs: pairs };
                }
                if (type === 'sequence') {
                    return {
                        instruction: $('#seq-instruction').val().trim(),
                        items: $('#seq-items .seq-text').map(function () { return $(this).val().trim(); }).get().filter(Boolean)
                    };
                }
                if (type === 'fill_blank') {
                    return {
                        text: $('#fb-text').val().trim(),
                        distractors: $('#fb-distractors').val().split(',').map(function (w) { return w.trim(); }).filter(Boolean)
                    };
                }
                if (type === 'hotspot') {
                    return { labels: hotspotLabels, tolerance: parseFloat($('#hotspot-tolerance').val()) || 12 };
                }
                if (type === 'category_sort') {
                    const categories = [];
                    $('#cat-categories .cat-category').each(function () {
                        categories.push({
                            name: $(this).find('.cat-name').val().trim(),
                            items: $(this).find('.cat-items').val().split('\n').map(function (item) { return item.trim(); }).filter(Boolean)
                        });
                    });
                    return { categories: categories };
                }
                return null;
            }

            // Baris awal agar builder tidak kosong.
            mcAdd(); matchAdd(); matchAdd(); seqAdd(); seqAdd(); catAdd(); catAdd();

            $('form[action="{{ route('guru.pjj.assignments.store') }}"]').on('submit', function () {
                const type = $('#task_type').val();
                if (INTERACTIVE_TYPES.includes(type)) {
                    $('#questions-json').val(JSON.stringify(collectQuestions(type)));
                } else {
                    $('#questions-json').val('');
                }
            });

            switchBuilder();
        });
    </script>
@stop
