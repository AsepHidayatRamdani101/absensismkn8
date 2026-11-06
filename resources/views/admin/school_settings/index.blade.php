@extends('adminlte::page')

@section('title', 'Profile Sekolah')

@section('css')
    <style>
        .school-hero {
            border-radius: 18px;
            padding: 1.5rem 1.75rem;
            color: #fff;
            background: linear-gradient(135deg, #0d6efd 0%, #4f46e5 100%);
            box-shadow: 0 12px 28px rgba(13, 110, 253, .2);
        }

        .school-hero h1 { font-weight: 700; margin: 0; font-size: 1.55rem; }
        .school-hero p { margin: .35rem 0 0; opacity: .86; }
        .settings-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .07);
            overflow: hidden;
        }
        .settings-card .card-header { background: #fff; border-bottom: 1px solid #edf0f5; padding: 1.1rem 1.35rem; }
        .section-title { font-weight: 700; color: #1f2937; margin: 0; }
        .section-subtitle { color: #6b7280; font-size: .82rem; margin: .25rem 0 0; }
        .form-label { color: #374151; font-size: .82rem; font-weight: 600; }
        .form-control {
            border-radius: 9px;
            border-color: #dfe4ec;
            min-height: 42px;
        }
        .form-control:focus { border-color: #5b8def; box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .1); }
        textarea.form-control { min-height: 92px; }
        .input-hint { color: #8a94a6; font-size: .75rem; margin-top: .35rem; }
        .logo-preview {
            min-height: 280px;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            background: linear-gradient(145deg, #f8fafc, #eef4ff);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .logo-preview img { max-width: 100%; max-height: 220px; border-radius: 12px; background: #fff; padding: .6rem; box-shadow: 0 5px 18px rgba(15, 23, 42, .1); }
        .logo-empty { color: #94a3b8; text-align: center; }
        .logo-empty i { display: block; font-size: 2.5rem; margin-bottom: .65rem; color: #93c5fd; }
        .save-button { border-radius: 9px; padding: .65rem 1.25rem; font-weight: 600; }
        .alert { border: 0; border-radius: 10px; }
    </style>
@stop

@section('content_header')
    <div class="school-hero mb-4">
        <h1><i class="fas fa-school mr-2"></i>Profile Sekolah</h1>
        <p>Kelola identitas sekolah, informasi kontak, jam masuk, dan pengaturan apresiasi dashboard.</p>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card settings-card h-100">
                <div class="card-header">
                    <h3 class="section-title"><i class="fas fa-sliders-h text-primary mr-2"></i>Pengaturan Utama</h3>
                    <p class="section-subtitle">Perbarui data yang digunakan pada halaman dan dokumen sekolah.</p>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('school-settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-row">
                            <div class="form-group col-md-7">
                                <label class="form-label" for="nama_sekolah">Nama Sekolah</label>
                                <input type="text" id="nama_sekolah" name="nama_sekolah" class="form-control @error('nama_sekolah') is-invalid @enderror" value="{{ old('nama_sekolah', $setting->nama_sekolah ?? '') }}" placeholder="Contoh: SMK Negeri 8" required>
                                @error('nama_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-5">
                                <label class="form-label" for="npsn">NPSN</label>
                                <input type="text" id="npsn" name="npsn" class="form-control @error('npsn') is-invalid @enderror" value="{{ old('npsn', $setting->npsn ?? '') }}" placeholder="Nomor pokok sekolah">
                                @error('npsn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="alamat">Alamat Sekolah</label>
                            <textarea id="alamat" name="alamat" class="form-control @error('alamat') is-invalid @enderror" placeholder="Alamat lengkap sekolah">{{ old('alamat', $setting->alamat ?? '') }}</textarea>
                            @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="telepon">Nomor Telepon</label>
                                <input type="text" id="telepon" name="telepon" class="form-control @error('telepon') is-invalid @enderror" value="{{ old('telepon', $setting->telepon ?? '') }}" placeholder="08xx / (022) xxx">
                                @error('telepon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="email">Email Sekolah</label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $setting->email ?? '') }}" placeholder="email@sekolah.sch.id">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-2">
                            <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-clock text-primary mr-2"></i>Pengaturan Kehadiran</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="jam_masuk">Jam Masuk</label>
                                    <input type="time" id="jam_masuk" name="jam_masuk" class="form-control @error('jam_masuk') is-invalid @enderror" value="{{ old('jam_masuk', isset($setting->jam_masuk) ? substr($setting->jam_masuk, 0, 5) : '07:00') }}" required>
                                    @error('jam_masuk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="batas_terlambat">Batas Terlambat (menit)</label>
                                    <input type="number" id="batas_terlambat" name="batas_terlambat" min="0" max="240" class="form-control @error('batas_terlambat') is-invalid @enderror" value="{{ old('batas_terlambat', $setting->batas_terlambat ?? 15) }}" required>
                                    @error('batas_terlambat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="monthly_achievement_duration_days">Durasi Modal Apresiasi</label>
                                    <div class="input-group">
                                        <input type="number" id="monthly_achievement_duration_days" name="monthly_achievement_duration_days" min="0" max="31" class="form-control @error('monthly_achievement_duration_days') is-invalid @enderror" value="{{ old('monthly_achievement_duration_days', $setting->monthly_achievement_duration_days ?? 14) }}" required>
                                        <div class="input-group-append"><span class="input-group-text">hari</span></div>
                                    </div>
                                    <div class="input-hint">0 = nonaktif</div>
                                    @error('monthly_achievement_duration_days')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-2">
                            <label class="form-label" for="logo">Logo Sekolah</label>
                            <div class="custom-file">
                                <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp" class="custom-file-input @error('logo') is-invalid @enderror">
                                <label class="custom-file-label" for="logo">Pilih file logo...</label>
                            </div>
                            <div class="input-hint">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                            @error('logo')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary save-button mt-2"><i class="fas fa-save mr-1"></i>Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card settings-card h-100">
                <div class="card-header">
                    <h3 class="section-title"><i class="fas fa-image text-primary mr-2"></i>Preview Logo</h3>
                    <p class="section-subtitle">Logo akan digunakan pada dokumen dan identitas sekolah.</p>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <div class="logo-preview flex-grow-1">
                        @if (!empty($setting?->logo))
                            <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo {{ $setting->nama_sekolah ?? 'Sekolah' }}">
                        @else
                            <div class="logo-empty"><i class="fas fa-school"></i><div>Logo belum diupload</div><small>Upload logo melalui form di sebelah kiri.</small></div>
                        @endif
                    </div>
                    <div class="alert alert-light border mt-3 mb-0 small"><i class="fas fa-info-circle text-primary mr-1"></i> Gunakan logo dengan latar transparan agar hasil cetak lebih rapi.</div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function() {
            $('#logo').on('change', function() {
                const fileName = this.files && this.files.length ? this.files[0].name : 'Pilih file logo...';
                $(this).next('.custom-file-label').text(fileName);
            });
        });
    </script>
@stop

@section('footer')
    @include('components.app-footer')
@stop
