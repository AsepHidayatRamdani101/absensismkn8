<script>
    $(function() {

        //------------------------------------
        // DATATABLE
        //------------------------------------

        $('#tableClassrooms').DataTable({

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

                url: "{{ route('classrooms.store') }}",

                type: "POST",

                data: $(this).serialize(),

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
                        text: 'Periksa kembali data.'
                    });

                }

            });

        });


        //------------------------------------
        // EDIT
        //------------------------------------

        $('.btn-edit').click(function() {

            let id = $(this).data('id');

            $.get('/classrooms/' + id + '/edit', function(data) {

                $('#edit_id').val(data.id);

                $('#edit_kode_kelas').val(data.kode_kelas);

                $('#edit_nama_kelas').val(data.nama_kelas);

                $('#edit_tingkat').val(data.tingkat);

                $('#edit_jurusan').val(data.jurusan);

                $('#edit_rombel').val(data.rombel);

                $('#modalEdit').modal('show');

            });

        });


        //------------------------------------
        // UPDATE
        //------------------------------------

        $('#formEdit').submit(function(e) {

            e.preventDefault();

            let id = $('#edit_id').val();

            $.ajax({

                url: '/classrooms/' + id,

                type: 'POST',

                data: {

                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',

                    kode_kelas: $('#edit_kode').val(),
                    nama_kelas: $('#edit_nama').val(),
                    tingkat: $('#edit_tingkat').val(),
                    jurusan: $('#edit_jurusan').val(),
                    rombel: $('#edit_rombel').val(),

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

                        url: '/classrooms/' + id,

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
