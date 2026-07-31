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
                <select name="import_mode" id="importModeTeacherSubjects"
                    class="form-control form-control-sm d-inline-block mr-1" style="width: 210px; vertical-align: middle;">
                    <option value="auto_create" selected>Mode: Auto-create master</option>
                    <option value="strict_existing">Mode: Hanya data existing</option>
                </select>
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

            <div class="mt-2 text-right">
                @if (!empty($activeAcademicYear))
                    <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-check-circle mr-1"></i>
                        Aktif: {{ $activeAcademicYear->tahun_ajaran }} - {{ $activeAcademicYear->semester }}
                    </span>
                @else
                    <span class="badge badge-danger px-3 py-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Tahun ajaran aktif belum disetel
                    </span>
                @endif
            </div>
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

            <div class="alert alert-info">
                Import dari format jadwal guru pengampu didukung dan otomatis menggunakan tahun ajaran aktif.
                Pilih mode import:
                <strong>Auto-create master</strong> untuk membuat data guru/mapel/kelas yang belum ada,
                atau <strong>Hanya data existing</strong> untuk memproses data yang sudah tersedia di database saja.
                @if (!empty($activeAcademicYear))
                    Tahun ajaran aktif saat ini:
                    <strong>{{ $activeAcademicYear->tahun_ajaran }} - {{ $activeAcademicYear->semester }}</strong>.
                @else
                    Saat ini belum ada tahun ajaran aktif, sehingga sistem akan memakai data tahun ajaran terbaru.
                @endif
            </div>

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
                        @foreach ($subjects as $subject)
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
                    <a href="{{ route('teacher-subjects.index') }}" id="btnResetTeacherSubjectsFilter"
                        class="btn btn-outline-secondary btn-sm btn-block">
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

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            const importModeKey = 'teacher-subjects-import-mode';

            // Restore last selected import mode from browser storage.
            try {
                const savedMode = localStorage.getItem(importModeKey);
                if (savedMode && $('#importModeTeacherSubjects option[value="' + savedMode + '"]').length > 0) {
                    $('#importModeTeacherSubjects').val(savedMode);
                }
            } catch (error) {
                // Ignore storage access issues (private mode or blocked storage).
            }

            $('#importModeTeacherSubjects').on('change', function() {
                try {
                    localStorage.setItem(importModeKey, $(this).val());
                } catch (error) {
                    // Ignore storage access issues.
                }
            });

            // Pengisian data modal edit ditangani di file script teacher_subjects.

            $('#btnImportTeacherSubjects').on('click', function() {
                $('#fileImportTeacherSubjects').trigger('click');
            });

            $('#fileImportTeacherSubjects').on('change', function() {
                if (this.files.length > 0) {
                    try {
                        localStorage.setItem(importModeKey, $('#importModeTeacherSubjects').val());
                    } catch (error) {
                        // Ignore storage access issues.
                    }

                    $('#formImportTeacherSubjects').submit();
                }
            });
        });
    </script>

    @include('admin.teacher_subjects._script')
@stop
