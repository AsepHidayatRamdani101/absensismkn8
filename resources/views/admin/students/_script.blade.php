<script>
    $(function() {

        //------------------------------------
        // DATATABLE
        //------------------------------------

        if ($('#tableStudents').length) {
            $('#tableStudents').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('students.datatable') }}",
                    data: function(d) {
                        d.major_id = $('#filterMajor').val();
                        d.classroom_id = $('#filterClassroom').val();
                    }
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'no',
                        name: 'no',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'nisn',
                        name: 'nisn'
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'jk',
                        name: 'jk'
                    },
                    {
                        data: 'kelas',
                        name: 'kelas',
                        orderable: false
                    },
                    {
                        data: 'jabatan',
                        name: 'jabatan'
                    },
                    {
                        data: 'no_hp',
                        name: 'no_hp'
                    },
                    {
                        data: 'qr',
                        name: 'qr',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    }
                ],
                columnDefs: [{
                    targets: [0, 4, 9, 10],
                    render: function(data) {
                        return data;
                    }
                }],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }

            });
        }

        //------------------------------------
        // CREATE
        //------------------------------------

        $('#formCreate').submit(function(e) {

            e.preventDefault();

            $.ajax({

                url: "{{ route('students.store') }}",

                type: "POST",

                data: $(this).serialize(),

                success: function(res) {

                    $('#modalCreate').modal('hide');

                    Swal.fire(
                        'Berhasil',
                        res.message,
                        'success'
                    ).then(() => {

                        location.reload();

                    });

                },

                error: function(xhr) {

                    Swal.fire(
                        'Gagal',
                        xhr.responseJSON.message,
                        'error'
                    );

                }

            });

        });


        //------------------------------------
        // EDIT
        //------------------------------------

        $(document).on('click', '.btn-edit', function() {

            let id = $(this).data('id');

            $.get('/students/' + id + '/edit',

                function(data) {

                    $('#edit_id').val(data.id);

                    $('#edit_nis').val(data.nis);

                    $('#edit_nisn').val(data.nisn);

                    $('#edit_nama_lengkap')
                        .val(data.nama_lengkap);

                    $('#edit_jenis_kelamin')
                        .val(data.jenis_kelamin);

                    $('#edit_classroom_id')
                        .val(data.classroom_id);

                    $('#edit_jabatan_kelas')
                        .val(data.jabatan_kelas ?? '');

                    $('#edit_no_hp')
                        .val(data.no_hp);

                    $('#edit_alamat')
                        .val(data.alamat);

                    $('#modalEdit').modal('show');

                }

            );

        });


        //------------------------------------
        // UPDATE
        //------------------------------------

        $('#formEdit').submit(function(e) {

            e.preventDefault();

            let id = $('#edit_id').val();

            $.ajax({

                url: '/students/' + id,

                type: 'POST',

                data: {

                    _token: '{{ csrf_token() }}',

                    _method: 'PUT',

                    nis: $('#edit_nis').val(),

                    nisn: $('#edit_nisn').val(),

                    nama_lengkap: $('#edit_nama_lengkap').val(),

                    jenis_kelamin: $('#edit_jenis_kelamin').val(),

                    classroom_id: $('#edit_classroom_id').val(),

                    jabatan_kelas: $('#edit_jabatan_kelas').val(),

                    no_hp: $('#edit_no_hp').val(),

                    alamat: $('#edit_alamat').val(),

                },

                success: function(res) {

                    $('#modalEdit').modal('hide');

                    Swal.fire(
                        'Berhasil',
                        res.message,
                        'success'
                    ).then(() => {

                        location.reload();

                    });

                }

            });

        });


        //------------------------------------
        // DELETE
        //------------------------------------

        $(document).on('click', '.btn-delete', function() {

            let id = $(this).data('id');

            Swal.fire({

                title: 'Hapus data?',

                text: 'Data tidak bisa dikembalikan',

                icon: 'warning',

                showCancelButton: true,

                confirmButtonText: 'Ya, hapus',

                cancelButtonText: 'Batal'

            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({

                        url: '/students/' + id,

                        type: 'POST',

                        data: {

                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'

                        },

                        success: function(res) {

                            Swal.fire({

                                icon: 'success',

                                title: 'Berhasil',

                                text: res.message

                            }).then(() => {

                                location.reload();

                            });

                        }

                    });

                }

            });

        });

        //------------------------------------
        // DELETE MULTIPLE
        //------------------------------------

        function updateStudentBulkBtn() {
            let count = $('.check-student:checked').length;
            $('#selectedCountStudents').text(count);
            $('#btnDeleteMultipleStudents').toggleClass('d-none', count === 0);
        }

        $('#checkAllStudents').on('change', function() {
            $('.check-student').prop('checked', this.checked);
            updateStudentBulkBtn();
        });

        $(document).on('change', '.check-student', function() {
            if (!this.checked) $('#checkAllStudents').prop('checked', false);
            updateStudentBulkBtn();
        });

        $('#btnDeleteMultipleStudents').on('click', function() {
            let ids = $('.check-student:checked').map(function() {
                return this.value;
            }).get();
            Swal.fire({
                title: 'Hapus ' + ids.length + ' data siswa?',
                text: 'Data tidak bisa dikembalikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('students.destroy-multiple') }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE',
                            ids: ids,
                        },
                        success: function(res) {
                            Swal.fire('Berhasil', res.message, 'success').then(() =>
                                location.reload());
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal', xhr.responseJSON?.message ??
                                'Terjadi kesalahan', 'error');
                        }
                    });
                }
            });
        });

    });
</script>
