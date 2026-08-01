@extends('adminlte::page')

@section('title', 'Absensi Siswa Oleh Guru')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Absensi Siswa Oleh Guru</h1>

        <div>
            <button class="btn btn-primary mr-1" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Absensi Siswa
            </button>

            <button id="btnDeleteMultipleAttendanceDetails" class="btn btn-danger d-none">
                <i class="fas fa-trash"></i>
                Hapus Terpilih (<span id="selectedCountAttendanceDetails">0</span>)
            </button>
        </div>
    </div>
@stop

@section('content')
    @if (!$isWeekendHoliday)
        <div class="row mb-3">
            <div class="col-md-6">
                <a href="{{ route('attendance-details.teacher-attendance-detail', ['filter' => 'sudah']) }}"
                    class="text-decoration-none">
                    <div class="info-box bg-success" style="cursor:pointer;">
                        <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Guru Sudah Absen Hari Ini</span>
                            <span class="info-box-number">{{ count($teachersWithAttendance) }}</span>
                            <span class="progress-description"><i class="fas fa-eye"></i> Lihat Detail</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('attendance-details.teacher-attendance-detail', ['filter' => 'belum']) }}"
                    class="text-decoration-none">
                    <div class="info-box bg-danger" style="cursor:pointer;">
                        <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Guru Belum Absen Hari Ini</span>
                            <span class="info-box-number">{{ count($teachersWithoutAttendance) }}</span>
                            <span class="progress-description"><i class="fas fa-eye"></i> Lihat Detail</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('attendance-details.index') }}" class="mb-3"
                id="attendanceDetailsFilterForm">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Tahun Ajaran</label>
                        <select name="tahun_ajaran" class="form-control form-control-sm">
                            <option value="">Semua Tahun Ajaran</option>
                            @foreach ($filterTahunAjarans as $tahunAjaran)
                                <option value="{{ $tahunAjaran }}"
                                    {{ ($filters['tahun_ajaran'] ?? '') === $tahunAjaran ? 'selected' : '' }}>
                                    {{ $tahunAjaran }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm"
                            value="{{ $filters['tanggal'] ?? '' }}">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Guru</label>
                        <select name="guru" class="form-control form-control-sm">
                            <option value="">Semua Guru</option>
                            @foreach ($filterGurus as $guru)
                                <option value="{{ $guru }}"
                                    {{ ($filters['guru'] ?? '') === $guru ? 'selected' : '' }}>
                                    {{ $guru }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Mapel</label>
                        <select name="mapel" class="form-control form-control-sm">
                            <option value="">Semua Mapel</option>
                            @foreach ($filterMapels as $mapel)
                                <option value="{{ $mapel }}"
                                    {{ ($filters['mapel'] ?? '') === $mapel ? 'selected' : '' }}>
                                    {{ $mapel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Kelas</label>
                        <select name="kelas" class="form-control form-control-sm">
                            <option value="">Semua Kelas</option>
                            @foreach ($filterKelas as $kelas)
                                <option value="{{ $kelas }}"
                                    {{ ($filters['kelas'] ?? '') === $kelas ? 'selected' : '' }}>
                                    {{ $kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">Semua Status</option>
                            @foreach ($filterStatuses as $status)
                                <option value="{{ $status }}"
                                    {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-1">
                        <button type="submit" class="btn btn-primary btn-sm mr-1">
                            <i class="fas fa-filter"></i> Terapkan
                        </button>
                        <a href="{{ route('attendance-details.index') }}" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            @if (!$hasFilter)
                <div class="alert alert-info mb-0">
                    <i class="fas fa-filter mr-1"></i>
                    Terapkan filter terlebih dahulu untuk menampilkan data absensi siswa oleh guru.
                </div>
            @else
                <div class="table-responsive">
                    <table id="tableAttendanceDetails" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="3%"><input type="checkbox" id="checkAllAttendanceDetails"></th>
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
                        <tbody></tbody>
                    </table>
                </div>
            @endif
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
