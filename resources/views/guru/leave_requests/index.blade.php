@extends('adminlte::page')

@section('title', 'Pengajuan Guru')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Pengajuan Izin/Sakit/Cuti/Dinas Luar/Home Visit</h1>
            <p class="text-muted mb-0">{{ $teacher->nama_lengkap }}</p>
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

    <div class="card mb-3">
        <div class="card-header">
            <strong>Form Pengajuan</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('guru.leave-requests.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Jenis Pengajuan</label>
                        <select name="jenis_pengajuan" id="jenis_pengajuan" class="form-control" required>
                            <option value="">- Pilih Jenis -</option>
                            <option value="Izin" @selected(old('jenis_pengajuan') === 'Izin')>Izin</option>
                            <option value="Sakit" @selected(old('jenis_pengajuan') === 'Sakit')>Sakit</option>
                            <option value="Cuti" @selected(old('jenis_pengajuan') === 'Cuti')>Cuti</option>
                            <option value="Dinas Luar" @selected(old('jenis_pengajuan') === 'Dinas Luar')>Dinas Luar</option>
                            <option value="Home Visit" @selected(old('jenis_pengajuan') === 'Home Visit')>Home Visit</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}"
                            required>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control"
                            value="{{ old('tanggal_selesai') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Alasan</label>
                    <textarea name="alasan" rows="3" class="form-control" required>{{ old('alasan') }}</textarea>
                </div>

                <div class="form-group mb-0">
                    <label>Lampiran Tugas</label>
                    <input type="file" name="lampiran_tugas" id="lampiran_tugas" class="form-control-file"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>

                <div class="form-group mt-3 mb-0">
                    <label>Deskripsi Tugas</label>
                    <textarea name="deskripsi_tugas" id="deskripsi_tugas" rows="3" class="form-control"
                        placeholder="Boleh diisi jika tugas tidak diupload sebagai file.">{{ old('deskripsi_tugas') }}</textarea>
                    <small id="pengajuan_tugas_hint" class="text-muted d-block mt-1"></small>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Riwayat Pengajuan</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableGuruLeaveRequests" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Jenis</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Alasan</th>
                            <th>Tugas</th>
                            <th>Status</th>
                            <th>Dikirim</th>
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
                                    @if ($item->lampiran_tugas_path)
                                        <a href="{{ asset('storage/' . $item->lampiran_tugas_path) }}" target="_blank"
                                            class="btn btn-xs btn-outline-primary">
                                            Lihat File
                                        </a>
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
                                <td>{{ $item->created_at->format('d-m-Y H:i') }}</td>
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
            $('#tableGuruLeaveRequests').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });

            function syncLampiranRequirement() {
                let selectedType = $('#jenis_pengajuan').val();
                let isTaskRequired = selectedType && selectedType !== 'Cuti';

                $('#lampiran_tugas').prop('required', false);
                $('#deskripsi_tugas').prop('required', false);

                if (isTaskRequired) {
                    $('#pengajuan_tugas_hint').text('Wajib isi salah satu tugas: upload file atau deskripsi teks.');
                } else {
                    $('#pengajuan_tugas_hint').text('Untuk Cuti, tugas tidak diwajibkan.');
                }
            }

            $('#jenis_pengajuan').on('change', syncLampiranRequirement);
            $('#deskripsi_tugas').on('input', syncLampiranRequirement);
            syncLampiranRequirement();
        });
    </script>
@stop
