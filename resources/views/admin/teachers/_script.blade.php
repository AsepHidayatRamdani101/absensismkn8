<script>
    $(function() {
        const teacherUrlTemplate = @json(route('teachers.update', ['teacher' => '__ID__']));
        const teacherEditUrlTemplate = @json(route('teachers.edit', ['teacher' => '__ID__']));

        const buildTeacherUrl = (id, isEdit = false) => {
            const template = isEdit ? teacherEditUrlTemplate : teacherUrlTemplate;
            return template.replace('__ID__', id);
        };

        const showAjaxError = (xhr, fallbackMessage) => {
            const message = xhr.responseJSON?.message ?? fallbackMessage;

            Swal.fire('Gagal', message, 'error');
        };

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
        $(document).on('click', '.btn-edit', function() {

            let id = $(this).data('id');

            $.get(buildTeacherUrl(id, true), function(data) {

                $('#edit_id').val(data.id);
                $('#edit_nip').val(data.nip);
                $('#edit_nuptk').val(data.nuptk);
                $('#edit_nama_lengkap').val(data.nama_lengkap);
                $('#edit_jenis_kelamin').val(data.jenis_kelamin);
                $('#edit_no_hp').val(data.no_hp);
                $('#edit_alamat').val(data.alamat);

                $('#modalEdit').modal('show');

            }).fail(function(xhr) {
                showAjaxError(xhr, 'Data guru gagal dimuat.');
            });

        });

        // UPDATE
        $('#formEdit').submit(function(e) {

            e.preventDefault();

            let id = $('#edit_id').val();

            $.ajax({

                url: buildTeacherUrl(id),

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

                },

                error: function(xhr) {
                    showAjaxError(xhr, 'Data guru gagal diperbarui.');
                }

            });

        });

        // DELETE
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

                        url: buildTeacherUrl(id),

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
                            showAjaxError(xhr, 'Data guru gagal dihapus.');
                        }

                    });

                }

            });

        });

    });
</script>
