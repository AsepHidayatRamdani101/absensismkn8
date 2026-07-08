@extends('adminlte::page')

@section('title', 'Data Hari Libur')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Master Data Hari Libur</h1>

        <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
            <i class="fas fa-plus"></i>
            Tambah Hari Libur
        </button>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table id="tableHolidays" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="20%">Tanggal</th>
                        <th>Keterangan</th>
                        <th width="15%">Kategori</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($holidays as $holiday)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ \Carbon\Carbon::parse($holiday->tanggal)->format('d-m-Y') }}</td>
                            <td>{{ $holiday->keterangan }}</td>
                            <td>
                                @if ($holiday->is_national)
                                    <span class="badge badge-success">Nasional</span>
                                @else
                                    <span class="badge badge-secondary">Sekolah</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $holiday->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $holiday->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.holidays.modal-create')
    @include('admin.holidays.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @include('admin.holidays._script')
@stop
