@extends('adminlte::page')

@section('title', 'Data Tahun Ajaran')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Master Data Tahun Ajaran</h1>

        <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
            <i class="fas fa-plus"></i>
            Tambah Tahun Ajaran
        </button>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table id="tableAcademicYears" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Tahun Ajaran</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($academicYears as $academicYear)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $academicYear->tahun_ajaran }}</td>
                            <td>{{ $academicYear->semester }}</td>
                            <td>
                                @if ($academicYear->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $academicYear->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $academicYear->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.academic_years.modal-create')
    @include('admin.academic_years.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @include('admin.academic_years._script')
@stop
