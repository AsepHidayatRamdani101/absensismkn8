<script>
    $(function() {

        //------------------------------------
        // DATATABLE
        //------------------------------------

        $('#tableStudents').DataTable({

            responsive: true,

            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }

        });


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

        $('.btn-edit').click(function() {

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

        $('.btn-delete').click(function() {

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

    });
</script>
