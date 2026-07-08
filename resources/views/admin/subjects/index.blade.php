@extends('adminlte::page')

@section('title', 'Data Mata Pelajaran')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Master Data Mata Pelajaran</h1>

        <div>
            <a href="{{ route('subjects.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('subjects.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportSubjects" action="{{ route('subjects.import') }}" method="POST" enctype="multipart/form-data"
                class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportSubjects" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportSubjects" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>

            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Mata Pelajaran
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

            <table id="tableSubjects" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Kategori</th>
                        <th>Jam/Minggu</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subjects as $subject)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $subject->kode_mapel }}</td>
                            <td>{{ $subject->nama_mapel }}</td>
                            <td>{{ $subject->kategori }}</td>
                            <td>{{ $subject->jam_per_minggu }}</td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $subject->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $subject->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.subjects.modal-create')
    @include('admin.subjects.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            $('#btnImportSubjects').on('click', function() {
                $('#fileImportSubjects').trigger('click');
            });

            $('#fileImportSubjects').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportSubjects').submit();
                }
            });
        });
    </script>

    @include('admin.subjects._script')
@stop
