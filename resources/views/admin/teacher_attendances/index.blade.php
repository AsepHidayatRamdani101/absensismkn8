@extends('adminlte::page')

@section('title', 'Absensi Oleh Guru')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Absensi Oleh Guru</h1>

        @if (!$isReadOnly)
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Absensi Guru
            </button>
        @endif
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
                    <label for="filterPertemuan" class="mb-1">Pertemuan Ke</label>
                    <select id="filterPertemuan" class="form-control form-control-sm">
                        <option value="">Semua Pertemuan</option>
                        @foreach ($filterPertemuans as $pertemuan)
                            <option value="{{ $pertemuan }}">{{ $pertemuan }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="filterKelas" class="mb-1">Kelas</label>
                    <select id="filterKelas" class="form-control form-control-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($filterKelas as $kelas)
                            <option value="{{ $kelas }}">{{ $kelas }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <table id="tableTeacherAttendances" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Guru</th>
                        <th>Tanggal</th>
                        <th>Pertemuan</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Tahun Ajaran</th>
                        <th>Status</th>
                        @if (!$isReadOnly)
                            <th width="15%">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($teacherAttendances as $item)
                        <tr data-tahun-ajaran="{{ $item->academicYear->tahun_ajaran ?? '' }}"
                            data-tanggal="{{ $item->tanggal }}" data-guru="{{ $item->teacher->nama_lengkap ?? '' }}"
                            data-mapel="{{ $item->subject->nama_mapel ?? '' }}"
                            data-pertemuan="{{ $item->pertemuan ?? '' }}"
                            data-kelas="{{ $item->classroom->nama_kelas ?? '' }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->teacher->nama_lengkap ?? '-' }}</td>
                            <td>{{ $item->tanggal }}</td>
                            <td>{{ $item->pertemuan }}</td>
                            <td>{{ $item->classroom->nama_kelas ?? '-' }}</td>
                            <td>{{ $item->subject->nama_mapel ?? '-' }}</td>
                            <td>{{ $item->academicYear->tahun_ajaran ?? '-' }}
                                ({{ $item->academicYear->semester ?? '-' }})
                            </td>
                            <td>{{ $item->status }}</td>
                            @if (!$isReadOnly)
                                <td>
                                    <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $item->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if (!$isReadOnly)
        @include('admin.teacher_attendances.modal-create')
        @include('admin.teacher_attendances.modal-edit')
    @endif
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    @if (!$isReadOnly)
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endif

    @include('admin.teacher_attendances._script')
@stop
