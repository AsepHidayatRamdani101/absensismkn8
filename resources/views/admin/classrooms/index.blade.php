@extends('adminlte::page')

@section('title', 'Data Kelas')

@section('plugins.Datatables', true)

@section('content_header')

    <div class="d-flex justify-content-between">

        <h1>Master Data Kelas</h1>

        <div>
            <a href="{{ route('classrooms.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('classrooms.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportClassrooms" action="{{ route('classrooms.import') }}" method="POST"
                enctype="multipart/form-data" class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportClassrooms" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportClassrooms" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>

            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Kelas
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

            <table id="tableClassrooms" class="table table-bordered table-striped">

                <thead>

                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Jurusan</th>
                        <th>Rombel</th>
                        <th width="120">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($classrooms as $kelas)
                        <tr>

                            <td>{{ $loop->iteration }}</td>

                            <td>{{ $kelas->kode_kelas }}</td>

                            <td>{{ $kelas->nama_kelas }}</td>

                            <td>{{ $kelas->tingkat }}</td>

                            <td>{{ $kelas->major->singkatan }}</td>

                            <td>{{ $kelas->rombel }}</td>

                            <td>

                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $kelas->id }}">

                                    <i class="fas fa-edit"></i>

                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $kelas->id }}">

                                    <i class="fas fa-trash"></i>

                                </button>

                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    @include('admin.classrooms.modal-create')
    @include('admin.classrooms.modal-edit')

@stop

@section('footer')
    @include('components.app-footer')
@stop


@section('js')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            $('#btnImportClassrooms').on('click', function() {
                $('#fileImportClassrooms').trigger('click');
            });

            $('#fileImportClassrooms').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportClassrooms').submit();
                }
            });
        });
    </script>

    @include('admin.classrooms._script')

@stop
