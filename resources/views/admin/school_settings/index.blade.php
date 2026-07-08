@extends('adminlte::page')

@section('title', 'Profile Sekolah')

@section('content_header')
    <h1>Profile Sekolah</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="row">
                <div class="col-md-8">
                    <form action="{{ route('school-settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nama_sekolah">Nama Sekolah</label>
                                <input type="text" id="nama_sekolah" name="nama_sekolah"
                                    class="form-control @error('nama_sekolah') is-invalid @enderror"
                                    value="{{ old('nama_sekolah', $setting->nama_sekolah ?? '') }}" required>
                                @error('nama_sekolah')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="npsn">NPSN</label>
                                <input type="text" id="npsn" name="npsn"
                                    class="form-control @error('npsn') is-invalid @enderror"
                                    value="{{ old('npsn', $setting->npsn ?? '') }}">
                                @error('npsn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" rows="3" class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat', $setting->alamat ?? '') }}</textarea>
                            @error('alamat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="telepon">Telepon</label>
                                <input type="text" id="telepon" name="telepon"
                                    class="form-control @error('telepon') is-invalid @enderror"
                                    value="{{ old('telepon', $setting->telepon ?? '') }}">
                                @error('telepon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $setting->email ?? '') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="jam_masuk">Jam Masuk</label>
                                <input type="time" id="jam_masuk" name="jam_masuk"
                                    class="form-control @error('jam_masuk') is-invalid @enderror"
                                    value="{{ old('jam_masuk', isset($setting->jam_masuk) ? substr($setting->jam_masuk, 0, 5) : '07:00') }}"
                                    required>
                                @error('jam_masuk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="batas_terlambat">Batas Terlambat (menit)</label>
                                <input type="number" id="batas_terlambat" name="batas_terlambat" min="0"
                                    max="240" class="form-control @error('batas_terlambat') is-invalid @enderror"
                                    value="{{ old('batas_terlambat', $setting->batas_terlambat ?? 15) }}" required>
                                @error('batas_terlambat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="logo">Logo Sekolah</label>
                                <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp"
                                    class="form-control-file @error('logo') is-invalid @enderror">
                                @error('logo')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Perubahan
                        </button>
                    </form>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Preview Logo</h3>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            @if (!empty($setting?->logo))
                                <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo Sekolah"
                                    style="max-height: 220px; max-width: 100%; border-radius: 6px; border: 1px solid #e3e3e3; padding: 6px; background: #fff;">
                            @else
                                <p class="text-muted mb-0">Logo belum diupload.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('footer')
    @include('components.app-footer')
@stop
