@extends('adminlte::page')

@section('title', 'Penilaian Akhir')
@section('plugins.Datatables', true)

@section('content_header')
    <h1>Penilaian Akhir</h1>
@stop

@section('content')
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card">
        <div class="card-header py-2">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter</h3>
            @if ($assignment)
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#gradingMethodModal">
                        <i class="fas fa-cog mr-1"></i> Metode Nilai Akhir
                    </button>
                </div>
            @endif
        </div>
        <div class="card-body py-2">
            <form id="filterForm" method="GET" action="{{ route('guru.assessments.final') }}" class="form-row">
                <input type="hidden" name="teacher_subject_id" id="teacherSubjectInput" value="{{ $assignment?->id }}">
                <div class="form-group col-md-4 mb-1">
                    <label class="mb-0 small">Jurusan</label>
                    <select id="filterMajor" class="form-control form-control-sm"></select>
                </div>
                <div class="form-group col-md-4 mb-1">
                    <label class="mb-0 small">Kelas</label>
                    <select id="filterClassroom" class="form-control form-control-sm"></select>
                </div>
                <div class="form-group col-md-4 mb-1">
                    <label class="mb-0 small">Mata Pelajaran</label>
                    <select id="filterSubject" class="form-control form-control-sm"></select>
                </div>
            </form>
            @if ($assignment)
                <p class="text-muted small mb-0 mt-1">
                    Metode: <strong>{{ ($assignment->grading_method ?? 'manual') === 'automatic' ? 'Otomatis' : 'Manual' }}</strong>
                    · Bobot kehadiran: <strong>{{ $assignment->attendance_weight ?? 30 }}%</strong>
                    — Nilai otomatis = rata-rata nilai harian × (100% − bobot kehadiran) + persentase kehadiran mapel × bobot kehadiran.
                </p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="finalGradesTable" class="table table-bordered table-striped table-sm w-100"
                data-url="{{ route('guru.assessments.final.data') }}"
                @if ($assignment) data-save-url="{{ url('/guru/penilaian-akhir/' . $assignment->id . '/scores') }}" @endif>
                <thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Rata-rata Nilai Harian</th><th>Kehadiran Mapel (%)</th><th>Nilai Akhir</th><th>Metode</th></tr></thead>
                <tbody></tbody>
            </table>
            <input type="hidden" id="teacherSubjectId" value="{{ $assignment?->id }}">
        </div>
    </div>

    @if ($assignment)
        <div class="modal fade" id="gradingMethodModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('guru.assessments.final.method', $assignment) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Metode Nilai Akhir — {{ $assignment->classroom->nama_kelas }} · {{ $assignment->subject->nama_mapel }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Metode nilai akhir</label>
                                <select name="grading_method" class="form-control form-control-sm">
                                    <option value="manual" @selected(($assignment->grading_method ?? 'manual') === 'manual')>Input manual oleh guru</option>
                                    <option value="automatic" @selected(($assignment->grading_method ?? 'manual') === 'automatic')>Otomatis: nilai harian + kehadiran mapel</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Bobot kehadiran mapel (%)</label>
                                <input type="number" name="attendance_weight" class="form-control form-control-sm"
                                    min="0" max="100" step="0.5" value="{{ $assignment->attendance_weight ?? 30 }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                            <button class="btn btn-primary btn-sm">Simpan Metode</button>
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
                if ($(this).val() !== '') $('#filterForm').trigger('submit');
            });

            const table = $('#finalGradesTable');
            const csrf = @json(csrf_token());
            const hasAssignment = @json((bool) $assignment);
            const emptyMessage = 'Pilih jurusan, kelas, dan mata pelajaran untuk menampilkan nilai akhir siswa.';

            if (hasAssignment) {
                table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: table.data('url'),
                        data: function (data) { data.teacher_subject_id = $('#teacherSubjectId').val(); }
                    },
                    columns: [
                        { data: null, orderable: false, searchable: false,
                            render: function (_, __, ___, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                        { data: 'nis' },
                        { data: 'student' },
                        { data: 'daily_average', render: function (value) { return value ?? '-'; } },
                        { data: 'attendance_percentage', render: function (value) { return value ?? '-'; } },
                        { data: 'final_score', render: function (value, _, row) {
                            return row.grading_method === 'manual'
                                ? '<input type="number" class="form-control form-control-sm final-score" data-id="' + row.student_id
                                    + '" value="' + (value ?? '') + '" min="0" max="100" step="0.01">'
                                : (value ?? '-');
                        } },
                        { data: 'grading_method', render: function (value) { return value === 'automatic' ? 'Otomatis' : 'Manual'; } }
                    ]
                });
                table.on('change', '.final-score', function () {
                    const input = $(this);
                    $.ajax({
                        url: table.data('save-url') + '/' + input.data('id'),
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf },
                        data: { score: input.val() }
                    }).done(function () { toastr.success('Nilai akhir tersimpan.'); })
                      .fail(function (xhr) { toastr.error(xhr.responseJSON?.message || 'Nilai akhir gagal disimpan.'); });
                });
            } else {
                table.DataTable({
                    data: [],
                    columns: [{ data: null }, { data: null }, { data: null }, { data: null }, { data: null }, { data: null }, { data: null }],
                    language: { emptyTable: emptyMessage, zeroRecords: emptyMessage }
                });
            }
        });
    </script>
@stop
