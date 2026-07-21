@extends('adminlte::page')

@section('title', 'Approve Pengajuan Izin Guru')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Approve Pengajuan Izin Guru</h1>
            <p class="text-muted mb-0">Verifikasi pengajuan izin, sakit, cuti, dinas luar, dan home visit guru.</p>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableTeacherLeaveApproval" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Guru</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Alasan</th>
                            <th>Tugas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->teacher->nama_lengkap ?? '-' }}</td>
                                <td>{{ $item->jenis_pengajuan }}</td>
                                <td>{{ optional($item->tanggal_mulai)->format('d-m-Y') }} s/d
                                    {{ optional($item->tanggal_selesai)->format('d-m-Y') }}</td>
                                <td>{{ $item->alasan }}</td>
                                <td>
                                    @if ($item->lampiran_tugas_path)
                                        <a href="{{ asset('storage/' . $item->lampiran_tugas_path) }}" target="_blank"
                                            class="btn btn-xs btn-outline-primary">Lihat File</a>
                                    @endif
                                    @if ($item->deskripsi_tugas)
                                        <div class="small text-muted mt-1">
                                            {{ \Illuminate\Support\Str::limit($item->deskripsi_tugas, 90) }}</div>
                                    @endif
                                    @if (!$item->lampiran_tugas_path && !$item->deskripsi_tugas)
                                        -
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
                                        <div class="d-flex flex-wrap" style="gap:.4rem;">
                                            <form method="POST"
                                                action="{{ route('kurikulum.teacher-leave-requests.approve', $item->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-xs">Setujui</button>
                                            </form>
                                            <form method="POST"
                                                action="{{ route('kurikulum.teacher-leave-requests.reject', $item->id) }}"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-xs">Tolak</button>
                                            </form>
                                        </div>
                                    @else
                                        <small class="text-muted">Sudah diproses</small>
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
            $('#tableTeacherLeaveApproval').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        });
    </script>
@stop
