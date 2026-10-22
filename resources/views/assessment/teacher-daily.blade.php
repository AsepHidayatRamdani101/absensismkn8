@extends('adminlte::page')

@section('title', 'Penilaian Harian')
@section('plugins.Datatables', true)

@section('content_header')
    <h1>Penilaian Harian</h1>
@stop

@section('content')
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('guru.assessments.daily') }}" class="form-row align-items-end">
                <div class="form-group col-md-6">
                    <label>Kelas dan Mata Pelajaran</label>
                    <select name="teacher_subject_id" class="form-control form-control-sm" required>
                        <option value="">Pilih penugasan</option>
                        @foreach ($assignments as $item)
                            <option value="{{ $item->id }}" @selected($assignment?->id === $item->id)>
                                {{ $item->classroom->nama_kelas }} - {{ $item->subject->nama_mapel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3"><button class="btn btn-primary btn-sm">Tampilkan</button></div>
            </form>
        </div>
    </div>

    @if ($assignment)
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Buat Penilaian</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('guru.assessments.daily.store') }}" class="form-row align-items-end">
                    @csrf
                    <input type="hidden" name="teacher_subject_id" value="{{ $assignment->id }}">
                    <div class="form-group col-md-3">
                        <label>Nama Project/Tugas</label>
                        <input name="title" class="form-control form-control-sm" maxlength="150" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Jenis</label>
                        <select name="type" class="form-control form-control-sm">
                            <option value="project">Project</option><option value="task">Tugas</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Tanggal</label><input type="date" name="assessment_date" class="form-control form-control-sm"
                            value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Skor Maksimum</label><input type="number" name="max_score" class="form-control form-control-sm"
                            value="100" min="1" max="1000" step="0.01" required>
                    </div>
                    <div class="form-group col-md-1">
                        <label>Bobot</label><input type="number" name="weight" class="form-control form-control-sm"
                            value="1" min="0.01" max="1000" step="0.01" required>
                    </div>
                    <div class="form-group col-md-2"><button class="btn btn-success btn-sm">Buat</button></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('guru.assessments.daily') }}" class="form-row align-items-end mb-3">
                    <input type="hidden" name="teacher_subject_id" value="{{ $assignment->id }}">
                    <div class="form-group col-md-6">
                        <label>Penilaian yang akan diisi</label>
                        <select name="assessment_id" id="assessment_id" class="form-control form-control-sm">
                            <option value="">Pilih Project/Tugas</option>
                            @foreach ($assessments as $assessmentItem)
                                <option value="{{ $assessmentItem->id }}"
                                    @selected((int) request('assessment_id') === $assessmentItem->id)>
                                    {{ $assessmentItem->assessment_date->format('d-m-Y') }} - {{ $assessmentItem->title }}
                                    ({{ $assessmentItem->type === 'project' ? 'Project' : 'Tugas' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3"><button class="btn btn-primary btn-sm">Buka Nilai</button></div>
                </form>
                @if (request()->filled('assessment_id'))
                    <table id="dailyScoresTable" class="table table-bordered table-striped table-sm w-100"
                        data-url="{{ route('guru.assessments.daily.data') }}"
                        data-save-url="{{ url('/guru/penilaian-harian') }}">
                        <thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Nilai</th><th>Simpan</th></tr></thead>
                    </table>
                    <input type="hidden" id="assessmentId" value="{{ request('assessment_id') }}">
                    <input type="hidden" id="teacherSubjectId" value="{{ $assignment->id }}">
                @endif
            </div>
        </div>
    @endif
@stop

@if ($assignment && request()->filled('assessment_id'))
    @section('js')
        <script>
            $(function () {
                const table = $('#dailyScoresTable');
                const csrf = @json(csrf_token());
                table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: table.data('url'),
                        data: function (data) {
                            data.teacher_subject_id = $('#teacherSubjectId').val();
                            data.assessment_id = $('#assessmentId').val();
                        }
                    },
                    columns: [
                        { data: null, orderable: false, searchable: false,
                            render: function (_, __, ___, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                        { data: 'nis' },
                        { data: 'student' },
                        { data: 'score', orderable: false, searchable: false,
                            render: function (value, _, row) {
                                return '<input type="number" class="form-control form-control-sm score-input" data-id="'
                                    + row.student_id + '" value="' + (value ?? '') + '" min="0" step="0.01">';
                            } },
                        { data: 'student_id', orderable: false, searchable: false,
                            render: function (id) { return '<button type="button" class="btn btn-success btn-sm save-score" data-id="' + id + '">Simpan</button>'; } }
                    ]
                });
                table.on('click', '.save-score', function () {
                    const studentId = $(this).data('id');
                    const score = table.find('.score-input[data-id="' + studentId + '"]').val();
                    $.ajax({
                        url: table.data('save-url') + '/' + $('#assessmentId').val() + '/students/' + studentId,
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf },
                        data: { score: score }
                    }).done(function () {
                        toastr.success('Nilai tersimpan.');
                    }).fail(function (xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Nilai gagal disimpan.');
                    });
                });
            });
        </script>
    @stop
@endif
