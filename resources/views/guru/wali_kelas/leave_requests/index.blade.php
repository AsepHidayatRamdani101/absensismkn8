@extends('adminlte::page')

@include('guru.partials.mobile-ux')

@section('title', 'Pengajuan Izin/Sakit Siswa')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('css')
    <style>
        #quickLeaveCard .quick-leave-toolbar {
            background: #f8fafc;
        }

        #tableQuickLeave thead th {
            white-space: nowrap;
            vertical-align: middle;
            background: #f8fafc;
        }

        #tableQuickLeave tbody td {
            vertical-align: middle;
        }

        #tableQuickLeave .btn-group .btn {
            min-width: 64px;
            font-weight: 600;
        }

        #tableQuickLeave tr.row-selected-izin {
            background: #eff6ff !important;
        }

        #tableQuickLeave tr.row-selected-sakit {
            background: #fffbeb !important;
        }

        #quickLeaveSummary .summary-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 600;
            border: 1px solid transparent;
        }

        #quickLeaveSummary .chip-izin {
            color: #1e3a8a;
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        #quickLeaveSummary .chip-sakit {
            color: #92400e;
            background: #fffbeb;
            border-color: #fde68a;
        }

        @media (max-width: 767.98px) {
            #tableQuickLeave .btn-group .btn {
                min-width: 48px;
                font-size: .74rem;
                padding: .25rem .4rem;
            }
        }
    </style>
@stop

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

    @if ($pendingCount > 0)
        <div class="alert alert-warning">
            Ada <strong>{{ $pendingCount }}</strong> pengajuan siswa yang masih menunggu verifikasi wali kelas.
        </div>
    @endif

    {{-- Input cepat massal: tandai banyak siswa sekaligus lalu simpan sekali --}}
    <div class="card mb-3" id="quickLeaveCard">
        <div class="card-header">
            <strong><i class="fas fa-bolt mr-1 text-warning"></i> Input Cepat Izin/Sakit (Banyak Siswa Sekaligus)</strong>
        </div>
        <div class="card-body p-0">
            <div class="px-3 py-2 border-bottom quick-leave-toolbar">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3 mb-2">
                        <label class="mb-1">Tanggal Mulai</label>
                        <input type="date" id="quickTanggalMulai" class="form-control form-control-sm"
                            value="{{ now()->toDateString() }}">
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label class="mb-1">Tanggal Selesai</label>
                        <input type="date" id="quickTanggalSelesai" class="form-control form-control-sm"
                            value="{{ now()->toDateString() }}">
                    </div>
                    <div class="form-group col-md-6 mb-2">
                        <label class="mb-1">Alasan Umum <span class="text-danger">*</span></label>
                        <input type="text" id="quickAlasanUmum" class="form-control form-control-sm"
                            placeholder="Contoh: Sakit demam / izin acara keluarga. Dipakai jika alasan per siswa kosong.">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                    <div id="quickLeaveSummary" class="d-flex flex-wrap align-items-center" style="gap:.4rem;">
                        <small class="text-muted mb-0">Klik Izin/Sakit pada siswa yang diperlukan, lalu simpan sekali.</small>
                        <span class="summary-chip chip-izin">Izin <strong id="quickCountIzin">0</strong></span>
                        <span class="summary-chip chip-sakit">Sakit <strong id="quickCountSakit">0</strong></span>
                    </div>
                    <div class="d-flex" style="gap:.4rem;">
                        <button type="button" id="btnQuickReset" class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fas fa-undo mr-1"></i> Reset
                        </button>
                        <button type="button" id="btnQuickSave" class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-save mr-1"></i> Simpan Semua
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-3 py-2 border-bottom">
                <input type="text" id="quickSearchStudent" class="form-control form-control-sm"
                    placeholder="Cari nama siswa...">
            </div>

            <div class="table-responsive p-3 pt-2">
                <table id="tableQuickLeave" class="table table-bordered table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="width:6%;">No</th>
                            <th>Nama Siswa</th>
                            <th style="width:22%;">Status</th>
                            <th style="width:30%;">Alasan Khusus (Opsional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr data-student-id="{{ $student->id }}"
                                data-student-name="{{ strtolower($student->nama_lengkap) }}">
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $student->nama_lengkap }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary js-quick-status active"
                                            data-status="">Hadir</button>
                                        <button type="button" class="btn btn-outline-info js-quick-status"
                                            data-status="Izin">Izin</button>
                                        <button type="button" class="btn btn-outline-warning js-quick-status"
                                            data-status="Sakit">Sakit</button>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm js-quick-alasan"
                                        placeholder="Kosongkan untuk pakai alasan umum" disabled>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;"
            data-toggle="collapse" data-target="#singleLeaveFormBody" aria-expanded="false">
            <strong>Input Satuan Dengan Bukti Surat/Chat (Opsional)</strong>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="card-body collapse" id="singleLeaveFormBody">
            <form method="POST" action="{{ route('guru.wali-kelas.leave-requests.hardfile') }}"
                enctype="multipart/form-data">
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
                            placeholder="Contoh: Bukti chat orang tua diverifikasi.">
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Screenshot Surat/Chat Orang Tua</label>
                    <input type="file" name="foto_surat" id="wali_foto_surat" class="form-control-file" accept="image/*"
                        required data-preview-wrap="#wali_foto_preview_wrap" data-preview-image="#wali_foto_preview">
                    <small class="text-muted">Format: JPG/JPEG/PNG/WEBP, maksimal 5MB, minimal 600x600 px.</small>
                    <div id="wali_foto_preview_wrap" class="mt-2 d-none">
                        <img id="wali_foto_preview" src="" alt="Preview Bukti"
                            style="max-width: 240px; max-height: 240px; border:1px solid #e5e7eb; border-radius:.35rem;">
                    </div>
                </div>

                <div class="form-group">
                    <label>Alasan</label>
                    <textarea name="alasan" rows="2" class="form-control" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Izin/Sakit + Auto Absen
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
                            <th>Sumber Input</th>
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
                                    @php
                                        $catatanLower = strtolower((string) ($item->catatan_wali ?? ''));
                                        $isOfficerDirect = str_contains($catatanLower, 'pengurus kelas');
                                        $isWaliDirect = str_contains($catatanLower, 'input langsung wali kelas');
                                    @endphp

                                    @if ($isOfficerDirect)
                                        <span class="badge badge-info">Pengurus Kelas (Direct)</span>
                                    @elseif ($isWaliDirect)
                                        <span class="badge badge-primary">Wali Kelas (Direct)</span>
                                    @elseif ($item->status_pengajuan === 'Menunggu')
                                        <span class="badge badge-secondary">Siswa (Pengajuan)</span>
                                    @else
                                        <span class="badge badge-success">Siswa (Diverifikasi Wali)</span>
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
                                    <div class="d-flex flex-wrap" style="gap: .4rem;">
                                        @if ($item->status_pengajuan === 'Menunggu')
                                            <form method="POST"
                                                action="{{ route('guru.wali-kelas.leave-requests.approve', $item->id) }}"
                                                class="d-inline js-approve-form"
                                                data-student="{{ $item->student->nama_lengkap ?? '-' }}">
                                                @csrf
                                                <input type="hidden" name="catatan_wali"
                                                    value="Disetujui oleh wali kelas.">
                                                <button type="submit" class="btn btn-success btn-xs" title="Setujui">
                                                    <i class="fas fa-check"></i> Setujui
                                                </button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('guru.wali-kelas.leave-requests.reject', $item->id) }}"
                                                class="d-inline js-reject-form"
                                                data-student="{{ $item->student->nama_lengkap ?? '-' }}">
                                                @csrf
                                                <input type="hidden" name="catatan_wali"
                                                    value="Pengajuan ditolak oleh wali kelas.">
                                                <button type="submit" class="btn btn-danger btn-xs" title="Tolak">
                                                    <i class="fas fa-times"></i> Tolak
                                                </button>
                                            </form>
                                        @endif

                                        <button type="button" class="btn btn-warning btn-xs js-edit-leave" title="Edit"
                                            data-id="{{ $item->id }}"
                                            data-student="{{ $item->student->nama_lengkap ?? '-' }}"
                                            data-jenis="{{ $item->jenis_pengajuan }}"
                                            data-mulai="{{ optional($item->tanggal_mulai)->format('Y-m-d') }}"
                                            data-selesai="{{ optional($item->tanggal_selesai)->format('Y-m-d') }}"
                                            data-alasan="{{ $item->alasan }}"
                                            data-catatan="{{ $item->catatan_wali }}"
                                            data-action="{{ route('guru.wali-kelas.leave-requests.update', $item->id) }}">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>

                                        <form method="POST"
                                            action="{{ route('guru.wali-kelas.leave-requests.destroy', $item->id) }}"
                                            class="d-inline js-delete-form"
                                            data-student="{{ $item->student->nama_lengkap ?? '-' }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-xs" title="Hapus">
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </button>
                                        </form>
                                    </div>

                                    @if ($item->status_pengajuan !== 'Menunggu')
                                        <small class="text-muted d-block mt-1">Diproses oleh
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

    {{-- Modal Edit Izin/Sakit --}}
    <div class="modal fade" id="modalEditLeave" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="formEditLeave" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="fas fa-edit mr-1"></i> Edit Izin/Sakit — <span
                                id="editLeaveStudentName"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Jenis</label>
                                <select name="jenis_pengajuan" id="editJenisPengajuan" class="form-control" required>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" id="editTanggalMulai" class="form-control"
                                    required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" id="editTanggalSelesai" class="form-control"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alasan</label>
                            <textarea name="alasan" id="editAlasan" rows="2" class="form-control" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Catatan Wali</label>
                            <input type="text" name="catatan_wali" id="editCatatanWali" class="form-control"
                                placeholder="Opsional">
                        </div>

                        <div class="form-group mb-0">
                            <label>Ganti Foto Surat (Opsional)</label>
                            <input type="file" name="foto_surat" class="form-control-file" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak ingin mengganti. Format: JPG/JPEG/PNG/WEBP,
                                maksimal 5MB, minimal 600x600 px.</small>
                        </div>

                        <div class="alert alert-info mb-0 mt-3 py-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Jika pengajuan sudah <strong>Disetujui</strong>, absensi siswa akan otomatis disesuaikan
                            dengan tanggal baru.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save mr-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
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
                let rowStatus = (data[8] || '').trim();
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

            // ================= Input Cepat Izin/Sakit Massal =================
            let isSavingQuickLeave = false;
            // Draft status per siswa: { studentId: 'Izin' | 'Sakit' }
            const quickDraft = new Map();

            function updateQuickToolbarState() {
                let izin = 0;
                let sakit = 0;

                quickDraft.forEach(function(status) {
                    if (status === 'Izin') izin++;
                    if (status === 'Sakit') sakit++;
                });

                $('#quickCountIzin').text(izin);
                $('#quickCountSakit').text(sakit);
                $('#btnQuickSave').prop('disabled', quickDraft.size === 0 || isSavingQuickLeave);
                $('#btnQuickReset').prop('disabled', quickDraft.size === 0 || isSavingQuickLeave);
            }

            function setQuickRowStatus($row, status) {
                const studentId = String($row.data('student-id'));
                const $buttons = $row.find('.js-quick-status');
                const $alasan = $row.find('.js-quick-alasan');

                $buttons.removeClass('active');
                $row.removeClass('row-selected-izin row-selected-sakit');

                if (status === 'Izin' || status === 'Sakit') {
                    quickDraft.set(studentId, status);
                    $buttons.filter('[data-status="' + status + '"]').addClass('active');
                    $row.addClass(status === 'Izin' ? 'row-selected-izin' : 'row-selected-sakit');
                    $alasan.prop('disabled', false);
                } else {
                    quickDraft.delete(studentId);
                    $buttons.filter('[data-status=""]').addClass('active');
                    $alasan.prop('disabled', true).val('');
                }

                updateQuickToolbarState();
            }

            $('#tableQuickLeave').on('click', '.js-quick-status', function() {
                setQuickRowStatus($(this).closest('tr'), $(this).data('status'));
            });

            $('#quickSearchStudent').on('input', function() {
                const query = ($(this).val() || '').toLowerCase().trim();

                $('#tableQuickLeave tbody tr').each(function() {
                    const name = String($(this).data('student-name') || '');
                    $(this).toggle(query === '' || name.indexOf(query) !== -1);
                });
            });

            $('#btnQuickReset').on('click', function() {
                $('#tableQuickLeave tbody tr').each(function() {
                    setQuickRowStatus($(this), '');
                });
            });

            $('#btnQuickSave').on('click', function() {
                if (quickDraft.size === 0 || isSavingQuickLeave) {
                    return;
                }

                const tanggalMulai = $('#quickTanggalMulai').val();
                const tanggalSelesai = $('#quickTanggalSelesai').val();
                const alasanUmum = ($('#quickAlasanUmum').val() || '').trim();

                if (!tanggalMulai || !tanggalSelesai) {
                    Swal.fire('Gagal', 'Tanggal mulai dan tanggal selesai wajib diisi.', 'error');
                    return;
                }

                if (tanggalSelesai < tanggalMulai) {
                    Swal.fire('Gagal', 'Tanggal selesai tidak boleh sebelum tanggal mulai.', 'error');
                    return;
                }

                if (alasanUmum === '') {
                    Swal.fire('Gagal', 'Isi alasan umum terlebih dahulu.', 'error');
                    $('#quickAlasanUmum').focus();
                    return;
                }

                const entries = [];

                $('#tableQuickLeave tbody tr').each(function() {
                    const studentId = String($(this).data('student-id'));
                    const status = quickDraft.get(studentId);

                    if (status) {
                        entries.push({
                            student_id: Number(studentId),
                            jenis_pengajuan: status,
                            alasan: ($(this).find('.js-quick-alasan').val() || '').trim(),
                        });
                    }
                });

                Swal.fire({
                    title: 'Simpan Izin/Sakit?',
                    html: '<strong>' + entries.length +
                        ' siswa</strong> akan dicatat Izin/Sakit dan absensi otomatis diisikan.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-save mr-1"></i> Ya, Simpan Semua',
                    cancelButtonText: 'Batal'
                }).then(async function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    isSavingQuickLeave = true;
                    updateQuickToolbarState();
                    $('#btnQuickSave').html(
                        '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Menyimpan...'
                    );

                    try {
                        const response = await fetch(
                            "{{ route('guru.wali-kelas.leave-requests.batch') }}", {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': @json(csrf_token()),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    tanggal_mulai: tanggalMulai,
                                    tanggal_selesai: tanggalSelesai,
                                    alasan_umum: alasanUmum,
                                    entries: entries,
                                }),
                            });
                        const payload = await response.json();

                        if (!response.ok) {
                            const validationErrors = payload.errors ?
                                Object.values(payload.errors).flat().join(' ') : '';
                            throw new Error(validationErrors || payload.message ||
                                'Izin/sakit gagal disimpan.');
                        }

                        await Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: payload.message,
                            timer: 2500,
                            showConfirmButton: false,
                        });

                        // Muat ulang untuk menampilkan riwayat terbaru.
                        window.location.reload();
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal menyimpan',
                            text: error.message || 'Terjadi kesalahan saat menyimpan.',
                        });
                    } finally {
                        isSavingQuickLeave = false;
                        $('#btnQuickSave').html('<i class="fas fa-save mr-1"></i> Simpan Semua');
                        updateQuickToolbarState();
                    }
                });
            });

            // Buka modal edit dengan data dari baris tabel
            $(document).on('click', '.js-edit-leave', function() {
                let btn = $(this);

                $('#formEditLeave').attr('action', btn.data('action'));
                $('#editLeaveStudentName').text(btn.data('student'));
                $('#editJenisPengajuan').val(btn.data('jenis'));
                $('#editTanggalMulai').val(btn.data('mulai'));
                $('#editTanggalSelesai').val(btn.data('selesai'));
                $('#editAlasan').val(btn.data('alasan'));
                $('#editCatatanWali').val(btn.data('catatan'));

                $('#modalEditLeave').modal('show');
            });

            // Konfirmasi SweetAlert untuk simpan perubahan edit
            $('#formEditLeave').on('submit', function(e) {
                e.preventDefault();
                let form = this;

                Swal.fire({
                    title: 'Simpan Perubahan?',
                    html: 'Data izin/sakit <strong>' + $('#editLeaveStudentName').text() +
                        '</strong> akan diperbarui.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-save mr-1"></i> Ya, Simpan',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Konfirmasi SweetAlert untuk hapus
            $(document).on('submit', '.js-delete-form', function(e) {
                e.preventDefault();
                let form = this;

                Swal.fire({
                    title: 'Hapus Data Izin/Sakit?',
                    html: 'Data izin/sakit <strong>' + $(form).data('student') +
                        '</strong> akan dihapus permanen beserta absensi otomatis yang terkait.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Konfirmasi SweetAlert untuk setujui
            $(document).on('submit', '.js-approve-form', function(e) {
                e.preventDefault();
                let form = this;

                Swal.fire({
                    title: 'Setujui Pengajuan?',
                    html: 'Pengajuan <strong>' + $(form).data('student') +
                        '</strong> akan disetujui dan absensi otomatis diisikan.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check mr-1"></i> Ya, Setujui',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Konfirmasi SweetAlert untuk tolak
            $(document).on('submit', '.js-reject-form', function(e) {
                e.preventDefault();
                let form = this;

                Swal.fire({
                    title: 'Tolak Pengajuan?',
                    html: 'Pengajuan <strong>' + $(form).data('student') + '</strong> akan ditolak.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-times mr-1"></i> Ya, Tolak',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Tampilkan notifikasi hasil aksi dengan SweetAlert
            @if (session('success'))
                Swal.fire({
                    title: 'Berhasil',
                    text: @json(session('success')),
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    title: 'Gagal',
                    text: @json(session('error')),
                    icon: 'error'
                });
            @endif
        });
    </script>

    @include('components.image-upload-preview-script')
@stop
