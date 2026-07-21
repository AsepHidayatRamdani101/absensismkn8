@extends('adminlte::page')

@section('title', 'Pengajuan Izin/Sakit')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Pengajuan Izin/Sakit Siswa</h1>
            <p class="text-muted mb-0">Upload foto surat izin/sakit dari orangtua wajib untuk setiap pengajuan.</p>
        </div>
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

    <div class="alert alert-info">
        Jika Anda tidak memiliki kuota internet atau tidak memegang HP, mohon bawa hardfile surat izin/sakit ke sekolah
        dan laporkan kepada wali kelas agar pengajuan tetap dapat diproses.
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <strong>Form Pengajuan</strong>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <strong>Nama Siswa:</strong> {{ $student->nama_lengkap }}<br>
                <strong>Kelas:</strong> {{ $student->classroom->nama_kelas ?? '-' }}
            </div>

            <form method="POST" action="{{ route('siswa.leave-requests.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Jenis Pengajuan</label>
                        <select name="jenis_pengajuan" class="form-control" required>
                            <option value="">- Pilih -</option>
                            <option value="Izin" @selected(old('jenis_pengajuan') === 'Izin')>Izin</option>
                            <option value="Sakit" @selected(old('jenis_pengajuan') === 'Sakit')>Sakit</option>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}"
                            required>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control"
                            value="{{ old('tanggal_selesai') }}" required>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Foto Surat Orangtua</label>
                        <input type="file" name="foto_surat" class="form-control-file" accept="image/*" required>
                        <small class="text-muted d-block mt-1">Wajib diupload untuk semua pengajuan izin/sakit.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Alasan</label>
                    <textarea name="alasan" rows="3" class="form-control" required>{{ old('alasan') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Riwayat Pengajuan</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableStudentLeaveRequests" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Jenis</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Alasan</th>
                            <th>Surat</th>
                            <th>Status</th>
                            <th>Catatan Wali</th>
                            <th>Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->jenis_pengajuan }}</td>
                                <td>{{ optional($item->tanggal_mulai)->format('d-m-Y') }}</td>
                                <td>{{ optional($item->tanggal_selesai)->format('d-m-Y') }}</td>
                                <td>{{ $item->alasan }}</td>
                                <td>
                                    @if ($item->foto_surat_path)
                                        <a href="{{ asset('storage/' . $item->foto_surat_path) }}" target="_blank"
                                            class="btn btn-xs btn-outline-primary">
                                            Lihat Surat
                                        </a>
                                    @else
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
                                <td>{{ $item->catatan_wali ?? '-' }}</td>
                                <td>
                                    @if ($item->verified_at)
                                        {{ $item->verified_at->format('d-m-Y H:i') }}
                                    @else
                                        -
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
            $('#tableStudentLeaveRequests').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        });
    </script>
@stop
