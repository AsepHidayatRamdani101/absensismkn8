@extends('adminlte::page')

@include('guru.partials.mobile-ux')

@section('title', 'Pengajuan Guru')

@section('plugins.Datatables', true)

@push('css')
    <style>
        #guruLeavePage .card-header {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        #guruLeavePage .status-badge {
            min-width: 88px;
            text-align: center;
            font-weight: 600;
        }

        #guruLeavePage .task-file-btn {
            border-radius: .45rem;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            #guruLeavePage .mobile-info {
                border-radius: .65rem;
                border: 1px solid #bfdbfe;
                background: #eff6ff;
                color: #1e3a8a;
            }

            #guruLeavePage .form-row {
                display: block;
            }

            #guruLeavePage .form-group {
                margin-bottom: .8rem;
            }

            #guruLeavePage label {
                font-size: .83rem;
                font-weight: 700;
                color: #475569;
                margin-bottom: .25rem;
            }

            #guruLeavePage textarea.form-control {
                min-height: 92px;
            }

            #guruLeavePage .submit-leave-btn {
                width: 100%;
                min-height: 42px;
                font-size: .82rem;
                box-shadow: 0 8px 16px rgba(37, 99, 235, .2);
            }

            #tableGuruLeaveRequests td,
            #tableGuruLeaveRequests th {
                vertical-align: middle;
            }

            #tableGuruLeaveRequests .task-file-btn {
                width: 100%;
                margin-bottom: .3rem;
            }

            #tableGuruLeaveRequests .status-badge {
                min-width: 96px;
            }
        }
    </style>
@endpush

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

    <div id="guruLeavePage">
        <div class="card mb-3">
            <div class="card-header">
                <strong>Form Pengajuan</strong>
            </div>
            <div class="card-body">
                <div class="alert mobile-info d-md-none py-2 px-3 mb-3">
                    Isi form singkat, lalu tekan <strong>Kirim Pengajuan</strong>. Riwayat terbaru tampil di tabel bawah.
                </div>
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
                            <input type="date" name="tanggal_mulai" class="form-control"
                                value="{{ old('tanggal_mulai') }}" required>
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
                        <button type="submit" class="btn btn-primary submit-leave-btn">
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
                                <th width="10%">Aksi</th>
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
                                                class="btn btn-xs btn-outline-primary task-file-btn">
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
                                            <span class="badge badge-success status-badge">Disetujui</span>
                                        @elseif ($item->status_pengajuan === 'Ditolak')
                                            <span class="badge badge-danger status-badge">Ditolak</span>
                                        @else
                                            <span class="badge badge-warning status-badge">Menunggu</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->created_at->format('d-m-Y H:i') }}</td>
                                    <td>
                                        @if ($item->status_pengajuan === 'Menunggu')
                                            <div class="d-flex" style="gap:.35rem;">
                                                <button type="button" class="btn btn-xs btn-warning btn-edit-pengajuan"
                                                    data-action="{{ route('guru.leave-requests.update', $item->id) }}"
                                                    data-jenis="{{ $item->jenis_pengajuan }}"
                                                    data-tanggal-mulai="{{ optional($item->tanggal_mulai)->format('Y-m-d') }}"
                                                    data-tanggal-selesai="{{ optional($item->tanggal_selesai)->format('Y-m-d') }}"
                                                    data-alasan="{{ $item->alasan }}"
                                                    data-deskripsi="{{ $item->deskripsi_tugas ?? '' }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form method="POST"
                                                    action="{{ route('guru.leave-requests.destroy', $item->id) }}"
                                                    class="d-inline form-delete-pengajuan">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-xs btn-danger btn-delete-pengajuan">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editPengajuanModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="formEditPengajuan" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h5 class="modal-title">Edit Pengajuan Guru</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Jenis Pengajuan</label>
                                    <select name="jenis_pengajuan" id="edit_jenis_pengajuan" class="form-control"
                                        required>
                                        <option value="">- Pilih Jenis -</option>
                                        <option value="Izin">Izin</option>
                                        <option value="Sakit">Sakit</option>
                                        <option value="Cuti">Cuti</option>
                                        <option value="Dinas Luar">Dinas Luar</option>
                                        <option value="Home Visit">Home Visit</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-4">
                                    <label>Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai"
                                        class="form-control" required>
                                </div>

                                <div class="form-group col-md-4">
                                    <label>Tanggal Selesai</label>
                                    <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai"
                                        class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Alasan</label>
                                <textarea name="alasan" id="edit_alasan" rows="3" class="form-control" required></textarea>
                            </div>

                            <div class="form-group mb-0">
                                <label>Lampiran Tugas (opsional, isi jika ingin mengganti file)</label>
                                <input type="file" name="lampiran_tugas" id="edit_lampiran_tugas"
                                    class="form-control-file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>

                            <div class="form-group mt-3 mb-0">
                                <label>Deskripsi Tugas</label>
                                <textarea name="deskripsi_tugas" id="edit_deskripsi_tugas" rows="3" class="form-control"
                                    placeholder="Boleh diisi jika tugas tidak diupload sebagai file."></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
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
            let isMobile = window.matchMedia('(max-width: 767.98px)').matches;

            $('#tableGuruLeaveRequests').DataTable({
                responsive: !isMobile,
                scrollX: isMobile,
                autoWidth: false,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                },
                columnDefs: [
                    ...(isMobile ? [{
                        targets: [0, 4, 7],
                        visible: false
                    }] : [])
                ]
            });

            $(document).on('click', '.btn-edit-pengajuan', function() {
                let button = $(this);

                $('#formEditPengajuan').attr('action', button.data('action'));
                $('#edit_jenis_pengajuan').val(button.data('jenis'));
                $('#edit_tanggal_mulai').val(button.data('tanggal-mulai'));
                $('#edit_tanggal_selesai').val(button.data('tanggal-selesai'));
                $('#edit_alasan').val(button.data('alasan'));
                $('#edit_deskripsi_tugas').val(button.data('deskripsi'));
                $('#edit_lampiran_tugas').val('');

                $('#editPengajuanModal').modal('show');
            });

            $(document).on('submit', '.form-delete-pengajuan', function(e) {
                if (!confirm('Hapus pengajuan ini? Data yang dihapus tidak dapat dikembalikan.')) {
                    e.preventDefault();
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
