<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        $('#tableMajors').DataTable({

            responsive: true,

            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }

        });


        //----------------------------------
        // CREATE
        //----------------------------------

        $('#formCreate').submit(function(e) {

            e.preventDefault();

            $.ajax({

                url: "{{ route('majors.store') }}",

                type: "POST",

                data: $(this).serialize(),

                success: function(res) {

                    $('#modalCreate').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message
                    }).then(() => {

                        location.reload();

                    });

                },

                error: function(xhr) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON.message
                    });

                }

            });

        });


        //----------------------------------
        // EDIT
        //----------------------------------

        $('.btn-edit').click(function() {

            let id = $(this).data('id');

            $.get('/majors/' + id + '/edit', function(data) {

                $('#edit_id').val(data.id);

                $('#edit_kode_jurusan')
                    .val(data.kode_jurusan);

                $('#edit_nama_jurusan')
                    .val(data.nama_jurusan);

                $('#edit_singkatan')
                    .val(data.singkatan);

                $('#modalEdit').modal('show');

            });

        });


        //----------------------------------
        // UPDATE
        //----------------------------------

        $('#formEdit').submit(function(e) {

            e.preventDefault();

            let id = $('#edit_id').val();

            $.ajax({

                url: '/majors/' + id,

                type: 'POST',

                data: {

                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',

                    kode_jurusan: $('#edit_kode_jurusan').val(),

                    nama_jurusan: $('#edit_nama_jurusan').val(),

                    singkatan: $('#edit_singkatan').val()

                },

                success: function(res) {

                    $('#modalEdit').modal('hide');

                    Swal.fire({

                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message

                    }).then(() => {

                        location.reload();

                    });

                }

            });

        });


        //----------------------------------
        // DELETE
        //----------------------------------

        $('.btn-delete').click(function() {

            let id = $(this).data('id');

            Swal.fire({

                title: 'Hapus data?',

                text: 'Data tidak dapat dikembalikan',

                icon: 'warning',

                showCancelButton: true,

                confirmButtonText: 'Ya, hapus',

                cancelButtonText: 'Batal'

            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({

                        url: '/majors/' + id,

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

                        },

                        error: function(xhr) {

                            Swal.fire({

                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON.message

                            });

                        }

                    });

                }

            });

        });

    });
</script>
