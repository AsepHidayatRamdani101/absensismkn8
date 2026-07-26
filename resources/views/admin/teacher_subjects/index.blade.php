@extends('adminlte::page')

@section('title', 'Data Guru Pengampu')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Master Data Guru Pengampu</h1>

        <div>
            <a href="{{ route('teacher-subjects.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('teacher-subjects.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportTeacherSubjects" action="{{ route('teacher-subjects.import') }}" method="POST"
                enctype="multipart/form-data" class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportTeacherSubjects" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportTeacherSubjects" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>

            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Guru Pengampu
            </button>

            <button id="btnDeleteMultipleTeacherSubjects" class="btn btn-danger d-none">
                <i class="fas fa-trash"></i>
                Hapus Terpilih (<span id="selectedCountTeacherSubjects">0</span>)
            </button>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->has('file'))
                <div class="alert alert-danger">{{ $errors->first('file') }}</div>
            @endif

            {{-- FILTER --}}
            <div class="row mb-3">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label for="filterTeacher" class="mb-1">Filter Guru</label>
                    <select id="filterTeacher" class="form-control form-control-sm">
                        <option value="">Semua Guru</option>
                        @foreach ($teacherSubjects->unique('teacher_id')->pluck('teacher')->sortBy('nama_lengkap') as $teacher)
                            <option value="{{ $teacher->nama_lengkap }}">{{ $teacher->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label for="filterSubject" class="mb-1">Filter Mata Pelajaran</label>
                    <select id="filterSubject" class="form-control form-control-sm">
                        <option value="">Semua Mata Pelajaran</option>
                        @foreach ($teacherSubjects->unique('subject_id')->pluck('subject')->sortBy('nama_mapel') as $subject)
                            <option value="{{ $subject->nama_mapel }}" data-subject-name="{{ $subject->nama_mapel }}">
                                {{ $subject->nama_mapel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label for="filterClassroom" class="mb-1">Filter Kelas</label>
                    <select id="filterClassroom" class="form-control form-control-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($teacherSubjects->unique('classroom_id')->pluck('classroom')->sortBy('nama_kelas') as $classroom)
                            <option value="{{ $classroom->nama_kelas }}"
                                data-classroom-name="{{ $classroom->nama_kelas }}">{{ $classroom->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <a href="{{ route('teacher-subjects.index') }}" class="btn btn-outline-secondary btn-sm btn-block">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tableTeacherSubjects" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkAllTeacherSubjects"></th>
                            <th width="5%">No</th>
                            <th>Guru</th>
                            <th>Mata Pelajaran</th>
                            <th>Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th>Semester</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teacherSubjects as $teacherSubject)
                            <tr data-teacher="{{ $teacherSubject->teacher->nama_lengkap ?? '' }}"
                                data-subject="{{ $teacherSubject->subject->nama_mapel ?? '' }}"
                                data-classroom="{{ $teacherSubject->classroom->nama_kelas ?? '' }}">
                                <td><input type="checkbox" class="check-teacher-subject" value="{{ $teacherSubject->id }}">
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $teacherSubject->teacher->nama_lengkap ?? '-' }}</td>
                                <td>{{ $teacherSubject->subject->nama_mapel ?? '-' }}</td>
                                <td>{{ $teacherSubject->classroom->nama_kelas ?? '-' }}</td>
                                <td>{{ $teacherSubject->academicYear->tahun_ajaran ?? '-' }}</td>
                                <td>{{ $teacherSubject->academicYear->semester ?? '-' }}</td>
                                <td>
                                    <button class="btn btn-warning btn-xs btn-edit" data-id="{{ $teacherSubject->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="btn btn-danger btn-xs btn-delete" data-id="{{ $teacherSubject->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.teacher_subjects.modal-create')
    @include('admin.teacher_subjects.modal-edit')
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
@endsection

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function() {
            // Initialize Select2 for modal selects
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: '- Pilih -',
                allowClear: true,
                width: '100%'
            });

            // Reinitialize Select2 when modals are shown
            $('#modalEdit').on('shown.bs.modal', function() {
                $('#edit_teacher_id, #edit_subject_id, #edit_classroom_id, #edit_academic_year_id')
                    .select2({
                        theme: 'bootstrap-5',
                        placeholder: '- Pilih -',
                        dropdownParent: $('#modalEdit'),
                        width: '100%'
                    });
            });

            $('#btnImportTeacherSubjects').on('click', function() {
                $('#fileImportTeacherSubjects').trigger('click');
            });

            $('#fileImportTeacherSubjects').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportTeacherSubjects').submit();
                }
            });
        });
    </script>

    @include('admin.teacher_subjects._script')
@stop
