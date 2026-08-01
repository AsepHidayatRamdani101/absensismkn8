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
            <a href="{{ route('students.export-accounts-pdf', request()->query()) }}" class="btn btn-danger mr-1">
                <i class="fas fa-file-pdf"></i>
                Export PDF Akun
            </a>
            <form id="formImportStudents" action="{{ route('students.import') }}" method="POST"
                enctype="multipart/form-data" class="d-inline-block mr-1">
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

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->has('file'))
                <div class="alert alert-danger">{{ $errors->first('file') }}</div>
            @endif

            <div class="alert alert-info mb-3">
                Import siswa mendukung file ABSENSI (blok <strong>NO / NIS / NISN / NAMA SISWA</strong>)
                dan otomatis menyesuaikan kelas sesuai data yang ada di database.
            </div>

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
                @if (!$hasFilter)
                    <div class="alert alert-info">
                        <i class="fas fa-filter mr-1"></i>
                        Terapkan filter jurusan atau kelas terlebih dahulu untuk menampilkan data siswa.
                    </div>
                @else
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
                @endif
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
                const $form = $('#formGenerateAccountsStudents');
                const url = $form.attr('action');
                const token = $form.find('input[name="_token"]').val();
                const batchSize = 300;

                Swal.fire({
                    title: 'Generate akun siswa?',
                    text: 'Username dari NISN (fallback NIS) dan password default siswa12345.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, generate',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let afterId = 0;
                        let totalProcessed = 0;
                        let totalCreated = 0;
                        let totalUpdated = 0;
                        let totalSkipped = 0;

                        Swal.fire({
                            title: 'Memproses generate akun...',
                            html: 'Memulai proses batch akun siswa...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        const processNextBatch = () => {
                            $.ajax({
                                url: url,
                                method: 'POST',
                                data: {
                                    _token: token,
                                    after_id: afterId,
                                    batch_size: batchSize,
                                },
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                success: function(resp) {
                                    totalProcessed += Number(resp.processed || 0);
                                    totalCreated += Number(resp.created || 0);
                                    totalUpdated += Number(resp.updated || 0);
                                    totalSkipped += Number(resp.skipped || 0);

                                    const total = Number(resp.total || 0);
                                    const percent = total > 0 ? Math.min(100, Math
                                        .round((totalProcessed / total) * 100)
                                    ) : 100;

                                    Swal.update({
                                        html: `
                                            <div class="text-left">
                                                <p class="mb-1">Progress: <strong>${totalProcessed}</strong> / <strong>${total}</strong> (${percent}%)</p>
                                                <p class="mb-1">Dibuat: <strong>${totalCreated}</strong></p>
                                                <p class="mb-1">Diperbarui: <strong>${totalUpdated}</strong></p>
                                                <p class="mb-0">Dilewati: <strong>${totalSkipped}</strong></p>
                                            </div>
                                        `,
                                    });

                                    if (resp.done) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Generate akun siswa selesai',
                                            html: `
                                                <div class="text-left">
                                                    <p class="mb-1">Total diproses: <strong>${totalProcessed}</strong></p>
                                                    <p class="mb-1">Dibuat: <strong>${totalCreated}</strong></p>
                                                    <p class="mb-1">Diperbarui: <strong>${totalUpdated}</strong></p>
                                                    <p class="mb-0">Dilewati: <strong>${totalSkipped}</strong></p>
                                                </div>
                                            `,
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                        return;
                                    }

                                    afterId = Number(resp.next_after_id || afterId);
                                    setTimeout(processNextBatch, 20);
                                },
                                error: function(xhr) {
                                    const err = (xhr.responseJSON && xhr
                                            .responseJSON.message) ?
                                        xhr.responseJSON.message :
                                        'Terjadi kesalahan saat generate akun siswa.';

                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Generate gagal',
                                        text: err,
                                    });
                                }
                            });
                        };

                        processNextBatch();
                    }
                });
            });
        });
    </script>
    @include('admin.students._script')

@stop
