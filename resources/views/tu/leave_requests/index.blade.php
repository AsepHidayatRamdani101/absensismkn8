@extends('adminlte::page')
@section('title', 'Perizinan Siswa - TU')
@section('content_header')<h1>Kontrol Perizinan Siswa</h1>@stop
@section('content')
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body">
        <form class="form-inline mb-3" method="GET">
            <label class="mr-2">Status</label><select name="status" class="form-control form-control-sm mr-2"><option value="">Semua</option>@foreach(['Menunggu','Disetujui','Ditolak'] as $item)<option @selected($status === $item)>{{ $item }}</option>@endforeach</select>
            <button class="btn btn-primary btn-sm mr-2">Tampilkan</button><a class="btn btn-outline-dark btn-sm" href="{{ route('tu.leave-requests.print') }}"><i class="fas fa-print mr-1"></i>Cetak yang Disetujui</a>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped table-sm"><thead><tr><th>No</th><th>Siswa</th><th>Kelas</th><th>Jenis</th><th>Periode</th><th>Orang Tua</th><th>No. HP Orang Tua</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($requests as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item->student->nama_lengkap }}</td><td>{{ $item->student->classroom->nama_kelas ?? '-' }}</td><td>{{ $item->jenis_pengajuan }}</td><td>{{ $item->tanggal_mulai->format('d/m/Y') }} - {{ $item->tanggal_selesai->format('d/m/Y') }}</td><td>{{ $item->student->nama_orang_tua_wali ?: '-' }}</td><td>{{ $item->student->no_hp_orang_tua ?: '-' }}</td><td><span class="badge badge-{{ $item->status_pengajuan === 'Disetujui' ? 'success' : ($item->status_pengajuan === 'Ditolak' ? 'danger' : 'warning') }}">{{ $item->status_pengajuan }}</span></td><td class="text-nowrap">@if($item->status_pengajuan === 'Menunggu')<form class="d-inline" method="POST" action="{{ route('tu.leave-requests.approve', $item) }}">@csrf<button class="btn btn-success btn-xs">Setujui</button></form> <form class="d-inline" method="POST" action="{{ route('tu.leave-requests.reject', $item) }}">@csrf<button class="btn btn-danger btn-xs">Tolak</button></form>@elseif($item->status_pengajuan === 'Disetujui')<a target="_blank" href="{{ route('tu.leave-requests.print', ['ids[]' => $item->id]) }}" class="btn btn-outline-secondary btn-xs"><i class="fas fa-print"></i></a>@endif</td></tr>@empty<tr><td colspan="9" class="text-center">Belum ada data perizinan.</td></tr>@endforelse
        </tbody></table></div>
    </div></div>
@stop
