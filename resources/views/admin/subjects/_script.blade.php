<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        $('#tableSubjects').DataTable({
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
                url: "{{ route('subjects.store') }}",
                type: 'POST',
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

            $.get('/subjects/' + id + '/edit', function(data) {
                $('#edit_id').val(data.id);
                $('#edit_kode_mapel').val(data.kode_mapel);
                $('#edit_nama_mapel').val(data.nama_mapel);
                $('#edit_kategori').val(data.kategori);
                $('#edit_jam_per_minggu').val(data.jam_per_minggu);

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
                url: '/subjects/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    kode_mapel: $('#edit_kode_mapel').val(),
                    nama_mapel: $('#edit_nama_mapel').val(),
                    kategori: $('#edit_kategori').val(),
                    jam_per_minggu: $('#edit_jam_per_minggu').val()
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
                        url: '/subjects/' + id,
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
        //----------------------------------
        // DELETE MULTIPLE
        //----------------------------------

        function updateSubjectBulkBtn() {
            let count = $('.check-subject:checked').length;
            $('#selectedCountSubjects').text(count);
            $('#btnDeleteMultipleSubjects').toggleClass('d-none', count === 0);
        }

        $('#checkAllSubjects').on('change', function() {
            $('.check-subject').prop('checked', this.checked);
            updateSubjectBulkBtn();
        });

        $(document).on('change', '.check-subject', function() {
            if (!this.checked) $('#checkAllSubjects').prop('checked', false);
            updateSubjectBulkBtn();
        });

        $('#btnDeleteMultipleSubjects').on('click', function() {
            let ids = $('.check-subject:checked').map(function() {
                return this.value;
            }).get();
            Swal.fire({
                title: 'Hapus ' + ids.length + ' mata pelajaran?',
                text: 'Data tidak dapat dikembalikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('subjects.destroy-multiple') }}",
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
