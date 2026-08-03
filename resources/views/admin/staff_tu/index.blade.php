@extends('adminlte::page')

@section('title', 'Master Data TU')

@section('plugins.Datatables', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Master Data Tata Usaha (TU)</h1>
        <div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreateTu">
                <i class="fas fa-plus"></i> Tambah Staf TU
            </button>
            <button id="btnDeleteMultipleTu" class="btn btn-danger d-none ml-1">
                <i class="fas fa-trash"></i> Hapus Terpilih (<span id="selectedCountTu">0</span>)
            </button>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableStaffTu" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkAllTu"></th>
                            <th width="5%">No</th>
                            <th>NIP</th>
                            <th>Nama Lengkap</th>
                            <th>Jabatan</th>
                            <th>JK</th>
                            <th>No HP</th>
                            <th width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staffList as $staff)
                            <tr>
                                <td><input type="checkbox" class="check-tu" value="{{ $staff->id }}"></td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $staff->nip ?? '-' }}</td>
                                <td>{{ $staff->nama_lengkap }}</td>
                                <td>
                                    @if ($staff->jabatan === 'kepala_tu')
                                        <span class="badge badge-primary">Kepala TU</span>
                                    @else
                                        <span class="badge badge-secondary">Staf TU</span>
                                    @endif
                                </td>
                                <td>{{ $staff->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                <td>{{ $staff->no_hp ?? '-' }}</td>
                                <td>
                                    <button class="btn btn-warning btn-xs btn-edit-tu" data-id="{{ $staff->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-xs btn-delete-tu" data-id="{{ $staff->id }}">
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

    {{-- Modal Create --}}
    <div class="modal fade" id="modalCreateTu">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formCreateTu">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Staf TU</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" name="nip" class="form-control" maxlength="30"
                                placeholder="Kosongkan jika tidak ada">
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Jabatan <span class="text-danger">*</span></label>
                                <select name="jabatan" class="form-control" required>
                                    <option value="staf_tu">Staf TU</option>
                                    <option value="kepala_tu">Kepala TU</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="jenis_kelamin" class="form-control" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>No HP</label>
                            <input type="text" name="no_hp" class="form-control" maxlength="20">
                        </div>
                        <div class="form-group mb-0">
                            <label>Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="modalEditTu">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEditTu">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_tu_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Staf TU</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" name="nip" id="edit_tu_nip" class="form-control" maxlength="30">
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" id="edit_tu_nama" class="form-control" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Jabatan <span class="text-danger">*</span></label>
                                <select name="jabatan" id="edit_tu_jabatan" class="form-control" required>
                                    <option value="staf_tu">Staf TU</option>
                                    <option value="kepala_tu">Kepala TU</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="jenis_kelamin" id="edit_tu_jk" class="form-control" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>No HP</label>
                            <input type="text" name="no_hp" id="edit_tu_nohp" class="form-control" maxlength="20">
                        </div>
                        <div class="form-group mb-0">
                            <label>Alamat</label>
                            <textarea name="alamat" id="edit_tu_alamat" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update</button>
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
            const table = $('#tableStaffTu').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                },
                columnDefs: [{
                    orderable: false,
                    targets: [0, 7]
                }],
            });

            // Check-all
            $('#checkAllTu').on('change', function() {
                $('.check-tu').prop('checked', $(this).is(':checked'));
                updateDeleteBtn();
            });

            $(document).on('change', '.check-tu', updateDeleteBtn);

            function updateDeleteBtn() {
                const count = $('.check-tu:checked').length;
                $('#btnDeleteMultipleTu').toggleClass('d-none', count === 0);
                $('#selectedCountTu').text(count);
            }

            // Create
            $('#formCreateTu').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: '{{ route('staff-tu.store') }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    success(res) {
                        if (res.success) {
                            Swal.fire('Berhasil', res.message, 'success').then(() => location
                                .reload());
                        }
                    },
                    error(xhr) {
                        const errors = xhr.responseJSON?.errors;
                        const msg = errors ? Object.values(errors).flat().join('\n') :
                            'Terjadi kesalahan.';
                        Swal.fire('Gagal', msg, 'error');
                    }
                });
            });

            // Edit
            $(document).on('click', '.btn-edit-tu', function() {
                const id = $(this).data('id');
                $.get(`/staff-tu/${id}/edit`, function(data) {
                    $('#edit_tu_id').val(data.id);
                    $('#edit_tu_nip').val(data.nip ?? '');
                    $('#edit_tu_nama').val(data.nama_lengkap);
                    $('#edit_tu_jabatan').val(data.jabatan);
                    $('#edit_tu_jk').val(data.jenis_kelamin);
                    $('#edit_tu_nohp').val(data.no_hp ?? '');
                    $('#edit_tu_alamat').val(data.alamat ?? '');
                    $('#modalEditTu').modal('show');
                });
            });

            $('#formEditTu').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_tu_id').val();
                $.ajax({
                    url: `/staff-tu/${id}`,
                    method: 'POST',
                    data: $(this).serialize(),
                    success(res) {
                        if (res.success) {
                            Swal.fire('Berhasil', res.message, 'success').then(() => location
                                .reload());
                        }
                    },
                    error(xhr) {
                        const errors = xhr.responseJSON?.errors;
                        const msg = errors ? Object.values(errors).flat().join('\n') :
                            'Terjadi kesalahan.';
                        Swal.fire('Gagal', msg, 'error');
                    }
                });
            });

            // Delete single
            $(document).on('click', '.btn-delete-tu', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Hapus staf TU?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/staff-tu/${id}`,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success(res) {
                                if (res.success) {
                                    Swal.fire('Berhasil', res.message, 'success').then(() =>
                                        location.reload());
                                }
                            }
                        });
                    }
                });
            });

            // Delete multiple
            $('#btnDeleteMultipleTu').on('click', function() {
                const ids = $('.check-tu:checked').map((_, el) => $(el).val()).get();
                Swal.fire({
                    title: `Hapus ${ids.length} staf TU?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('staff-tu.destroy-multiple') }}',
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}',
                                ids
                            },
                            success(res) {
                                if (res.success) {
                                    Swal.fire('Berhasil', res.message, 'success').then(() =>
                                        location.reload());
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@stop
