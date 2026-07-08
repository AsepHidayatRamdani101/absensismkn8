<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        $('#tableTeacherSubjects').DataTable({
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
                url: "{{ route('teacher-subjects.store') }}",
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

            $.get('/teacher-subjects/' + id + '/edit', function(data) {
                $('#edit_id').val(data.id);
                $('#edit_teacher_id').val(data.teacher_id);
                $('#edit_subject_id').val(data.subject_id);
                $('#edit_classroom_id').val(data.classroom_id);
                $('#edit_academic_year_id').val(data.academic_year_id);

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
                url: '/teacher-subjects/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    teacher_id: $('#edit_teacher_id').val(),
                    subject_id: $('#edit_subject_id').val(),
                    classroom_id: $('#edit_classroom_id').val(),
                    academic_year_id: $('#edit_academic_year_id').val(),
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
                        url: '/teacher-subjects/' + id,
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
