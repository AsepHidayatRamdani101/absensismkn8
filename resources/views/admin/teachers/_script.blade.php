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
                $('#edit_jabatan').val(data.jabatan ?? 'guru');
                $('#edit_jenis_kelamin').val(data.jenis_kelamin);
                $('#edit_no_hp').val(data.no_hp);
                $('#edit_alamat').val(data.alamat);
                $('#edit_wali_classroom_id').val(data.wali_classroom_id ?? '');
                $('#edit_is_kurikulum').prop('checked', !!data.is_kurikulum);
                $('#edit_is_bk').prop('checked', !!data.is_bk);
                $('#edit_is_kesiswaan').prop('checked', !!data.is_kesiswaan);

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
                    jabatan: $('#edit_jabatan').val(),
                    jenis_kelamin: $('#edit_jenis_kelamin').val(),
                    no_hp: $('#edit_no_hp').val(),
                    alamat: $('#edit_alamat').val(),
                    wali_classroom_id: $('#edit_wali_classroom_id').val(),
                    is_kurikulum: $('#edit_is_kurikulum').is(':checked') ? 1 : 0,
                    is_bk: $('#edit_is_bk').is(':checked') ? 1 : 0,
                    is_kesiswaan: $('#edit_is_kesiswaan').is(':checked') ? 1 : 0,

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

        // DELETE MULTIPLE
        function updateTeacherBulkBtn() {
            let count = $('.check-teacher:checked').length;
            $('#selectedCountTeachers').text(count);
            $('#btnDeleteMultipleTeachers').toggleClass('d-none', count === 0);
        }

        $('#checkAllTeachers').on('change', function() {
            $('.check-teacher').prop('checked', this.checked);
            updateTeacherBulkBtn();
        });

        $(document).on('change', '.check-teacher', function() {
            if (!this.checked) $('#checkAllTeachers').prop('checked', false);
            updateTeacherBulkBtn();
        });

        $('#btnDeleteMultipleTeachers').on('click', function() {
            let ids = $('.check-teacher:checked').map(function() {
                return this.value;
            }).get();
            Swal.fire({
                title: 'Hapus ' + ids.length + ' data guru?',
                text: 'Data tidak bisa dikembalikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('teachers.destroy-multiple') }}",
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
                            showAjaxError(xhr, 'Data guru gagal dihapus.');
                        }
                    });
                }
            });
        });

    });
</script>
