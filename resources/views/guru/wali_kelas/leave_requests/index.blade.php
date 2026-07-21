@extends('adminlte::page')

@section('title', 'Pengajuan Izin/Sakit Siswa')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Pengajuan Izin/Sakit Siswa</h1>
            <p class="text-muted mb-0">Verifikasi online dan input hardfile surat siswa oleh wali kelas.</p>
        </div>
        <span class="badge badge-{{ $pendingCount > 0 ? 'danger' : 'secondary' }} px-3 py-2 mt-2 mt-md-0">
            Menunggu Verifikasi: {{ $pendingCount }}
        </span>
    </div>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($pendingCount > 0)
        <div class="alert alert-warning">
            Ada <strong>{{ $pendingCount }}</strong> pengajuan siswa yang masih menunggu verifikasi wali kelas.
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <strong>Input Hardfile Surat (Jika Siswa Terkendala Upload)</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('guru.wali-kelas.leave-requests.hardfile') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Siswa</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">- Pilih Siswa -</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-2">
                        <label>Jenis</label>
                        <select name="jenis_pengajuan" class="form-control" required>
                            <option value="">- Pilih -</option>
                            <option value="Izin">Izin</option>
                            <option value="Sakit">Sakit</option>
                        </select>
                    </div>

                    <div class="form-group col-md-2">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" required>
                    </div>

                    <div class="form-group col-md-2">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control" required>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Catatan Wali</label>
                        <input type="text" name="catatan_wali" class="form-control"
                            placeholder="Contoh: Hardfile surat diterima.">
                    </div>
                </div>

                <div class="form-group">
                    <label>Alasan</label>
                    <textarea name="alasan" rows="2" class="form-control" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Hardfile + Auto Absen
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Daftar Pengajuan Siswa</strong>
        </div>
        <div class="card-body">
            @php
                $approvedCount = (int) $requests->where('status_pengajuan', 'Disetujui')->count();
                $rejectedCount = (int) $requests->where('status_pengajuan', 'Ditolak')->count();
            @endphp

            <div class="row mb-3">
                <div class="col-md-4 mb-2 mb-md-0">
                    <div class="small-box bg-warning mb-0">
                        <div class="inner">
                            <h3>{{ $pendingCount }}</h3>
                            <p>Menunggu</p>
                        </div>
                        <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <div class="small-box bg-success mb-0">
                        <div class="inner">
                            <h3>{{ $approvedCount }}</h3>
                            <p>Disetujui</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-danger mb-0">
                        <div class="inner">
                            <h3>{{ $rejectedCount }}</h3>
                            <p>Ditolak</p>
                        </div>
                        <div class="icon"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
            </div>

            <div class="form-row mb-3">
                <div class="form-group col-md-4 mb-0">
                    <label for="filterStatusPengajuan" class="mb-1">Filter Status</label>
                    <select id="filterStatusPengajuan" class="form-control form-control-sm">
                        <option value="">Semua Status</option>
                        <option value="Menunggu">Menunggu</option>
                        <option value="Disetujui">Disetujui</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
                <div class="form-group col-md-4 mb-0 mt-2 mt-md-0">
                    <label for="filterNamaSiswaPengajuan" class="mb-1">Filter Nama Siswa</label>
                    <input type="text" id="filterNamaSiswaPengajuan" class="form-control form-control-sm"
                        placeholder="Cari nama siswa...">
                </div>
                <div class="form-group col-md-2 mb-0 mt-2 mt-md-0">
                    <label for="filterTanggalMulaiPengajuan" class="mb-1">Dari Tanggal</label>
                    <input type="date" id="filterTanggalMulaiPengajuan" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2 mb-0 mt-2 mt-md-0">
                    <label for="filterTanggalSelesaiPengajuan" class="mb-1">Sampai Tanggal</label>
                    <input type="date" id="filterTanggalSelesaiPengajuan" class="form-control form-control-sm">
                </div>
            </div>

            <div class="table-responsive">
                <table id="tableWaliLeaveRequests" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Alasan</th>
                            <th>Surat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            <tr data-student-name="{{ strtolower($item->student->nama_lengkap ?? '-') }}"
                                data-start-date="{{ optional($item->tanggal_mulai)->format('Y-m-d') }}"
                                data-end-date="{{ optional($item->tanggal_selesai)->format('Y-m-d') }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                                <td>{{ $item->student->classroom->nama_kelas ?? '-' }}</td>
                                <td>{{ $item->jenis_pengajuan }}</td>
                                <td>
                                    {{ optional($item->tanggal_mulai)->format('d-m-Y') }}
                                    s/d
                                    {{ optional($item->tanggal_selesai)->format('d-m-Y') }}
                                </td>
                                <td>{{ $item->alasan }}</td>
                                <td>
                                    @if ($item->foto_surat_path)
                                        <a href="{{ asset('storage/' . $item->foto_surat_path) }}" target="_blank"
                                            class="btn btn-xs btn-outline-primary">
                                            Lihat Surat
                                        </a>
                                    @else
                                        <span class="badge badge-secondary">Hardfile</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->status_pengajuan === 'Disetujui')
                                        <span class="badge badge-success">Disetujui</span>
                                    @elseif ($item->status_pengajuan === 'Ditolak')
                                        <span class="badge badge-danger">Ditolak</span>
                                    @else
                                        <span class="badge badge-warning">Menunggu</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->status_pengajuan === 'Menunggu')
                                        <div class="d-flex flex-wrap" style="gap: .4rem;">
                                            <form method="POST"
                                                action="{{ route('guru.wali-kelas.leave-requests.approve', $item->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="catatan_wali"
                                                    value="Disetujui oleh wali kelas.">
                                                <button type="submit" class="btn btn-success btn-xs">Setujui</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('guru.wali-kelas.leave-requests.reject', $item->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="catatan_wali"
                                                    value="Pengajuan ditolak oleh wali kelas.">
                                                <button type="submit" class="btn btn-danger btn-xs">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <small class="text-muted">Diproses oleh
                                            {{ $item->verifier->nama_lengkap ?? '-' }}</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script>
        $(function() {
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'tableWaliLeaveRequests') {
                    return true;
                }

                let rowNode = settings.aoData[dataIndex]?.nTr;
                let selectedStatus = $('#filterStatusPengajuan').val();
                let studentQuery = ($('#filterNamaSiswaPengajuan').val() || '').toLowerCase().trim();
                let filterStartDate = $('#filterTanggalMulaiPengajuan').val();
                let filterEndDate = $('#filterTanggalSelesaiPengajuan').val();
                let rowStatus = (data[7] || '').trim();
                let rowStudentName = rowNode ? String($(rowNode).data('student-name') || '') : '';
                let rowStartDate = rowNode ? String($(rowNode).data('start-date') || '') : '';
                let rowEndDate = rowNode ? String($(rowNode).data('end-date') || '') : '';

                if (selectedStatus && rowStatus.indexOf(selectedStatus) === -1) {
                    return false;
                }

                if (studentQuery && rowStudentName.indexOf(studentQuery) === -1) {
                    return false;
                }

                if (filterStartDate && rowEndDate && rowEndDate < filterStartDate) {
                    return false;
                }

                if (filterEndDate && rowStartDate && rowStartDate > filterEndDate) {
                    return false;
                }

                return true;
            });

            let table = $('#tableWaliLeaveRequests').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });

            $('#filterStatusPengajuan').on('change', function() {
                table.draw();
            });

            $('#filterNamaSiswaPengajuan').on('input', function() {
                table.draw();
            });

            $('#filterTanggalMulaiPengajuan, #filterTanggalSelesaiPengajuan').on('change', function() {
                table.draw();
            });
        });
    </script>
@stop
