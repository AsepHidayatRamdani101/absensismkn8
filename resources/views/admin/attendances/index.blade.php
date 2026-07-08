@extends('adminlte::page')

@section('title', 'Data Absensi')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Master Data Absensi</h1>

        <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
            <i class="fas fa-plus"></i>
            Tambah Absensi
        </button>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table id="tableAttendances" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Tanggal</th>
                        <th>Jadwal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th>Device</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendances as $attendance)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $attendance->student->nama_lengkap ?? '-' }}</td>
                            <td>{{ $attendance->student->classroom->nama_kelas ?? '-' }}</td>
                            <td>{{ $attendance->tanggal }}</td>
                            <td>{{ $attendance->schedule->teacherSubject->subject->nama_mapel ?? '-' }}</td>
                            <td>{{ $attendance->jam_masuk ?? '-' }}</td>
                            <td>{{ $attendance->jam_keluar ?? '-' }}</td>
                            <td>{{ $attendance->status }}</td>
                            <td>{{ $attendance->metode }}</td>
                            <td>{{ $attendance->attendanceDevice->nama_device ?? '-' }}</td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $attendance->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $attendance->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.attendances.modal-create')
    @include('admin.attendances.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @include('admin.attendances._script')
@stop
