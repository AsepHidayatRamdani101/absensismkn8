<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        $('#tableHolidays').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        function syncHolidayDateRange(startSelector, endSelector) {
            const start = $(startSelector).val();
            $(endSelector).attr('min', start || '');

            if (start && $(endSelector).val() && $(endSelector).val() < start) {
                $(endSelector).val(start);
            }
        }

        $('#formCreate [name="tanggal"]').on('change', function() {
            syncHolidayDateRange('#formCreate [name="tanggal"]', '#formCreate [name="tanggal_akhir"]');
        });
        $('#edit_tanggal').on('change', function() {
            syncHolidayDateRange('#edit_tanggal', '#edit_tanggal_akhir');
        });

        function syncHolidayType(pjjSelector, nationalSelector) {
            const isPjj = $(pjjSelector).is(':checked');
            if (isPjj) {
                $(nationalSelector).prop('checked', false);
            }
            $(nationalSelector).prop('disabled', isPjj);
        }

        $('#create_is_pjj').on('change', function() {
            syncHolidayType('#create_is_pjj', '#create_is_national');
        });
        $('#edit_is_pjj').on('change', function() {
            syncHolidayType('#edit_is_pjj', '#edit_is_national');
        });


        //----------------------------------
        // CREATE
        //----------------------------------

        $('#formCreate').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('holidays.store') }}",
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
                        text: xhr.responseJSON.message || 'Periksa kembali rentang tanggal dan data yang diisi.'
                    });
                }
            });
        });


        //----------------------------------
        // EDIT
        //----------------------------------

        $('.btn-edit').click(function() {
            let id = $(this).data('id');

            $.get('/holidays/' + id + '/edit', function(data) {
                $('#edit_id').val(data.id);
                $('#edit_tanggal').val(String(data.tanggal).substring(0, 10));
                $('#edit_tanggal_akhir').val(String(data.tanggal_akhir || data.tanggal).substring(0, 10));
                $('#edit_jam_mulai_libur').val(String(data.jam_mulai_libur || '00:00').substring(0, 5));
                $('#edit_keterangan').val(data.keterangan);
                $('#edit_is_national').prop('checked', !!data.is_national);
                $('#edit_is_pjj').prop('checked', !!data.is_pjj);
                syncHolidayType('#edit_is_pjj', '#edit_is_national');

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
                url: '/holidays/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    tanggal: $('#edit_tanggal').val(),
                    tanggal_akhir: $('#edit_tanggal_akhir').val(),
                    jam_mulai_libur: $('#edit_jam_mulai_libur').val(),
                    keterangan: $('#edit_keterangan').val(),
                    is_national: $('#edit_is_national').is(':checked') ? 1 : 0,
                    is_pjj: $('#edit_is_pjj').is(':checked') ? 1 : 0,
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
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON.message || 'Periksa kembali rentang tanggal dan data yang diisi.'
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
                        url: '/holidays/' + id,
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
                                text: xhr.responseJSON.message || 'Data hari libur gagal dihapus.'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
