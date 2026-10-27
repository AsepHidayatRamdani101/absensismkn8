@extends('adminlte::page')

@section('title', 'Penilaian Tugas PJJ')

@section('plugins.Datatables', true)

@section('css')
    <style>
        #pjj-submissions-table {
            width: 100% !important;
            table-layout: fixed;
        }

        #pjj-submissions-table th:nth-child(5),
        #pjj-submissions-table td:nth-child(5) {
            width: 150px !important;
            min-width: 150px !important;
        }

        #pjj-submissions-table td:nth-child(5) .form-control {
            width: 100%;
            min-width: 0;
        }

        #pjj-submissions-table td:nth-child(5) .pjj-grade-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">Pengumpulan dan Penilaian</h1>
            <p class="text-muted mb-0">{{ $assignment->title }} Â· {{ $assignment->teacherSubject->classroom->nama_kelas }}
                Â·
                {{ $assignment->teacherSubject->subject->nama_mapel }}</p>
        </div>
        <a href="{{ route('guru.pjj.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i>
            Kembali ke tugas</a>
    </div>
@stop

@section('content')
    <div id="pjj-grade-notice" class="alert d-none" role="alert"></div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible"><button type="button" class="close"
                data-dismiss="alert">&times;</button>{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-book-open mr-1"></i> Detail Tugas</h3>
        </div>
        <div class="card-body">
            <p class="mb-2">{{ $assignment->instructions }}</p>
            <div class="d-flex flex-wrap align-items-center">
                <span class="text-muted mr-3">Dibuat {{ $assignment->created_at->format('d-m-Y H:i') }}</span>
                <span class="text-muted mr-3">Deadline
                    {{ $assignment->due_at?->format('d-m-Y H:i') ?? 'Tidak ditentukan' }}</span>
                @if ($assignment->attachment_path)
                    <a class="btn btn-xs btn-outline-primary"
                        href="{{ route('pjj.assignment.download', $assignment) }}">Unduh materi/lampiran</a>
                @endif
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i> Jawaban Siswa</h3>
            <button type="button" id="pjj-save-all-grades" class="btn btn-success btn-sm"><i class="fas fa-save mr-1"></i>
                Simpan Semua Nilai</button>
        </div>
        <div class="card-body">
            <form id="pjj-submission-filter-form" class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label for="submission-date-from">Dikirim dari</label>
                    <select id="submission-date-from" name="date_from" class="form-control form-control-sm">
                        <option value="">Semua tanggal PJJ</option>
                        @foreach ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}">{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="submission-date-to">Sampai</label>
                    <select id="submission-date-to" name="date_to" class="form-control form-control-sm">
                        <option value="">Semua tanggal PJJ</option>
                        @foreach ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}">{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="grading-status">Status penilaian</label>
                    <select id="grading-status" name="grading_status" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="pending">Belum dinilai</option>
                        <option value="graded">Sudah dinilai</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button type="button" id="submission-filter-apply" class="btn btn-primary btn-sm"><i
                            class="fas fa-filter mr-1"></i> Terapkan</button>
                    <button type="button" id="submission-filter-reset"
                        class="btn btn-outline-secondary btn-sm">Reset</button>
                </div>
            </form>
            <div class="table-responsive">
                <table id="pjj-submissions-table" class="table table-bordered table-striped table-hover ">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Waktu pengumpulan</th>
                            <th>Jawaban teks</th>
                            <th style="width: 10%;">Lampiran</th>
                            <th style="width: 50%;">Nilai dan catatan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function() {
            const form = $('#pjj-submission-filter-form');
            const table = $('#pjj-submissions-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                autoWidth: false,
                scrollX: true,
                columnDefs: [{
                        targets: 0,
                        width: '15%'
                    }, // Siswa
                    {
                        targets: 1,
                        width: '15%'
                    }, // Waktu pengumpulan
                    {
                        targets: 2,
                        width: '12%'
                    }, // Jawaban teks
                    {
                        targets: 3,
                        width: '23%'
                    }, // Lampiran
                    {
                        targets: 4,
                        width: '350px'
                    }, // Nilai dan catatan
                    {
                        targets: 5,
                        width: '12%'
                    } // Status
                ],

                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: @json(route('guru.pjj.assignments.show', $assignment)),
                    data: function(data) {
                        form.serializeArray().forEach(function(item) {
                            data[item.name] = item.value;
                        });
                    }
                },
                columns: [{
                        data: 'student',
                        name: 'student',
                        orderable: false
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'answer',
                        name: 'answer',
                        orderable: false
                    },
                    {
                        data: 'attachment',
                        name: 'attachment',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'grading',
                        name: 'grading',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [],
                language: {
                    processing: 'Memuat data...',
                    search: 'Cari siswa/jawaban:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_â€“_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada jawaban siswa',
                    paginate: {
                        first: 'Awal',
                        last: 'Akhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya'
                    }
                }
            });
            $('#submission-filter-apply').on('click', function() {
                table.ajax.reload();
            });
            $('#submission-filter-reset').on('click', function() {
                form[0].reset();
                table.ajax.reload();
            });
            form.on('change', 'input, select', function() {
                table.ajax.reload();
            });
            $(document).on('submit', '.pjj-grade-form', function(event) {
                event.preventDefault();
                const gradeForm = $(this);
                const button = gradeForm.find('button[type="submit"], button:not([type])');
                button.prop('disabled', true);
                $.ajax({
                    url: gradeForm.attr('action'),
                    method: 'POST',
                    data: gradeForm.serialize(),
                    headers: {
                        Accept: 'application/json'
                    }
                }).done(function(response) {
                    $('#pjj-grade-notice').removeClass('d-none alert-danger').addClass(
                        'alert-success').text(response.message);
                    table.ajax.reload(null, false);
                }).fail(function(xhr) {
                    const message = xhr.responseJSON?.message ||
                        'Nilai gagal disimpan. Periksa kembali input.';
                    $('#pjj-grade-notice').removeClass('d-none alert-success').addClass(
                        'alert-danger').text(message);
                }).always(function() {
                    button.prop('disabled', false);
                });
            });
            $('#pjj-save-all-grades').on('click', function() {
                const saveAllButton = $(this);
                const grades = [];
                let invalid = false;
                $('#pjj-submissions-table .pjj-grade-form').each(function() {
                    const gradeForm = $(this);
                    const scoreInput = gradeForm.find('input[name="score"]');
                    const score = scoreInput.val();
                    if (score === '' || score === null) {
                        return;
                    }
                    if (!scoreInput[0].checkValidity()) {
                        invalid = true;
                        scoreInput.addClass('is-invalid');
                        return;
                    }
                    scoreInput.removeClass('is-invalid');
                    grades.push({
                        submission_id: gradeForm.data('submission-id'),
                        score: score,
                        feedback: gradeForm.find('input[name="feedback"]').val() || ''
                    });
                });
                if (invalid) {
                    $('#pjj-grade-notice').removeClass('d-none alert-success').addClass('alert-danger')
                        .text('Ada nilai yang tidak valid. Nilai harus antara 0 dan 100.');
                    return;
                }
                if (grades.length === 0) {
                    $('#pjj-grade-notice').removeClass('d-none alert-success').addClass('alert-danger')
                        .text(
                            'Belum ada nilai yang diisi. Isi kolom nilai terlebih dahulu lalu klik Simpan Semua Nilai.'
                        );
                    return;
                }
                saveAllButton.prop('disabled', true);
                $.ajax({
                    url: @json(route('guru.pjj.submissions.grade-bulk', $assignment)),
                    method: 'POST',
                    data: {
                        _token: @json(csrf_token()),
                        grades: grades
                    },
                    headers: {
                        Accept: 'application/json'
                    }
                }).done(function(response) {
                    $('#pjj-grade-notice').removeClass('d-none alert-danger').addClass(
                        'alert-success').text(response.message);
                    table.ajax.reload(null, false);
                }).fail(function(xhr) {
                    const message = xhr.responseJSON?.message ||
                        'Nilai gagal disimpan. Periksa kembali input.';
                    $('#pjj-grade-notice').removeClass('d-none alert-success').addClass(
                        'alert-danger').text(message);
                }).always(function() {
                    saveAllButton.prop('disabled', false);
                });
            });
        });
    </script>
@stop
