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

            <table id="tableTeacherSubjects" class="table table-bordered table-striped">
                <thead>
                    <tr>
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
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $teacherSubject->teacher->nama_lengkap ?? '-' }}</td>
                            <td>{{ $teacherSubject->subject->nama_mapel ?? '-' }}</td>
                            <td>{{ $teacherSubject->classroom->nama_kelas ?? '-' }}</td>
                            <td>{{ $teacherSubject->academicYear->tahun_ajaran ?? '-' }}</td>
                            <td>{{ $teacherSubject->academicYear->semester ?? '-' }}</td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $teacherSubject->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $teacherSubject->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.teacher_subjects.modal-create')
    @include('admin.teacher_subjects.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
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
