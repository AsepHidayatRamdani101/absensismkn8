@extends('adminlte::page')

@section('title', 'Kirim Laporan Wali Kelas')
@section('content_header')<h1>Kirim Laporan WhatsApp</h1>@stop
@section('content')
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card">
        <div class="card-body">
            <form method="GET" class="form-inline mb-3">
                <label class="mr-2">Bulan laporan</label>
                <input type="month" name="bulan" value="{{ $month }}" class="form-control form-control-sm mr-2">
                <button class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i>Tampilkan</button>
            </form>
            <p class="text-muted small">Setiap laporan berisi PDF rekap absen dan nilai tugas/harian siswa pada bulan yang dipilih.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>No. WhatsApp Orang Tua</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($students as $student)
                        @php($phone = trim((string) $student->no_hp_orang_tua))
                        <tr>
                            <td>{{ $loop->iteration }}</td><td>{{ $student->nis }}</td><td>{{ $student->nama_lengkap }}</td>
                            <td>{{ $phone ?: '-' }}</td>
                            <td>{!! $phone ? '<span class="badge badge-success">Siap dikirim</span>' : '<span class="badge badge-warning">Nomor belum ada</span>' !!}</td>
                            <td>
                                <form method="POST" action="{{ route('guru.wali-kelas.report-send.store', $student) }}">
                                    @csrf<input type="hidden" name="bulan" value="{{ $month }}">
                                    <button class="btn btn-success btn-sm" {{ $phone ? '' : 'disabled' }}><i class="fab fa-whatsapp mr-1"></i>Kirim PDF</button>
                                </form>
                            </td>
                        </tr>
                    @empty<tr><td colspan="6" class="text-center">Belum ada siswa.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
