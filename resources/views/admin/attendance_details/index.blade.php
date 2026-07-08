@extends('adminlte::page')

@section('title', 'Absensi Siswa Oleh Guru')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Absensi Siswa Oleh Guru</h1>

        <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
            <i class="fas fa-plus"></i>
            Tambah Absensi Siswa
        </button>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4 mb-2">
                    <label for="filterTahunAjaran" class="mb-1">Tahun Ajaran</label>
                    <select id="filterTahunAjaran" class="form-control form-control-sm">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach ($filterTahunAjarans as $tahunAjaran)
                            <option value="{{ $tahunAjaran }}">{{ $tahunAjaran }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-2">
                    <label for="filterTanggal" class="mb-1">Tanggal</label>
                    <select id="filterTanggal" class="form-control form-control-sm">
                        <option value="">Semua Tanggal</option>
                        @foreach ($filterTangggals as $tanggal)
                            <option value="{{ $tanggal }}">{{ $tanggal }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-2">
                    <label for="filterGuru" class="mb-1">Guru</label>
                    <select id="filterGuru" class="form-control form-control-sm">
                        <option value="">Semua Guru</option>
                        @foreach ($filterGurus as $guru)
                            <option value="{{ $guru }}">{{ $guru }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-2 mb-md-0">
                    <label for="filterMapel" class="mb-1">Mapel</label>
                    <select id="filterMapel" class="form-control form-control-sm">
                        <option value="">Semua Mapel</option>
                        @foreach ($filterMapels as $mapel)
                            <option value="{{ $mapel }}">{{ $mapel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-2 mb-md-0">
                    <label for="filterKelas" class="mb-1">Kelas</label>
                    <select id="filterKelas" class="form-control form-control-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($filterKelas as $kelas)
                            <option value="{{ $kelas }}">{{ $kelas }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="filterStatus" class="mb-1">Status</label>
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value="">Semua Status</option>
                        @foreach ($filterStatuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <table id="tableAttendanceDetails" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Tanggal</th>
                        <th>Guru</th>
                        <th>Mapel</th>
                        <th>Kelas</th>
                        <th>Siswa</th>
                        <th>Status</th>
                        <th>Jam Absen</th>
                        <th>Keterangan</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendanceDetails as $item)
                        <tr data-tahun-ajaran="{{ $item->teacherAttendance->academicYear->tahun_ajaran ?? '' }}"
                            data-tanggal="{{ $item->teacherAttendance->tanggal ?? '' }}"
                            data-guru="{{ $item->teacherAttendance->teacher->nama_lengkap ?? '' }}"
                            data-mapel="{{ $item->teacherAttendance->subject->nama_mapel ?? '' }}"
                            data-kelas="{{ $item->teacherAttendance->classroom->nama_kelas ?? '' }}"
                            data-status="{{ $item->status }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->teacherAttendance->tanggal ?? '-' }}</td>
                            <td>{{ $item->teacherAttendance->teacher->nama_lengkap ?? '-' }}</td>
                            <td>{{ $item->teacherAttendance->subject->nama_mapel ?? '-' }}</td>
                            <td>{{ $item->student->classroom->nama_kelas ?? '-' }}</td>
                            <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                            <td>{{ $item->status }}</td>
                            <td>{{ $item->jam_absen ?? '-' }}</td>
                            <td>{{ $item->keterangan ?? '-' }}</td>
                            <td>
                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $item->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.attendance_details.modal-create')
    @include('admin.attendance_details.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @include('admin.attendance_details._script')
@stop
