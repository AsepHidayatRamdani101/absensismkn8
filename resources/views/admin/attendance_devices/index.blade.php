@extends('adminlte::page')

@section('title', 'Data Perangkat IoT')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Master Data Perangkat IoT</h1>

        <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
            <i class="fas fa-plus"></i>
            Tambah Perangkat IoT
        </button>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table id="tableAttendanceDevices" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Device</th>
                        <th>Device Code</th>
                        <th>Jenis</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendanceDevices as $device)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $device->nama_device }}</td>
                            <td>{{ $device->device_code }}</td>
                            <td>{{ $device->jenis }}</td>
                            <td>{{ $device->lokasi ?? '-' }}</td>
                            <td>
                                @if ($device->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $device->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $device->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.attendance_devices.modal-create')
    @include('admin.attendance_devices.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @include('admin.attendance_devices._script')
@stop
