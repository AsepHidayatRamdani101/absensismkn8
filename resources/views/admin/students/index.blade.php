@extends('adminlte::page')

@section('title', 'Data Siswa')

@section('plugins.Datatables', true)

@section('content_header')

    <div class="d-flex justify-content-between">

        <h1>Master Data Siswa</h1>

        <div>
            <a href="{{ route('students.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('students.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportStudents" action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data"
                class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportStudents" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportStudents" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>

            <form id="formGenerateAccountsStudents" action="{{ route('students.generate-accounts') }}" method="POST"
                class="d-inline-block mr-1">
                @csrf
                <button type="button" id="btnGenerateAccountsStudents" class="btn btn-info">
                    <i class="fas fa-user-cog"></i>
                    Generate Akun Siswa
                </button>
            </form>

            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Siswa
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

            <table id="tableStudents" class="table table-bordered table-striped">

                <thead>

                    <tr>
                        <th width="5%">No</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>JK</th>
                        <th>Kelas</th>
                        <th>No HP</th>
                        <th width="15%">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($students as $student)
                        <tr>

                            <td>{{ $loop->iteration }}</td>

                            <td>{{ $student->nis }}</td>

                            <td>{{ $student->nama_lengkap }}</td>

                            <td>{{ $student->jenis_kelamin }}</td>

                            <td>

                                {{ $student->classroom->nama_kelas }}

                            </td>

                            <td>{{ $student->no_hp }}</td>

                            <td>

                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $student->id }}">

                                    <i class="fas fa-edit"></i>

                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $student->id }}">

                                    <i class="fas fa-trash"></i>

                                </button>

                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    @include('admin.students.modal-create')
    @include('admin.students.modal-edit')

@stop


@section('footer')
    @include('components.app-footer')
@stop


@section('js')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            $('#btnImportStudents').on('click', function() {
                $('#fileImportStudents').trigger('click');
            });

            $('#fileImportStudents').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportStudents').submit();
                }
            });

            $('#btnGenerateAccountsStudents').on('click', function() {
                Swal.fire({
                    title: 'Generate akun siswa?',
                    text: 'Username dari NISN dan password default siswa12345.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, generate',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#formGenerateAccountsStudents').submit();
                    }
                });
            });
        });
    </script>
    @include('admin.students._script')

@stop
