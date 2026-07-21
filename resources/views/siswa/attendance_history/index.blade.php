@extends('adminlte::page')

@section('title', 'Riwayat Absen Siswa')
@section('plugins.Datatables', true)

@section('css')
    <style>
        .history-filter-card,
        .history-table-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
        }

        .student-meta {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
            line-height: 1.5;
        }

        .history-filter-card .form-control {
            border-radius: 10px;
        }

        .history-action-group {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: .9rem .85rem;
            height: 100%;
            background: #fff;
        }

        .summary-card .summary-title {
            font-size: .8rem;
            color: #6b7280;
            margin-bottom: .2rem;
        }

        .summary-card .summary-value {
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1.1;
            margin: 0;
        }

        .summary-total {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .summary-hadir {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        }

        .summary-sakit {
            background: linear-gradient(135deg, #fefce8 0%, #fef08a 100%);
        }

        .summary-izin {
            background: linear-gradient(135deg, #eff6ff 0%, #bfdbfe 100%);
        }

        .summary-alpa {
            background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
        }

        .summary-terlambat {
            background: linear-gradient(135deg, #eef2ff 0%, #c7d2fe 100%);
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Riwayat Absen Siswa</h1>
            <p class="text-muted mb-0">Riwayat absensi pribadi berdasarkan data absensi kelas.</p>
        </div>
    </div>
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3 history-filter-card">
        <div class="card-body">
            <div class="mb-3 student-meta">
                <strong>Nama Siswa:</strong> {{ $student->nama_lengkap }}<br>
                <strong>Kelas:</strong> {{ $student->classroom->nama_kelas ?? '-' }}
                ({{ $student->classroom->kode_kelas ?? '-' }})
            </div>

            <form method="GET" action="{{ route('siswa.attendance-history.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="tanggal_dari" class="mb-1">Tanggal Dari</label>
                        <input type="date" class="form-control" id="tanggal_dari" name="tanggal_dari"
                            value="{{ $tanggalDari }}">
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <label for="tanggal_sampai" class="mb-1">Tanggal Sampai</label>
                        <input type="date" class="form-control" id="tanggal_sampai" name="tanggal_sampai"
                            value="{{ $tanggalSampai }}">
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <label for="status" class="mb-1">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">Semua Status</option>
                            @foreach (['Hadir', 'Sakit', 'Izin', 'Alpha', 'Terlambat'] as $statusOption)
                                <option value="{{ $statusOption }}" @selected($statusFilter === $statusOption)>
                                    {{ $statusOption === 'Alpha' ? 'Alpa' : $statusOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <label for="guru_mapel" class="mb-1">Guru-Mapel</label>
                        <select class="form-control" id="guru_mapel" name="guru_mapel">
                            <option value="">Semua Guru-Mapel</option>
                            @foreach ($guruMapelOptions as $option)
                                <option value="{{ $option['value'] }}" @selected($guruMapelFilter === $option['value'])>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3 history-action-group">
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                    <a href="{{ route('siswa.attendance-history.index') }}" class="btn btn-outline-secondary">Reset</a>
                    <a href="{{ route('siswa.attendance-history.pdf', request()->query()) }}" class="btn btn-danger"
                        target="_blank">
                        <i class="fas fa-file-pdf"></i>
                        Export PDF
                    </a>
                    <a href="{{ route('siswa.attendance-history.excel', request()->query()) }}" class="btn btn-success">
                        <i class="fas fa-file-excel"></i>
                        Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card history-table-card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <div class="summary-card summary-total">
                        <div class="summary-title">Total</div>
                        <h4 class="summary-value">{{ $statusSummary['total'] ?? 0 }}</h4>
                        <small class="text-muted">Hadir
                            {{ number_format($statusSummary['persentase_hadir'] ?? 0, 2) }}%</small>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <div class="summary-card summary-hadir">
                        <div class="summary-title">Hadir</div>
                        <h4 class="summary-value">{{ $statusSummary['hadir'] ?? 0 }}</h4>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <div class="summary-card summary-sakit">
                        <div class="summary-title">Sakit</div>
                        <h4 class="summary-value">{{ $statusSummary['sakit'] ?? 0 }}</h4>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <div class="summary-card summary-izin">
                        <div class="summary-title">Izin</div>
                        <h4 class="summary-value">{{ $statusSummary['izin'] ?? 0 }}</h4>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <div class="summary-card summary-alpa">
                        <div class="summary-title">Alpa</div>
                        <h4 class="summary-value">{{ $statusSummary['alpa'] ?? 0 }}</h4>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <div class="summary-card summary-terlambat">
                        <div class="summary-title">Terlambat</div>
                        <h4 class="summary-value">{{ $statusSummary['terlambat'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tableRiwayatAbsenSiswa" class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Tanggal</th>
                            <th width="12%">Hari</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th width="12%">Kelas</th>
                            <th width="10%">Status</th>
                            <th width="10%">Jam Absen</th>
                            <th width="14%">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($histories as $history)
                            @php
                                $attendance = $history->teacherAttendance;
                                $tanggal = $attendance?->tanggal;
                                $status = $history->status === 'Alpha' ? 'Alpa' : $history->status;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $tanggal ? \Carbon\Carbon::parse($tanggal)->format('d-m-Y') : '-' }}</td>
                                <td>{{ $tanggal ? \Carbon\Carbon::parse($tanggal)->translatedFormat('l') : '-' }}</td>
                                <td>{{ $attendance?->subject?->nama_mapel ?? '-' }}</td>
                                <td>{{ $attendance?->teacher?->nama_lengkap ?? '-' }}</td>
                                <td>{{ $attendance?->classroom?->nama_kelas ?? '-' }}</td>
                                <td>
                                    @if ($status === 'Hadir')
                                        <span class="badge badge-success">Hadir</span>
                                    @elseif ($status === 'Sakit')
                                        <span class="badge badge-warning">Sakit</span>
                                    @elseif ($status === 'Izin')
                                        <span class="badge badge-info">Izin</span>
                                    @elseif ($status === 'Alpa')
                                        <span class="badge badge-danger">Alpa</span>
                                    @elseif ($status === 'Terlambat')
                                        <span class="badge badge-primary">Terlambat</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $status }}</span>
                                    @endif
                                </td>
                                <td>{{ $history->jam_absen ?? '-' }}</td>
                                <td>{{ $history->keterangan ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Belum ada riwayat absensi.</td>
                            </tr>
                        @endforelse
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
            $('#tableRiwayatAbsenSiswa').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        });
    </script>
@stop
