@extends('adminlte::page')

@section('title', 'Data Jadwal')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Master Data Jadwal</h1>

        <div>
            <a href="{{ route('schedules.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('schedules.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportSchedules" action="{{ route('schedules.import') }}" method="POST"
                enctype="multipart/form-data" class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportSchedules" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportSchedules" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>

            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Jadwal
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

            <div class="row mb-3">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label for="filterTingkat" class="mb-1">Filter Tingkat</label>
                    <select id="filterTingkat" class="form-control form-control-sm">
                        <option value="">Semua Tingkat</option>
                        @foreach ($filterTingkats as $tingkat)
                            <option value="{{ $tingkat }}">{{ $tingkat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5 mb-2 mb-md-0">
                    <label for="filterJurusan" class="mb-1">Filter Jurusan</label>
                    <select id="filterJurusan" class="form-control form-control-sm">
                        <option value="">Semua Jurusan</option>
                        @foreach ($filterJurusans as $jurusan)
                            <option value="{{ $jurusan }}">{{ $jurusan }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="filterKelas" class="mb-1">Filter Kelas</label>
                    <select id="filterKelas" class="form-control form-control-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($filterKelas as $kelas)
                            <option value="{{ $kelas['nama_kelas'] }}" data-tingkat="{{ $kelas['tingkat'] }}"
                                data-jurusan="{{ $kelas['jurusan'] }}">{{ $kelas['nama_kelas'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <table id="tableSchedules" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Guru</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Ruangan</th>
                        <th>Tahun Ajaran</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($schedules as $schedule)
                        <tr data-tingkat="{{ $schedule->teacherSubject->classroom->tingkat ?? '' }}"
                            data-jurusan="{{ $schedule->teacherSubject->classroom->major->nama_jurusan ?? '' }}"
                            data-kelas="{{ $schedule->teacherSubject->classroom->nama_kelas ?? '' }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $schedule->teacherSubject->teacher->nama_lengkap ?? '-' }}</td>
                            <td>{{ $schedule->teacherSubject->subject->nama_mapel ?? '-' }}</td>
                            <td>{{ $schedule->teacherSubject->classroom->nama_kelas ?? '-' }}</td>
                            <td>{{ $schedule->hari }}</td>
                            <td>{{ $schedule->jam_mulai }} - {{ $schedule->jam_selesai }}</td>
                            <td>{{ $schedule->ruangan ?? '-' }}</td>
                            <td>
                                {{ $schedule->teacherSubject->academicYear->tahun_ajaran ?? '-' }}
                                ({{ $schedule->teacherSubject->academicYear->semester ?? '-' }})
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $schedule->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $schedule->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.schedules.modal-create')
    @include('admin.schedules.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            $('#btnImportSchedules').on('click', function() {
                $('#fileImportSchedules').trigger('click');
            });

            $('#fileImportSchedules').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportSchedules').submit();
                }
            });
        });
    </script>

    @include('admin.schedules._script')
@stop
