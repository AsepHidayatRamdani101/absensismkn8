@extends('adminlte::page')

@section('title', 'Identitas Siswa')

@section('plugins.Sweetalert2', true)

@section('content_header')
    @php
        $identityFields = [
            'Orang Tua / Wali' => $student->nama_orang_tua_wali,
            'Alamat' => $student->alamat,
            'No HP Siswa' => $student->no_hp,
            'No HP Orang Tua' => $student->no_hp_orang_tua,
            'Tinggi Badan' => $student->tinggi_badan,
            'Berat Badan' => $student->berat_badan,
        ];

        $missingIdentityFields = collect($identityFields)
            ->filter(fn($value) => $value === null || trim((string) $value) === '')
            ->keys()
            ->values();

        $identityCompletion = round(
            ((count($identityFields) - $missingIdentityFields->count()) / count($identityFields)) * 100,
        );
    @endphp

    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Identitas Siswa</h1>
            <p class="text-muted mb-0">Lengkapi dan perbarui identitas pribadi Anda secara mandiri.</p>
        </div>
        <a href="{{ route('siswa.dashboard') }}" class="btn btn-outline-secondary mt-2 mt-md-0">
            <i class="fas fa-arrow-left mr-1"></i>
            Kembali ke Dashboard
        </a>
    </div>
@stop

@section('content')
    @if ($missingIdentityFields->isEmpty())
        <div class="alert alert-success">
            Profil Anda sudah lengkap ({{ $identityCompletion }}%).
            <div class="progress mt-2" style="height: 10px; border-radius: 999px;">
                <div class="progress-bar bg-success" style="width: {{ $identityCompletion }}%"></div>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            Profil belum lengkap ({{ $identityCompletion }}%). Data yang belum diisi:
            {{ $missingIdentityFields->join(', ') }}.
            <div class="progress mt-2" style="height: 10px; border-radius: 999px;">
                <div class="progress-bar bg-warning" style="width: {{ $identityCompletion }}%"></div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Data Inti</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Status Profil:</strong>
                        @if ($missingIdentityFields->isEmpty())
                            <span class="badge badge-success">Lengkap</span>
                        @else
                            <span class="badge badge-warning">Belum Lengkap</span>
                        @endif
                    </p>
                    <p class="mb-2"><strong>NIS:</strong> {{ $student->nis }}</p>
                    <p class="mb-2"><strong>NISN:</strong> {{ $student->nisn ?: '-' }}</p>
                    <p class="mb-2"><strong>Nama:</strong> {{ $student->nama_lengkap }}</p>
                    <p class="mb-2"><strong>Jenis Kelamin:</strong> {{ $student->jenis_kelamin }}</p>
                    <p class="mb-2"><strong>Kelas:</strong> {{ $student->classroom?->nama_kelas ?? '-' }}</p>
                    <p class="mb-2"><strong>Jurusan:</strong> {{ $student->classroom?->major?->nama_jurusan ?? '-' }}</p>
                    <p class="mb-1"><strong>Progress:</strong> {{ $identityCompletion }}%</p>
                    <div class="progress" style="height: 10px; border-radius: 999px;">
                        <div class="progress-bar {{ $identityCompletion === 100 ? 'bg-success' : 'bg-warning' }}"
                            style="width: {{ $identityCompletion }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Form Identitas</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border">
                        Data inti seperti NIS, NISN, nama, dan kelas dikelola admin. Anda dapat mengubah jenis kelamin dan
                        password di form ini.
                    </div>

                    <form method="POST" action="{{ route('siswa.identity.update') }}" id="studentIdentityForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        <option value="L"
                                            {{ old('jenis_kelamin', $student->jenis_kelamin) === 'L' ? 'selected' : '' }}>
                                            Laki-laki</option>
                                        <option value="P"
                                            {{ old('jenis_kelamin', $student->jenis_kelamin) === 'P' ? 'selected' : '' }}>
                                            Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Orang Tua / Wali</label>
                                    <input type="text" name="nama_orang_tua_wali" class="form-control"
                                        value="{{ old('nama_orang_tua_wali', $student->nama_orang_tua_wali) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>No HP Siswa</label>
                                    <input type="text" name="no_hp" class="form-control"
                                        value="{{ old('no_hp', $student->no_hp) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>No HP Orang Tua</label>
                                    <input type="text" name="no_hp_orang_tua" class="form-control"
                                        value="{{ old('no_hp_orang_tua', $student->no_hp_orang_tua) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tinggi Badan (cm)</label>
                                    <input type="number" name="tinggi_badan" class="form-control" min="0"
                                        step="0.01" value="{{ old('tinggi_badan', $student->tinggi_badan) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Berat Badan (kg)</label>
                                    <input type="number" name="berat_badan" class="form-control" min="0"
                                        step="0.01" value="{{ old('berat_badan', $student->berat_badan) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" class="form-control" rows="4">{{ old('alamat', $student->alamat) }}</textarea>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold mb-3">Ubah Password</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Password Saat Ini</label>
                                    <input type="password" name="current_password" class="form-control"
                                        placeholder="Kosongkan jika tidak ingin ubah">
                                    @error('current_password')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Password Baru</label>
                                    <input type="password" name="password" class="form-control"
                                        placeholder="Min. 8 karakter">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Konfirmasi Password Baru</label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                        placeholder="Ulangi password baru">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            Simpan Identitas
                        </button>
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
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: '{{ session('success') }}'
                });
            @endif

            $('#studentIdentityForm').on('submit', function(e) {
                let noHpSiswa = $.trim($('input[name="no_hp"]').val());
                let noHpOrangTua = $.trim($('input[name="no_hp_orang_tua"]').val());
                let tinggiBadan = $.trim($('input[name="tinggi_badan"]').val());
                let beratBadan = $.trim($('input[name="berat_badan"]').val());

                if (tinggiBadan !== '' && Number(tinggiBadan) < 0) {
                    e.preventDefault();
                    Swal.fire('Gagal', 'Tinggi badan tidak boleh kurang dari 0.', 'error');
                    return;
                }

                if (beratBadan !== '' && Number(beratBadan) < 0) {
                    e.preventDefault();
                    Swal.fire('Gagal', 'Berat badan tidak boleh kurang dari 0.', 'error');
                    return;
                }

                let phonePattern = /^[0-9+\-\s]*$/;

                if (noHpSiswa !== '' && !phonePattern.test(noHpSiswa)) {
                    e.preventDefault();
                    Swal.fire('Gagal',
                        'No HP siswa hanya boleh berisi angka, spasi, tanda plus, atau strip.', 'error');
                    return;
                }

                if (noHpOrangTua !== '' && !phonePattern.test(noHpOrangTua)) {
                    e.preventDefault();
                    Swal.fire('Gagal',
                        'No HP orang tua hanya boleh berisi angka, spasi, tanda plus, atau strip.',
                        'error');
                    return;
                }

                Swal.fire({
                    title: 'Menyimpan identitas...',
                    text: 'Mohon tunggu, data sedang diperbarui.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            });
        });
    </script>
@stop
