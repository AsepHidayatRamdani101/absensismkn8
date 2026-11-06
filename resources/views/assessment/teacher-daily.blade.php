@extends('adminlte::page')

@section('title', 'Penilaian Harian')
@section('plugins.Datatables', true)

@section('content_header')
    <h1>Penilaian Harian</h1>
@stop

@section('content')
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card">
        <div class="card-header py-2">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter</h3>
            @if ($assignment)
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createAssessmentModal">
                        <i class="fas fa-plus mr-1"></i> Buat Penilaian
                    </button>
                </div>
            @endif
        </div>
        <div class="card-body py-2">
            <form id="filterForm" method="GET" action="{{ route('guru.assessments.daily') }}" class="form-row">
                <input type="hidden" name="teacher_subject_id" id="teacherSubjectInput" value="{{ $assignment?->id }}">
                <div class="form-group col-md-3 mb-1">
                    <label class="mb-0 small">Jurusan</label>
                    <select id="filterMajor" class="form-control form-control-sm"></select>
                </div>
                <div class="form-group col-md-3 mb-1">
                    <label class="mb-0 small">Kelas</label>
                    <select id="filterClassroom" class="form-control form-control-sm"></select>
                </div>
                <div class="form-group col-md-3 mb-1">
                    <label class="mb-0 small">Mata Pelajaran</label>
                    <select id="filterSubject" class="form-control form-control-sm"></select>
                </div>
                <div class="form-group col-md-3 mb-1">
                    <label class="mb-0 small">Penilaian (Project/Tugas)</label>
                    <select name="assessment_id" id="assessment_id" class="form-control form-control-sm" @disabled(!$assignment)>
                        <option value="">Pilih penilaian</option>
                        @foreach ($assessments as $assessmentItem)
                            <option value="{{ $assessmentItem->id }}"
                                @selected((int) request('assessment_id') === $assessmentItem->id)>
                                {{ $assessmentItem->assessment_date->format('d-m-Y') }} - {{ $assessmentItem->title }}
                                ({{ $assessmentItem->type === 'project' ? 'Project' : 'Tugas' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="dailyScoresTable" class="table table-bordered table-striped table-sm w-100"
                data-url="{{ route('guru.assessments.daily.data') }}"
                data-save-url="{{ url('/guru/penilaian-harian') }}">
                <thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Nilai</th><th>Simpan</th></tr></thead>
                <tbody></tbody>
            </table>
            <input type="hidden" id="assessmentId" value="{{ request('assessment_id') }}">
            <input type="hidden" id="teacherSubjectId" value="{{ $assignment?->id }}">
        </div>
    </div>

    @if ($assignment)
        <div class="modal fade" id="createAssessmentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('guru.assessments.daily.store') }}">
                        @csrf
                        <input type="hidden" name="teacher_subject_id" value="{{ $assignment->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title">Buat Penilaian — {{ $assignment->classroom->nama_kelas }} · {{ $assignment->subject->nama_mapel }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Project/Tugas</label>
                                <input name="title" class="form-control form-control-sm" maxlength="150" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Jenis</label>
                                    <select name="type" class="form-control form-control-sm">
                                        <option value="project">Project</option><option value="task">Tugas</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Tanggal</label>
                                    <input type="date" name="assessment_date" class="form-control form-control-sm"
                                        value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Skor Maksimum</label>
                                    <input type="number" name="max_score" class="form-control form-control-sm"
                                        value="100" min="1" max="1000" step="0.01" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Bobot</label>
                                    <input type="number" name="weight" class="form-control form-control-sm"
                                        value="1" min="0.01" max="1000" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                            <button class="btn btn-success btn-sm">Buat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@stop

@php
    $assignmentOptions = $assignments->map(function ($item) {
        return [
            'id' => $item->id,
            'major_id' => $item->classroom?->major_id,
            'major' => $item->classroom?->major?->nama_jurusan ?? 'Tanpa Jurusan',
            'classroom_id' => $item->classroom_id,
            'classroom' => $item->classroom?->nama_kelas,
            'subject' => $item->subject?->nama_mapel,
        ];
    })->values();
@endphp

@section('js')
    <script>
        $(function () {
            const assignments = @json($assignmentOptions);
            const selectedId = @json($assignment?->id);
            const current = assignments.find(a => a.id === selectedId) || null;
            const majorSel = $('#filterMajor'), classSel = $('#filterClassroom'), subjSel = $('#filterSubject');

            function fill(select, items, selectedVal, placeholder) {
                select.empty().append($('<option>').val('').text(placeholder));
                items.forEach(i => select.append($('<option>').val(i.value).text(i.text)));
                select.val(selectedVal !== null && selectedVal !== undefined ? String(selectedVal) : '');
            }
            function uniqueBy(list, key) {
                const seen = new Set();
                return list.filter(i => !seen.has(i[key]) && seen.add(i[key]));
            }
            function fillMajors() {
                fill(majorSel, uniqueBy(assignments, 'major_id').map(a => ({ value: a.major_id ?? '', text: a.major })),
                    current?.major_id, 'Pilih jurusan');
            }
            function fillClassrooms() {
                const majorId = majorSel.val();
                const items = uniqueBy(assignments.filter(a => String(a.major_id ?? '') === majorId), 'classroom_id');
                fill(classSel, items.map(a => ({ value: a.classroom_id, text: a.classroom })),
                    current && String(current.major_id ?? '') === majorId ? current.classroom_id : null, 'Pilih kelas');
                classSel.prop('disabled', majorId === '');
            }
            function fillSubjects() {
                const classroomId = classSel.val();
                const items = assignments.filter(a => String(a.classroom_id) === classroomId);
                fill(subjSel, items.map(a => ({ value: a.id, text: a.subject })),
                    current && String(current.classroom_id) === classroomId ? current.id : null, 'Pilih mapel');
                subjSel.prop('disabled', classroomId === '');
            }
            fillMajors(); fillClassrooms(); fillSubjects();
            majorSel.on('change', function () { fillClassrooms(); fillSubjects(); });
            classSel.on('change', fillSubjects);
            subjSel.on('change', function () {
                $('#teacherSubjectInput').val($(this).val());
                $('#assessment_id').val('');
                if ($(this).val() !== '') $('#filterForm').trigger('submit');
            });
            $('#assessment_id').on('change', function () { $('#filterForm').trigger('submit'); });

            const table = $('#dailyScoresTable');
            const csrf = @json(csrf_token());
            const hasAssessment = @json((bool) ($assignment && request()->filled('assessment_id')));
            const emptyMessage = 'Pilih jurusan, kelas, mata pelajaran, dan penilaian untuk menampilkan nilai siswa.';

            if (hasAssessment) {
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
            } else {
                table.DataTable({
                    data: [],
                    columns: [{ data: null }, { data: null }, { data: null }, { data: null }, { data: null }],
                    language: { emptyTable: emptyMessage, zeroRecords: emptyMessage }
                });
            }
        });
    </script>
@stop
