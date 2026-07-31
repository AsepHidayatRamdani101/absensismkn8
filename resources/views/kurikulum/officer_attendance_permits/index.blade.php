@extends('adminlte::page')

@section('title', 'Approval Izin Absen Kelas')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Approval Izin Absen Kelas</h1>
            <p class="text-muted mb-0">Persetujuan pengajuan petugas kelas (KM/Sekretaris/Bendahara).</p>
        </div>
        <span class="badge badge-warning px-3 py-2">Menunggu: {{ $pendingCount }}</span>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('kurikulum.officer-attendance-permits.index') }}" class="form-inline">
                <label class="mr-2">Filter Status</label>
                <select name="status_pengajuan" class="form-control mr-2">
                    <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Semua</option>
                    <option value="Menunggu" {{ $statusFilter === 'Menunggu' ? 'selected' : '' }}>Menunggu</option>
                    <option value="Disetujui" {{ $statusFilter === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="Ditolak" {{ $statusFilter === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm mr-2">Terapkan</button>
                <a href="{{ route('kurikulum.officer-attendance-permits.index') }}"
                    class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableOfficerPermits" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Petugas</th>
                            <th>Kelas</th>
                            <th>Guru</th>
                            <th>Mapel</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Catatan Kurikulum</th>
                            <th width="22%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $requestItem)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ optional($requestItem->request_date)->format('Y-m-d') }}</td>
                                <td>{{ $requestItem->officer->nama_lengkap ?? '-' }}</td>
                                <td>{{ $requestItem->classroom->nama_kelas ?? '-' }}</td>
                                <td>{{ $requestItem->teacher->nama_lengkap ?? '-' }}</td>
                                <td>{{ $requestItem->schedule->teacherSubject->subject->nama_mapel ?? '-' }}</td>
                                <td>{{ $requestItem->alasan }}</td>
                                <td>
                                    <span
                                        class="badge badge-{{ $requestItem->status_pengajuan === 'Disetujui' ? 'success' : ($requestItem->status_pengajuan === 'Ditolak' ? 'danger' : 'warning') }}">
                                        {{ $requestItem->status_pengajuan }}
                                    </span>
                                </td>
                                <td>{{ $requestItem->catatan_kurikulum ?: '-' }}</td>
                                <td>
                                    @if ($requestItem->status_pengajuan === 'Menunggu')
                                        <form method="POST"
                                            action="{{ route('kurikulum.officer-attendance-permits.approve', $requestItem->id) }}"
                                            class="mb-2">
                                            @csrf
                                            <input type="text" name="catatan_kurikulum"
                                                class="form-control form-control-sm mb-1"
                                                placeholder="Catatan persetujuan (opsional)">
                                            <button type="submit" class="btn btn-success btn-xs">Setujui</button>
                                        </form>
                                        <form method="POST"
                                            action="{{ route('kurikulum.officer-attendance-permits.reject', $requestItem->id) }}">
                                            @csrf
                                            <input type="text" name="catatan_kurikulum"
                                                class="form-control form-control-sm mb-1"
                                                placeholder="Alasan penolakan (opsional)">
                                            <button type="submit" class="btn btn-danger btn-xs">Tolak</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Sudah diproses</span>
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
            $('#tableOfficerPermits').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        });
    </script>
@stop
