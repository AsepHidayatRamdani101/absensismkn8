<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        let tableSchedules = $('#tableSchedules').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'tableSchedules') {
                return true;
            }

            let tingkatFilter = $('#filterTingkat').val();
            let jurusanFilter = $('#filterJurusan').val();
            let kelasFilter = $('#filterKelas').val();

            let rowNode = tableSchedules.row(dataIndex).node();

            if (!rowNode) {
                return true;
            }

            let rowTingkat = $(rowNode).data('tingkat') ? String($(rowNode).data('tingkat')) : '';
            let rowJurusan = $(rowNode).data('jurusan') ? String($(rowNode).data('jurusan')) : '';
            let rowKelas = $(rowNode).data('kelas') ? String($(rowNode).data('kelas')) : '';

            if (tingkatFilter && rowTingkat !== tingkatFilter) {
                return false;
            }

            if (jurusanFilter && rowJurusan !== jurusanFilter) {
                return false;
            }

            if (kelasFilter && rowKelas !== kelasFilter) {
                return false;
            }

            return true;
        });

        let classFilterOptions = $('#filterKelas option').not(':first').map(function() {
            return {
                value: $(this).attr('value') || '',
                text: $(this).text(),
                tingkat: $(this).data('tingkat') ? String($(this).data('tingkat')) : '',
                jurusan: $(this).data('jurusan') ? String($(this).data('jurusan')) : ''
            };
        }).get();

        function syncKelasFilterOptions() {
            let tingkatFilter = $('#filterTingkat').val();
            let jurusanFilter = $('#filterJurusan').val();
            let selectedKelas = $('#filterKelas').val();

            let availableClassOptions = classFilterOptions.filter(function(option) {
                if (tingkatFilter && option.tingkat !== tingkatFilter) {
                    return false;
                }

                if (jurusanFilter && option.jurusan !== jurusanFilter) {
                    return false;
                }

                return true;
            });

            $('#filterKelas').find('option:not(:first)').remove();

            $.each(availableClassOptions, function(_, option) {
                $('#filterKelas').append(
                    $('<option>', {
                        value: option.value,
                        text: option.text,
                        'data-tingkat': option.tingkat,
                        'data-jurusan': option.jurusan,
                    })
                );
            });

            let isSelectedKelasStillAvailable = availableClassOptions.some(function(option) {
                return option.value === selectedKelas;
            });

            if (isSelectedKelasStillAvailable) {
                $('#filterKelas').val(selectedKelas);
            } else {
                $('#filterKelas').val('');
            }
        }

        syncKelasFilterOptions();

        $('#filterTingkat, #filterJurusan').on('change', function() {
            syncKelasFilterOptions();
            tableSchedules.draw();
        });

        $('#filterKelas').on('change', function() {
            tableSchedules.draw();
        });


        //----------------------------------
        // CREATE
        //----------------------------------

        $('#formCreate').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('schedules.store') }}",
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

            $.get('/schedules/' + id + '/edit', function(data) {
                $('#edit_id').val(data.id);
                $('#edit_teacher_subject_id').val(data.teacher_subject_id);
                $('#edit_hari').val(data.hari);
                $('#edit_jam_mulai').val((data.jam_mulai || '').substring(0, 5));
                $('#edit_jam_selesai').val((data.jam_selesai || '').substring(0, 5));
                $('#edit_ruangan').val(data.ruangan);

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
                url: '/schedules/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    teacher_subject_id: $('#edit_teacher_subject_id').val(),
                    hari: $('#edit_hari').val(),
                    jam_mulai: $('#edit_jam_mulai').val(),
                    jam_selesai: $('#edit_jam_selesai').val(),
                    ruangan: $('#edit_ruangan').val(),
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
                        text: xhr.responseJSON.message
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
                        url: '/schedules/' + id,
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
