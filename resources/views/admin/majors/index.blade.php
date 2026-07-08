@extends('adminlte::page')

@section('title', 'Data Jurusan')

@section('plugins.Datatables', true)



@section('content_header')

    <div class="d-flex justify-content-between">

        <h1>Master Data Jurusan</h1>

        <div>
            <a href="{{ route('majors.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('majors.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportMajors" action="{{ route('majors.import') }}" method="POST" enctype="multipart/form-data"
                class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportMajors" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportMajors" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Jurusan
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

            <table id="tableMajors" class="table table-bordered table-striped">

                <thead>

                    <tr>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama Jurusan</th>
                        <th>Singkatan</th>
                        <th width="15%">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($majors as $major)
                        <tr>

                            <td>{{ $loop->iteration }}</td>

                            <td>{{ $major->kode_jurusan }}</td>

                            <td>{{ $major->nama_jurusan }}</td>

                            <td>{{ $major->singkatan }}</td>

                            <td>

                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $major->id }}">

                                    <i class="fas fa-edit"></i>

                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $major->id }}">

                                    <i class="fas fa-trash"></i>

                                </button>

                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    @include('admin.majors.modal-create')
    @include('admin.majors.modal-edit')

@stop

@section('footer')
    @include('components.app-footer')
@stop



@section('js')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            $('#btnImportMajors').on('click', function() {
                $('#fileImportMajors').trigger('click');
            });

            $('#fileImportMajors').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportMajors').submit();
                }
            });
        });
    </script>

    @include('admin.majors._script')

@stop
