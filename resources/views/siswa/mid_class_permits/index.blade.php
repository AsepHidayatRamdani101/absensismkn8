@extends('adminlte::page')

@section('title', 'Izin Pulang Siswa')

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
            <h1 class="mb-1">Izin Pulang Siswa</h1>
            <p class="text-muted mb-0">Input izin pulang karena sakit atau keperluan tertentu. Absensi otomatis terisi dari
                jam pulang hingga akhir jadwal hari ini.</p>
        </div>
        <span class="badge badge-light border px-3 py-2 mt-2 mt-md-0">
            Pengurus: {{ $officer->nama_lengkap }} — {{ $officer->jabatan_kelas_label }}
            — {{ $officer->classroom->nama_kelas ?? '-' }}
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
            <strong><i class="fas fa-home mr-1 text-warning"></i> Input Izin Pulang di Tengah Pembelajaran</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('siswa.mid-class-permits.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- Baris 1: Siswa + Tipe Izin sejajar --}}
                <div class="form-row align-items-end mb-2">
                    <div class="form-group col-12 col-md-6 mb-md-0">
                        <label class="mb-1 font-weight-semibold">Siswa <span class="text-danger">*</span></label>
                        <select name="student_id" id="officer_mid_student" class="form-control" required
                            style="width:100%;">
                            <option value="">— Cari / Pilih Siswa —</option>
                            @foreach ($students as $s)
                                <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>
                                    {{ $s->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-12 col-md-6 mb-0">
                        <label class="mb-1 font-weight-semibold d-block">Tipe Izin <span
                                class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap align-items-center" style="gap:.75rem;margin-top:.45rem;">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="officer_tipe_penuh" name="tipe_izin" value="penuh"
                                    class="custom-control-input"
                                    {{ old('tipe_izin', 'penuh') === 'penuh' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="officer_tipe_penuh">
                                    <span class="badge badge-warning">Pulang Penuh</span>
                                    <small class="text-muted ml-1">— tidak kembali</small>
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="officer_tipe_sementara" name="tipe_izin" value="sementara"
                                    class="custom-control-input" {{ old('tipe_izin') === 'sementara' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="officer_tipe_sementara">
                                    <span class="badge badge-info">Sementara</span>
                                    <small class="text-muted ml-1">— akan kembali</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Baris 2: Tanggal + Jam Pulang + Jam Kembali (kondisional) + Catatan --}}
                <div class="form-row align-items-end mb-2">
                    <div class="form-group col-6 col-md-2 mb-md-0">
                        <label class="mb-1 font-weight-semibold">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control"
                            value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                            required>
                    </div>

                    <div class="form-group col-6 col-md-2 mb-md-0">
                        <label class="mb-1 font-weight-semibold">Jam Pulang <span class="text-danger">*</span></label>
                        <input type="time" name="jam_keluar" class="form-control"
                            value="{{ old('jam_keluar', now()->format('H:i')) }}" required>
                    </div>

                    <div class="form-group col-6 col-md-2 mb-md-0" id="officer_jam_kembali_wrap"
                        style="{{ old('tipe_izin', 'penuh') === 'sementara' ? '' : 'display:none;' }}">
                        <label class="mb-1 font-weight-semibold">Jam Kembali</label>
                        <input type="time" name="jam_kembali" id="officer_jam_kembali" class="form-control"
                            value="{{ old('jam_kembali') }}">
                        <small class="text-muted">Jam kembali ke sekolah.</small>
                    </div>

                    <div class="form-group col-12 col-md mb-0">
                        <label class="mb-1 font-weight-semibold">Catatan</label>
                        <input type="text" name="catatan" class="form-control" maxlength="255"
                            value="{{ old('catatan') }}" placeholder="Misal: Izin ke dokter, disetujui orang tua.">
                    </div>
                </div>

                <p class="text-muted small mb-3" id="officer_info_penuh">
                    <i class="fas fa-info-circle text-warning mr-1"></i>
                    Semua jam belajar <strong>setelah jam pulang</strong> pada hari ini akan otomatis diisi
                    <strong>Izin</strong>.
                </p>
                <p class="text-muted small mb-3" id="officer_info_sementara" style="display:none;">
                    <i class="fas fa-info-circle text-info mr-1"></i>
                    Jam belajar <strong>antara jam pulang dan jam kembali</strong> akan otomatis diisi
                    <strong>Izin</strong>. Jam setelah kembali tetap <strong>Hadir</strong>.
                </p>

                {{-- Baris 3: Alasan + Foto --}}
                <div class="form-group col-md-6">
                    <label class="mb-1 font-weight-semibold">Alasan <span class="text-danger">*</span></label>
                    <textarea name="alasan" rows="3" class="form-control"
                        placeholder="Jelaskan alasan siswa izin pulang (sakit / keperluan lain)." required>{{ old('alasan') }}</textarea>
                </div>

                <div class="form-group col-md-6">
                    <label class="mb-1 font-weight-semibold">
                        Foto Bukti Izin
                        <small class="text-muted font-weight-normal">(opsional)</small>
                    </label>
                    <input type="file" name="foto_izin" id="officer_mid_foto" class="form-control-file"
                        accept="image/*" data-preview-wrap="#officer_mid_preview_wrap"
                        data-preview-image="#officer_mid_preview">
                    <small class="text-muted d-block mt-1">JPG / JPEG / PNG / WEBP &bull; maks 5 MB.</small>
                    <div id="officer_mid_preview_wrap" class="mt-2 d-none">
                        <img id="officer_mid_preview" src="" alt="Preview"
                            style="max-width:220px;max-height:180px;border:1px solid #e5e7eb;border-radius:.4rem;object-fit:contain;">
                    </div>
                </div>
        </div>

        <div class="border-top pt-3 mt-1">
            <button type="submit" class="btn btn-warning px-4">
                <i class="fas fa-save mr-1"></i> Simpan Izin Pulang + Auto Absen
            </button>
            <button type="reset" class="btn btn-outline-secondary ml-2" id="btnResetOfficerMid">
                <i class="fas fa-undo mr-1"></i> Reset
            </button>
        </div>
        </form>
    </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Riwayat Izin Pulang — {{ $officer->classroom->nama_kelas ?? '-' }}</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableOfficerMidPermits" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th>Siswa</th>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Jam Pulang</th>
                            <th>Jam Kembali</th>
                            <th>Alasan</th>
                            <th>Bukti</th>
                            <th>Oleh</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permits as $p)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $p->student->nama_lengkap ?? '-' }}</td>
                                <td>{{ optional($p->tanggal)->format('d-m-Y') }}</td>
                                <td>
                                    @if ($p->tipe_izin === 'sementara')
                                        <span class="badge badge-info">Sementara</span>
                                    @else
                                        <span class="badge badge-warning">Pulang Penuh</span>
                                    @endif
                                </td>
                                <td>{{ substr($p->jam_keluar, 0, 5) }}</td>
                                <td>{{ $p->jam_kembali ? substr($p->jam_kembali, 0, 5) : '-' }}</td>
                                <td>{{ $p->alasan }}</td>
                                <td>
                                    @if ($p->foto_izin_path)
                                        <a href="{{ asset('storage/' . $p->foto_izin_path) }}" target="_blank"
                                            class="btn btn-xs btn-outline-primary">Lihat</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($p->submitted_by_type === 'wali_kelas')
                                        <span class="badge badge-primary">Wali Kelas</span>
                                        <small
                                            class="d-block text-muted">{{ $p->submittedByTeacher->nama_lengkap ?? '-' }}</small>
                                    @else
                                        <span class="badge badge-info">Pengurus Kelas</span>
                                        <small
                                            class="d-block text-muted">{{ $p->submittedByStudent->nama_lengkap ?? '-' }}</small>
                                    @endif
                                </td>
                                <td>{{ $p->catatan ?? '-' }}</td>
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
        $('#tableOfficerMidPermits').DataTable({
            responsive: true,
            order: [
                [2, 'desc'],
                [3, 'desc']
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        $('#officer_mid_student').select2({
            placeholder: '— Cari / Pilih Siswa —',
            allowClear: true,
            width: '100%',
            language: {
                noResults: () => 'Siswa tidak ditemukan',
                searching: () => 'Mencari\u2026'
            },
        });

        $('#btnResetOfficerMid').on('click', function() {
            $('#officer_mid_student').val('').trigger('change');
            $('#officer_mid_preview_wrap').addClass('d-none');
            $('#officer_mid_preview').attr('src', '');
            $('#officer_tipe_penuh').prop('checked', true).trigger('change');
        });

        function toggleOfficerJamKembali() {
            const isSementara = $('#officer_tipe_sementara').is(':checked');
            $('#officer_jam_kembali_wrap').toggle(isSementara);
            $('#officer_info_sementara').toggle(isSementara);
            $('#officer_info_penuh').toggle(!isSementara);
            if (!isSementara) $('#officer_jam_kembali').val('');
        }

        $('input[name="tipe_izin"]').on('change', toggleOfficerJamKembali);
        toggleOfficerJamKembali();
        $('#officer_mid_preview_wrap').addClass('d-none');
        $('#officer_mid_preview').attr('src', '');
        });
        });
    </script>

    @include('components.image-upload-preview-script')
@stop
