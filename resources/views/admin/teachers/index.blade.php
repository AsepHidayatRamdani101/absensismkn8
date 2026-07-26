@extends('adminlte::page')

@section('title', 'Data Guru')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('content_header')

    <div class="d-flex justify-content-between">

        <h1>Master Data Guru</h1>

        <div>
            <a href="{{ route('teachers.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('teachers.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportTeachers" action="{{ route('teachers.import') }}" method="POST" enctype="multipart/form-data"
                class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportTeachers" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportTeachers" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>

            <form id="formGenerateAccountsTeachers" action="{{ route('teachers.generate-accounts') }}" method="POST"
                class="d-inline-block mr-1">
                @csrf
                <button type="button" id="btnGenerateAccountsTeachers" class="btn btn-info">
                    <i class="fas fa-user-cog"></i>
                    Generate Akun Guru
                </button>
            </form>

            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Guru
            </button>

            <button id="btnDeleteMultipleTeachers" class="btn btn-danger d-none">
                <i class="fas fa-trash"></i>
                Hapus Terpilih (<span id="selectedCountTeachers">0</span>)
            </button>
        </div>

    </div>

@stop


@section('content')

    <div class="card">

        <div class="card-body">

            <form method="GET" action="{{ route('teachers.index') }}" class="mb-3">
                <div class="row align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label for="kurikulum_filter" class="mb-1">Filter Kurikulum</label>
                        <select name="kurikulum_filter" id="kurikulum_filter" class="form-control">
                            <option value="" {{ $kurikulumFilter === '' ? 'selected' : '' }}>Semua Guru</option>
                            <option value="kurikulum" {{ $kurikulumFilter === 'kurikulum' ? 'selected' : '' }}>Akun
                                Kurikulum</option>
                            <option value="non_kurikulum" {{ $kurikulumFilter === 'non_kurikulum' ? 'selected' : '' }}>Bukan
                                Akun Kurikulum</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-3 mt-3 mt-md-0">
                        <button type="submit" class="btn btn-primary mr-2">Terapkan</button>
                        <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->has('file'))
                <div class="alert alert-danger">{{ $errors->first('file') }}</div>
            @endif

            <div class="table-responsive">
                <table id="tableTeachers" class="table table-bordered table-striped">

                    <thead>

                        <tr>
                            <th width="3%"><input type="checkbox" id="checkAllTeachers"></th>
                            <th width="5%">No</th>
                            <th>NIP</th>

                            <th>Nama Guru</th>
                            <th>Status</th>
                            <th>Jabatan</th>
                            <th>Kelas Wali</th>

                            <th>JK</th>
                            <th>No HP</th>
                            <th width="15%">Aksi</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($teachers as $teacher)
                            <tr>

                                <td><input type="checkbox" class="check-teacher" value="{{ $teacher->id }}"></td>

                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $teacher->nip }}</td>



                                <td>{{ $teacher->nama_lengkap }}</td>

                                <td>
                                    @if ($teacher->has_account)
                                        <span class="badge badge-success">Sudah</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>

                                <td>{{ $teacher->jabatan_label }}</td>

                                <td>{{ $teacher->waliClassroom->nama_kelas ?? '-' }}</td>



                                <td>{{ $teacher->jenis_kelamin }}</td>

                                <td>{{ $teacher->no_hp }}</td>

                                <td>

                                    <button class="btn btn-warning btn-xs btn-edit" data-id="{{ $teacher->id }}">

                                        <i class="fas fa-edit"></i>

                                    </button>

                                    <button class="btn btn-danger btn-xs btn-delete" data-id="{{ $teacher->id }}">

                                        <i class="fas fa-trash"></i>

                                    </button>

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>
            </div>

        </div>

    </div>

    @include('admin.teachers.modal-create')
    @include('admin.teachers.modal-edit')

@stop


@section('footer')
    @include('components.app-footer')
@stop


@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            $('#btnImportTeachers').on('click', function() {
                $('#fileImportTeachers').trigger('click');
            });

            $('#fileImportTeachers').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportTeachers').submit();
                }
            });

            $('#btnGenerateAccountsTeachers').on('click', function() {
                Swal.fire({
                    title: 'Generate akun guru?',
                    text: 'Username dari NIP (fallback NUPTK) dan password default guru12345.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, generate',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memproses generate akun...',
                            text: 'Mohon tunggu, akun guru sedang dibuat.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        $('#formGenerateAccountsTeachers').submit();
                    }
                });
            });
        });
    </script>
    @include('admin.teachers._script')
@stop
