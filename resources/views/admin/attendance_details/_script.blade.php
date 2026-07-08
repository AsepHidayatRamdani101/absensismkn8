<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        let tableAttendanceDetails = $('#tableAttendanceDetails').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'tableAttendanceDetails') {
                return true;
            }

            let tahunAjaranFilter = $('#filterTahunAjaran').val();
            let tanggalFilter = $('#filterTanggal').val();
            let guruFilter = $('#filterGuru').val();
            let mapelFilter = $('#filterMapel').val();
            let kelasFilter = $('#filterKelas').val();
            let statusFilter = $('#filterStatus').val();

            let rowNode = tableAttendanceDetails.row(dataIndex).node();

            if (!rowNode) {
                return true;
            }

            let rowTahunAjaran = $(rowNode).data('tahun-ajaran') ? String($(rowNode).data(
                'tahun-ajaran')) : '';
            let rowTanggal = $(rowNode).data('tanggal') ? String($(rowNode).data('tanggal')) : '';
            let rowGuru = $(rowNode).data('guru') ? String($(rowNode).data('guru')) : '';
            let rowMapel = $(rowNode).data('mapel') ? String($(rowNode).data('mapel')) : '';
            let rowKelas = $(rowNode).data('kelas') ? String($(rowNode).data('kelas')) : '';
            let rowStatus = $(rowNode).data('status') ? String($(rowNode).data('status')) : '';

            if (tahunAjaranFilter && rowTahunAjaran !== tahunAjaranFilter) {
                return false;
            }

            if (tanggalFilter && rowTanggal !== tanggalFilter) {
                return false;
            }

            if (guruFilter && rowGuru !== guruFilter) {
                return false;
            }

            if (mapelFilter && rowMapel !== mapelFilter) {
                return false;
            }

            if (kelasFilter && rowKelas !== kelasFilter) {
                return false;
            }

            if (statusFilter && rowStatus !== statusFilter) {
                return false;
            }

            return true;
        });

        $('#filterTahunAjaran, #filterTanggal, #filterGuru, #filterMapel, #filterKelas, #filterStatus').on(
            'change',
            function() {
                tableAttendanceDetails.draw();
            }
        );

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
