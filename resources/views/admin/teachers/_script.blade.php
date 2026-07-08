<script>
    $(function() {

        $('#tableTeachers').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        // CREATE
        $('#formCreate').submit(function(e) {

            e.preventDefault();

            $.post(
                "{{ route('teachers.store') }}",
                $(this).serialize(),
                function(res) {

                    $('#modalCreate').modal('hide');

                    Swal.fire(
                        'Berhasil',
                        res.message,
                        'success'
                    ).then(() => location.reload());

                }
            );

        });

        // EDIT
        $('.btn-edit').click(function() {

            let id = $(this).data('id');

            $.get('/teachers/' + id + '/edit', function(data) {

                $('#edit_id').val(data.id);
                $('#edit_nip').val(data.nip);
                $('#edit_nuptk').val(data.nuptk);
                $('#edit_nama_lengkap').val(data.nama_lengkap);
                $('#edit_jenis_kelamin').val(data.jenis_kelamin);
                $('#edit_no_hp').val(data.no_hp);
                $('#edit_alamat').val(data.alamat);

                $('#modalEdit').modal('show');

            });

        });

        // UPDATE
        $('#formEdit').submit(function(e) {

            e.preventDefault();

            let id = $('#edit_id').val();

            $.ajax({

                url: '/teachers/' + id,

                type: 'POST',

                data: {

                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',

                    nip: $('#edit_nip').val(),
                    nuptk: $('#edit_nuptk').val(),
                    nama_lengkap: $('#edit_nama_lengkap').val(),
                    jenis_kelamin: $('#edit_jenis_kelamin').val(),
                    no_hp: $('#edit_no_hp').val(),
                    alamat: $('#edit_alamat').val(),

                },

                success: function(res) {

                    $('#modalEdit').modal('hide');

                    Swal.fire(
                        'Berhasil',
                        res.message,
                        'success'
                    ).then(() => location.reload());

                }

            });

        });

        // DELETE
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

                        url: '/teachers/' + id,

                        type: 'POST',

                        data: {

                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'

                        },

                        success: function(res) {

                            Swal.fire(
                                'Berhasil',
                                res.message,
                                'success'
                            ).then(() => location.reload());

                        }

                    });

                }

            });

        });

    });
</script>
