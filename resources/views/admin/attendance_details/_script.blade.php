<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        let tableAttendanceDetails = null;
        if ($('#tableAttendanceDetails').length) {
            tableAttendanceDetails = $('#tableAttendanceDetails').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }

        function updateAttendanceDetailsBulkBtn() {
            let count = $('.check-attendance-detail:checked').length;
            $('#selectedCountAttendanceDetails').text(count);
            $('#btnDeleteMultipleAttendanceDetails').toggleClass('d-none', count === 0);
        }

        $('#checkAllAttendanceDetails').on('change', function() {
            $('.check-attendance-detail').prop('checked', this.checked);
            updateAttendanceDetailsBulkBtn();
        });

        $(document).on('change', '.check-attendance-detail', function() {
            if (!this.checked) {
                $('#checkAllAttendanceDetails').prop('checked', false);
            }

            updateAttendanceDetailsBulkBtn();
        });

        $('#btnDeleteMultipleAttendanceDetails').on('click', function() {
            let ids = $('.check-attendance-detail:checked').map(function() {
                return this.value;
            }).get();

            Swal.fire({
                title: 'Hapus ' + ids.length + ' data absensi?',
                text: 'Data tidak dapat dikembalikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('attendance-details.destroy-multiple') }}",
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

        updateAttendanceDetailsBulkBtn();

        function filterStudentOptions(sessionSelector, studentSelector, selectedStudentId = null) {
            let classroomId = $(sessionSelector).find('option:selected').data('classroom-id');

            $(studentSelector).find('option').each(function() {
                let optionClassroomId = $(this).data('classroom-id');
                let isPlaceholder = !$(this).val();

                if (isPlaceholder) {
                    $(this).prop('hidden', false);
                    return;
                }

                $(this).prop('hidden', classroomId ? optionClassroomId !== classroomId : false);
            });

            if (selectedStudentId) {
                $(studentSelector).val(String(selectedStudentId));
            } else {
                $(studentSelector).val('');
            }
        }

        $('#create_teacher_attendance_id').on('change', function() {
            filterStudentOptions('#create_teacher_attendance_id', '#create_student_id');
        });

        $('#edit_teacher_attendance_id').on('change', function() {
            filterStudentOptions('#edit_teacher_attendance_id', '#edit_student_id');
        });


        //----------------------------------
        // CREATE
        //----------------------------------

        $('#formCreate').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('attendance-details.store') }}",
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

            $.get('/attendance-details/' + id + '/edit', function(data) {
                $('#edit_id').val(data.id);
                $('#edit_teacher_attendance_id').val(data.teacher_attendance_id);
                filterStudentOptions('#edit_teacher_attendance_id', '#edit_student_id', data
                    .student_id);
                $('#edit_status').val(data.status);
                $('#edit_jam_absen').val(data.jam_absen);
                $('#edit_keterangan').val(data.keterangan);

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
                url: '/attendance-details/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    teacher_attendance_id: $('#edit_teacher_attendance_id').val(),
                    student_id: $('#edit_student_id').val(),
                    status: $('#edit_status').val(),
                    jam_absen: $('#edit_jam_absen').val(),
                    keterangan: $('#edit_keterangan').val(),
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
                        url: '/attendance-details/' + id,
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
