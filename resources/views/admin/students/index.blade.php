@extends('adminlte::page')

@section('title', 'Data Siswa')

@section('plugins.Datatables', true)

@section('content_header')

    <div class="d-flex justify-content-between">

        <h1>Master Data Siswa</h1>

        <div>
            <a href="{{ route('students.template') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-file-download"></i>
                Download Format
            </a>
            <a href="{{ route('students.export') }}" class="btn btn-success mr-1">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </a>
            <form id="formImportStudents" action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data"
                class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" id="fileImportStudents" class="d-none" accept=".xlsx,.xls,.csv">
                <button type="button" id="btnImportStudents" class="btn btn-warning">
                    <i class="fas fa-file-import"></i>
                    Import
                </button>
            </form>

            <form id="formGenerateAccountsStudents" action="{{ route('students.generate-accounts') }}" method="POST"
                class="d-inline-block mr-1">
                @csrf
                <button type="button" id="btnGenerateAccountsStudents" class="btn btn-info">
                    <i class="fas fa-user-cog"></i>
                    Generate Akun Siswa
                </button>
            </form>

            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="fas fa-plus"></i>
                Tambah Siswa
            </button>

            <button id="btnDeleteMultipleStudents" class="btn btn-danger d-none">
                <i class="fas fa-trash"></i>
                Hapus Terpilih (<span id="selectedCountStudents">0</span>)
            </button>
        </div>

    </div>

@stop

@section('content')

    <div class="card">

        <div class="card-body">

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->has('file'))
                <div class="alert alert-danger">{{ $errors->first('file') }}</div>
            @endif

            {{-- FILTER --}}
            <form method="GET" action="{{ route('students.index') }}" class="mb-3" id="formFilterStudents">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="mb-1">Filter Jurusan</label>
                        <select name="major_id" id="filterMajor" class="form-control form-control-sm">
                            <option value="">Semua Jurusan</option>
                            @foreach ($majors as $major)
                                <option value="{{ $major->id }}" {{ $majorFilter == $major->id ? 'selected' : '' }}>
                                    {{ $major->nama_jurusan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <label class="mb-1">Filter Kelas</label>
                        <select name="classroom_id" id="filterClassroom" class="form-control form-control-sm">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" data-major="{{ $classroom->major_id }}"
                                    {{ $classroomFilter == $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-primary btn-sm mr-1">Terapkan</button>
                        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table id="tableStudents" class="table table-bordered table-striped">

                    <thead>

                        <tr>
                            <th width="3%"><input type="checkbox" id="checkAllStudents"></th>
                            <th width="5%">No</th>
                            <th>NISN</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>JK</th>
                            <th>Kelas</th>
                            <th>Jabatan</th>
                            <th>No HP</th>
                            <th width="15%">Aksi</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($students as $student)
                            <tr>

                                <td><input type="checkbox" class="check-student" value="{{ $student->id }}"></td>

                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $student->nisn }}</td>

                                <td>{{ $student->nama_lengkap }}</td>

                                <td>
                                    @if ($student->has_account)
                                        <span class="badge badge-success">Sudah</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>

                                <td>{{ $student->jenis_kelamin }}</td>

                                <td>

                                    {{ $student->classroom->nama_kelas }}

                                </td>

                                <td>{{ $student->jabatan_kelas_label }}</td>

                                <td>{{ $student->no_hp }}</td>

                                <td>

                                    <button class="btn btn-warning btn-xs btn-edit" data-id="{{ $student->id }}">

                                        <i class="fas fa-edit"></i>

                                    </button>

                                    <button class="btn btn-danger btn-xs btn-delete" data-id="{{ $student->id }}">

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

    @include('admin.students.modal-create')
    @include('admin.students.modal-edit')

@stop


@section('footer')
    @include('components.app-footer')
@stop


@section('js')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            // Filter: cascade kelas by jurusan
            $('#filterMajor').on('change', function() {
                let majorId = $(this).val();
                $('#filterClassroom option').each(function() {
                    if (!$(this).val()) return; // keep "Semua Kelas"
                    $(this).toggle(!majorId || $(this).data('major') == majorId);
                });
                $('#filterClassroom').val('');
            });

            // On load: hide classrooms not matching selected major
            (function() {
                let majorId = $('#filterMajor').val();
                if (!majorId) return;
                $('#filterClassroom option').each(function() {
                    if (!$(this).val()) return;
                    if ($(this).data('major') != majorId) $(this).hide();
                });
            })();

            $('#btnImportStudents').on('click', function() {
                $('#fileImportStudents').trigger('click');
            });

            $('#fileImportStudents').on('change', function() {
                if (this.files.length > 0) {
                    $('#formImportStudents').submit();
                }
            });

            $('#btnGenerateAccountsStudents').on('click', function() {
                Swal.fire({
                    title: 'Generate akun siswa?',
                    text: 'Username dari NISN (fallback NIS) dan password default siswa12345.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, generate',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memproses generate akun...',
                            text: 'Mohon tunggu, akun siswa sedang dibuat.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        $('#formGenerateAccountsStudents').submit();
                    }
                });
            });
        });
    </script>
    @include('admin.students._script')

@stop
