@extends('adminlte::page')

@section('title', 'Data Guru')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('content_header')

    <div class="d-flex justify-content-between">

        <h1>Master Data Guru</h1>

        <div>
            <a href="{{ route('teachers.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('teachers.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportTeachers" action="{{ route('teachers.import') }}" method="POST" enctype="multipart/form-data"
                class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportTeachers" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportTeachers" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>

            <form id="formGenerateAccountsTeachers" action="{{ route('teachers.generate-accounts') }}" method="POST"
                class="d-inline-block mr-1">
                @csrf
                <button type="button" id="btnGenerateAccountsTeachers" class="btn btn-info">
                    <i class="fas fa-user-cog"></i>
                    Generate Akun Guru
                </button>
            </form>

            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Guru
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

            <table id="tableTeachers" class="table table-bordered table-striped">

                <thead>

                    <tr>
                        <th width="5%">No</th>
                        <th>NIP</th>
                        <th>NUPTK</th>
                        <th>Nama Guru</th>
                        <th>JK</th>
                        <th>No HP</th>
                        <th width="15%">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($teachers as $teacher)
                        <tr>

                            <td>{{ $loop->iteration }}</td>

                            <td>{{ $teacher->nip }}</td>

                            <td>{{ $teacher->nuptk }}</td>

                            <td>{{ $teacher->nama_lengkap }}</td>

                            <td>{{ $teacher->jenis_kelamin }}</td>

                            <td>{{ $teacher->no_hp }}</td>

                            <td>

                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $teacher->id }}">

                                    <i class="fas fa-edit"></i>

                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $teacher->id }}">

                                    <i class="fas fa-trash"></i>

                                </button>

                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    @include('admin.teachers.modal-create')
    @include('admin.teachers.modal-edit')

@stop


@section('footer')
    @include('components.app-footer')
@stop


@section('js')
    <script>
        $(function() {
            $('#btnImportTeachers').on('click', function() {
                $('#fileImportTeachers').trigger('click');
            });

            $('#fileImportTeachers').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportTeachers').submit();
                }
            });

            $('#btnGenerateAccountsTeachers').on('click', function() {
                Swal.fire({
                    title: 'Generate akun guru?',
                    text: 'Username dari NIP dan password default guru12345.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, generate',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#formGenerateAccountsTeachers').submit();
                    }
                });
            });
        });
    </script>
    @include('admin.teachers._script')
@stop
