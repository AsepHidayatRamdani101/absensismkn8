@extends('adminlte::page')

@section('title', 'Approve Pengajuan Izin Guru')

@section('plugins.Datatables', true)
@section('plugins.Select2', true)

@section('css')
    <style>
        .select2-container--default .select2-selection--single {
            height: calc(2.25rem + 2px);
            border: 1px solid #ced4da;
            border-radius: .25rem;
            padding: .2rem .75rem;
            font-size: 1rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.8;
            padding-left: 0;
            color: #495057;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px);
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #80bdff;
            box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25);
            outline: 0;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Approve Pengajuan Izin Guru</h1>
            <p class="text-muted mb-0">Verifikasi pengajuan izin, sakit, cuti, dinas luar, dan home visit guru.</p>
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

    {{-- Form input langsung oleh kurikulum (guru chat WA -> kurikulum input) --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong><i class="fas fa-plus-circle mr-1 text-primary"></i> Input Langsung Izin / Ketidakhadiran Guru</strong>
            <small class="text-muted ml-2">(Guru lapor via WA &rarr; kurikulum input langsung)</small>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('kurikulum.teacher-leave-requests.direct-store') }}"
                enctype="multipart/form-data">
                @csrf

                <div class="form-row align-items-end">
                    <div class="form-group col-md-4 col-lg-3">
                        <label class="mb-1 font-weight-semibold">Guru <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="select_teacher_direct" class="form-control" required
                            style="width:100%;">
                            <option value="">— Cari / Pilih Guru —</option>
                            @foreach ($teachers as $t)
                                <option value="{{ $t->id }}" @selected(old('teacher_id') == $t->id)>
                                    {{ $t->nama_lengkap }}
                                    @if ($t->nip)
                                        ({{ $t->nip }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-6 col-md-2">
                        <label class="mb-1 font-weight-semibold">Jenis <span class="text-danger">*</span></label>
                        <select name="jenis_pengajuan" class="form-control" required>
                            <option value="">— Pilih —</option>
                            @foreach (['Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'Home Visit'] as $jenis)
                                <option value="{{ $jenis }}" @selected(old('jenis_pengajuan') === $jenis)>{{ $jenis }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-6 col-md-2">
                        <label class="mb-1 font-weight-semibold">Tgl Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-control"
                            value="{{ old('tanggal_mulai', now()->toDateString()) }}" required>
                    </div>

                    <div class="form-group col-6 col-md-2">
                        <label class="mb-1 font-weight-semibold">Tgl Selesai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-control"
                            value="{{ old('tanggal_selesai', now()->toDateString()) }}" required>
                    </div>

                    <div class="form-group col-6 col-md-2 col-lg-3">
                        <label class="mb-1 font-weight-semibold">Alasan <span class="text-danger">*</span></label>
                        <input type="text" name="alasan" class="form-control" maxlength="255"
                            value="{{ old('alasan') }}" placeholder="Misal: Sakit, lapor via WA." required>
                    </div>
                </div>

                <div class="form-row align-items-start">
                    <div class="form-group col-md-6">
                        <label class="mb-1 font-weight-semibold">Deskripsi Tugas untuk Siswa</label>
                        <textarea name="deskripsi_tugas" rows="3" class="form-control"
                            placeholder="Jelaskan tugas yang diberikan untuk siswa selama guru tidak hadir.&#10;Wajib diisi untuk semua jenis selain Cuti.">{{ old('deskripsi_tugas') }}</textarea>
                        <small class="text-muted">Wajib diisi untuk Izin / Sakit / Dinas Luar / Home Visit jika tidak ada
                            file lampiran.</small>
                    </div>

                    <div class="form-group col-md-6">
                        <label class="mb-1 font-weight-semibold">File Lampiran Tugas</label>
                        <input type="file" name="lampiran_tugas" class="form-control-file"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted d-block mt-1">PDF / DOC / DOCX / JPG / PNG &bull; maks 5 MB.</small>
                    </div>
                </div>

                <div class="border-top pt-3 mt-1">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> Simpan &amp; Tandai Disetujui + Auto Absen
                    </button>
                    <button type="reset" class="btn btn-outline-secondary ml-2" id="btnResetDirectForm">
                        <i class="fas fa-undo mr-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                order: [
                    [3, 'desc']
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });

            $('#select_teacher_direct').select2({
                placeholder: '— Cari / Pilih Guru —',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: () => 'Guru tidak ditemukan',
                    searching: () => 'Mencari\u2026',
                },
            });

            $('#btnResetDirectForm').on('click', function() {
                $('#select_teacher_direct').val('').trigger('change');
            });
        });
    </script>
@stop
