@extends('adminlte::page')

@section('title', 'Penilaian Akhir')
@section('plugins.Datatables', true)

@section('content_header')
    <h1>Penilaian Akhir</h1>
@stop

@section('content')
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('guru.assessments.final') }}" class="form-row align-items-end">
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
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('guru.assessments.final.method', $assignment) }}" class="form-row align-items-end">
                    @csrf
                    <div class="form-group col-md-5">
                        <label>Metode nilai akhir</label>
                        <select name="grading_method" class="form-control form-control-sm">
                            <option value="manual" @selected(($assignment->grading_method ?? 'manual') === 'manual')>Input manual oleh guru</option>
                            <option value="automatic" @selected(($assignment->grading_method ?? 'manual') === 'automatic')>Otomatis: rata-rata berbobot nilai harian</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3"><button class="btn btn-primary btn-sm">Simpan Metode</button></div>
                </form>
                <p class="text-muted">
                    Nilai otomatis memakai skor yang dinormalisasi ke skala 0–100 dan dirata-rata berdasarkan bobot setiap project/tugas.
                </p>
                <table id="finalGradesTable" class="table table-bordered table-striped table-sm w-100"
                    data-url="{{ route('guru.assessments.final.data') }}"
                    data-save-url="{{ url('/guru/penilaian-akhir/' . $assignment->id . '/scores') }}">
                    <thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Rata-rata Nilai Harian</th><th>Nilai Akhir</th><th>Metode</th></tr></thead>
                </table>
                <input type="hidden" id="teacherSubjectId" value="{{ $assignment->id }}">
            </div>
        </div>
    @endif
@stop

@if ($assignment)
    @section('js')
        <script>
            $(function () {
                const table = $('#finalGradesTable');
                const csrf = @json(csrf_token());
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
            });
        </script>
    @stop
@endif
