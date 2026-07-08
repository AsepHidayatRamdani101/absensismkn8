<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        let tableTeacherAttendances = $('#tableTeacherAttendances').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'tableTeacherAttendances') {
                return true;
            }

            let tahunAjaranFilter = $('#filterTahunAjaran').val();
            let tanggalFilter = $('#filterTanggal').val();
            let guruFilter = $('#filterGuru').val();
            let mapelFilter = $('#filterMapel').val();
            let pertemuanFilter = $('#filterPertemuan').val();
            let kelasFilter = $('#filterKelas').val();

            let rowNode = tableTeacherAttendances.row(dataIndex).node();

            if (!rowNode) {
                return true;
            }

            let rowTahunAjaran = $(rowNode).data('tahun-ajaran') ? String($(rowNode).data(
                'tahun-ajaran')) : '';
            let rowTanggal = $(rowNode).data('tanggal') ? String($(rowNode).data('tanggal')) : '';
            let rowGuru = $(rowNode).data('guru') ? String($(rowNode).data('guru')) : '';
            let rowMapel = $(rowNode).data('mapel') ? String($(rowNode).data('mapel')) : '';
            let rowPertemuan = $(rowNode).data('pertemuan') ? String($(rowNode).data('pertemuan')) : '';
            let rowKelas = $(rowNode).data('kelas') ? String($(rowNode).data('kelas')) : '';

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

            if (pertemuanFilter && rowPertemuan !== pertemuanFilter) {
                return false;
            }

            if (kelasFilter && rowKelas !== kelasFilter) {
                return false;
            }

            return true;
        });

        $('#filterTahunAjaran, #filterTanggal, #filterGuru, #filterMapel, #filterPertemuan, #filterKelas').on(
            'change',
            function() {
                tableTeacherAttendances.draw();
            }
        );


        //----------------------------------
        // CREATE
        //----------------------------------

        $('#formCreate').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('teacher-attendances.store') }}",
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

            $.get('/teacher-attendances/' + id + '/edit', function(data) {
                $('#edit_id').val(data.id);
                $('#edit_teacher_id').val(data.teacher_id);
                $('#edit_schedule_id').val(data.schedule_id);
                $('#edit_classroom_id').val(data.classroom_id);
                $('#edit_subject_id').val(data.subject_id);
                $('#edit_academic_year_id').val(data.academic_year_id);
                $('#edit_tanggal').val(data.tanggal);
                $('#edit_pertemuan').val(data.pertemuan);
                $('#edit_materi_pembelajaran').val(data.materi_pembelajaran);
                $('#edit_catatan_guru').val(data.catatan_guru);
                $('#edit_status').val(data.status);

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
                url: '/teacher-attendances/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    teacher_id: $('#edit_teacher_id').val(),
                    schedule_id: $('#edit_schedule_id').val(),
                    classroom_id: $('#edit_classroom_id').val(),
                    subject_id: $('#edit_subject_id').val(),
                    academic_year_id: $('#edit_academic_year_id').val(),
                    tanggal: $('#edit_tanggal').val(),
                    pertemuan: $('#edit_pertemuan').val(),
                    materi_pembelajaran: $('#edit_materi_pembelajaran').val(),
                    catatan_guru: $('#edit_catatan_guru').val(),
                    status: $('#edit_status').val(),
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
                        url: '/teacher-attendances/' + id,
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
