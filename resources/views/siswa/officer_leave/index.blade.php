@extends('adminlte::page')

@section('title', 'Izin/Sakit Siswa Kelas')

@section('plugins.Datatables', true)
@section('plugins.Select2', true)

@section('css')
    <style>
        /* Match Select2 height & border to standard AdminLTE form-control */
        .select2-container--default .select2-selection--single {
            height: calc(2.25rem + 2px);
            border: 1px solid #ced4da;
            border-radius: .25rem;
            padding: .2rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
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
            <h1 class="mb-1">Izin / Sakit Siswa Kelas</h1>
            <p class="text-muted mb-0">Input langsung izin/sakit siswa dengan upload bukti screenshot surat atau chat orang
                tua.</p>
        </div>
        <span class="badge badge-light border px-3 py-2 mt-2 mt-md-0">
            Pengurus: {{ $officer->nama_lengkap }} &mdash; {{ $officer->jabatan_kelas_label }}
            &mdash; {{ $officer->classroom->nama_kelas ?? '-' }}
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

    <div class="card mb-3">
        <div class="card-header">
            <strong>Input Izin / Sakit Teman Sekelas</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('siswa.officer-leave.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- Baris 1: Identitas pengajuan --}}
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4 col-lg-3">
                        <label class="mb-1 font-weight-semibold">Siswa <span class="text-danger">*</span></label>
                        <select name="student_id" id="select_student_id" class="form-control" required style="width:100%;">
                            <option value="">— Cari / Pilih Siswa —</option>
                            @foreach ($students as $s)
                                <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>
                                    {{ $s->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-6 col-md-2">
                        <label class="mb-1 font-weight-semibold">Jenis <span class="text-danger">*</span></label>
                        <select name="jenis_pengajuan" class="form-control" required>
                            <option value="">— Pilih —</option>
                            <option value="Izin" @selected(old('jenis_pengajuan') === 'Izin')>Izin</option>
                            <option value="Sakit" @selected(old('jenis_pengajuan') === 'Sakit')>Sakit</option>
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
                        <label class="mb-1 font-weight-semibold">Catatan</label>
                        <input type="text" name="catatan" class="form-control" maxlength="255"
                            value="{{ old('catatan') }}" placeholder="Misal: Chat WA orang tua.">
                    </div>
                </div>

                {{-- Baris 2: Isi & bukti --}}
                <div class="form-row align-items-start">
                    <div class="form-group col-md-6">
                        <label class="mb-1 font-weight-semibold">Alasan <span class="text-danger">*</span></label>
                        <textarea name="alasan" rows="3" class="form-control" placeholder="Jelaskan alasan izin/sakit siswa." required>{{ old('alasan') }}</textarea>
                    </div>

                    <div class="form-group col-md-6">
                        <label class="mb-1 font-weight-semibold">
                            Bukti Screenshot Surat / Chat Orang Tua <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="foto_surat" id="officer_direct_foto_surat" class="form-control-file"
                            accept="image/*" required data-preview-wrap="#officer_direct_preview_wrap"
                            data-preview-image="#officer_direct_preview">
                        <small class="text-muted d-block mt-1">
                            JPG / JPEG / PNG / WEBP &bull; maks 5 MB &bull; minimal 600&times;600 px.
                        </small>
                        <div id="officer_direct_preview_wrap" class="mt-2 d-none">
                            <img id="officer_direct_preview" src="" alt="Preview Bukti"
                                style="max-width:260px;max-height:200px;border:1px solid #e5e7eb;border-radius:.4rem;object-fit:contain;">
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-1">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Izin/Sakit + Auto Absen
                    </button>
                    <button type="reset" class="btn btn-outline-secondary ml-2" id="btnResetForm">
                        <i class="fas fa-undo mr-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Riwayat Izin/Sakit Kelas {{ $officer->classroom->nama_kelas ?? '-' }}</strong>
        </div>
        <div class="card-body">
            @php
                $approvedCount = $requests->where('status_pengajuan', 'Disetujui')->count();
                $pendingCount = $requests->where('status_pengajuan', 'Menunggu')->count();
                $rejectedCount = $requests->where('status_pengajuan', 'Ditolak')->count();
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
                    <label for="filterOfficerStatus" class="mb-1">Filter Status</label>
                    <select id="filterOfficerStatus" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="Menunggu">Menunggu</option>
                        <option value="Disetujui">Disetujui</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
                <div class="form-group col-md-4 mb-0">
                    <label for="filterOfficerNama" class="mb-1">Filter Nama Siswa</label>
                    <input type="text" id="filterOfficerNama" class="form-control form-control-sm"
                        placeholder="Cari nama...">
                </div>
            </div>

            <div class="table-responsive">
                <table id="tableOfficerLeaveHistory" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th>Siswa</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Alasan</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th>Sumber</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $item)
                            @php
                                $catatanLower = strtolower((string) ($item->catatan_wali ?? ''));
                                $isOfficer = str_contains($catatanLower, 'pengurus kelas');
                                $isWaliDirect = str_contains($catatanLower, 'input langsung wali kelas');
                            @endphp
                            <tr data-student-name="{{ strtolower($item->student->nama_lengkap ?? '-') }}"
                                data-status="{{ $item->status_pengajuan }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
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
                                            Lihat
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
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
                                    @if ($isOfficer)
                                        <span class="badge badge-info">Pengurus (Direct)</span>
                                    @elseif ($isWaliDirect)
                                        <span class="badge badge-primary">Wali (Direct)</span>
                                    @else
                                        <span class="badge badge-secondary">Siswa</span>
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
                if (settings.nTable.id !== 'tableOfficerLeaveHistory') {
                    return true;
                }

                const rowNode = settings.aoData[dataIndex]?.nTr;
                const statusFilter = $('#filterOfficerStatus').val();
                const namaFilter = ($('#filterOfficerNama').val() || '').toLowerCase().trim();
                const rowStatus = rowNode ? String($(rowNode).data('status') || '') : '';
                const rowNama = rowNode ? String($(rowNode).data('student-name') || '') : '';

                if (statusFilter && rowStatus !== statusFilter) {
                    return false;
                }

                if (namaFilter && !rowNama.includes(namaFilter)) {
                    return false;
                }

                return true;
            });

            const table = $('#tableOfficerLeaveHistory').DataTable({
                responsive: true,
                order: [
                    [3, 'desc']
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });

            $('#filterOfficerStatus').on('change', () => table.draw());
            $('#filterOfficerNama').on('input', () => table.draw());

            $('#select_student_id').select2({
                placeholder: '— Cari / Pilih Siswa —',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: () => 'Siswa tidak ditemukan',
                    searching: () => 'Mencari\u2026',
                },
            });

            /* Reset preview dan Select2 saat tombol Reset ditekan */
            $('#btnResetForm').on('click', function() {
                $('#select_student_id').val('').trigger('change');
                $('#officer_direct_preview_wrap').addClass('d-none');
                $('#officer_direct_preview').attr('src', '');
            });
        });
    </script>

    @include('components.image-upload-preview-script')
@stop
