<script>
    $(function() {

        //----------------------------------
        // DATATABLE
        //----------------------------------

        let tableTeacherSubjects = $('#tableTeacherSubjects').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        // Build filter options map from ALL table rows (including paginated ones) using DataTables API
        let filterOptions = [];
        tableTeacherSubjects.rows().every(function() {
            let rowNode = this.node();
            filterOptions.push({
                teacher: $(rowNode).data('teacher') ? String($(rowNode).data('teacher')) : '',
                subject: $(rowNode).data('subject') ? String($(rowNode).data('subject')) : '',
                classroom: $(rowNode).data('classroom') ? String($(rowNode).data('classroom')) :
                    ''
            });
        });

        let originalSubjectOptions = $('#filterSubject option').not(':first').map(function() {
            return {
                value: $(this).attr('value') || '',
                text: $(this).text()
            };
        }).get();

        let originalClassroomOptions = $('#filterClassroom option').not(':first').map(function() {
            return {
                value: $(this).attr('value') || '',
                text: $(this).text()
            };
        }).get();

        // Custom filter untuk DataTables
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'tableTeacherSubjects') {
                return true;
            }

            let teacherFilter = $('#filterTeacher').val();
            let subjectFilter = $('#filterSubject').val();
            let classroomFilter = $('#filterClassroom').val();

            let rowNode = tableTeacherSubjects.row(dataIndex).node();

            if (!rowNode) {
                return true;
            }

            let rowTeacher = $(rowNode).data('teacher') ? String($(rowNode).data('teacher')) : '';
            let rowSubject = $(rowNode).data('subject') ? String($(rowNode).data('subject')) : '';
            let rowClassroom = $(rowNode).data('classroom') ? String($(rowNode).data('classroom')) : '';

            if (teacherFilter && rowTeacher !== teacherFilter) {
                return false;
            }

            if (subjectFilter && rowSubject !== subjectFilter) {
                return false;
            }

            if (classroomFilter && rowClassroom !== classroomFilter) {
                return false;
            }

            return true;
        });

        // Sync subject options based on teacher filter
        function syncSubjectOptions() {
            let teacherFilter = $('#filterTeacher').val();
            let currentSubject = $('#filterSubject').val();

            let availableSubjects = [];
            $.each(filterOptions, function(_, option) {
                if (!teacherFilter || option.teacher === teacherFilter) {
                    if (availableSubjects.indexOf(option.subject) === -1) {
                        availableSubjects.push(option.subject);
                    }
                }
            });

            $('#filterSubject').find('option:not(:first)').remove();
            $.each(originalSubjectOptions, function(_, option) {
                if (availableSubjects.indexOf(option.value) > -1) {
                    $('#filterSubject').append(
                        $('<option>', {
                            value: option.value,
                            text: option.text
                        })
                    );
                }
            });

            let isCurrentSubjectStillAvailable = availableSubjects.indexOf(currentSubject) > -1;
            if (isCurrentSubjectStillAvailable) {
                $('#filterSubject').val(currentSubject);
            } else {
                $('#filterSubject').val('');
            }
        }

        // Sync classroom options based on teacher and subject filters
        function syncClassroomOptions() {
            let teacherFilter = $('#filterTeacher').val();
            let subjectFilter = $('#filterSubject').val();
            let currentClassroom = $('#filterClassroom').val();

            let availableClassrooms = [];
            $.each(filterOptions, function(_, option) {
                if ((!teacherFilter || option.teacher === teacherFilter) &&
                    (!subjectFilter || option.subject === subjectFilter)) {
                    if (availableClassrooms.indexOf(option.classroom) === -1) {
                        availableClassrooms.push(option.classroom);
                    }
                }
            });

            $('#filterClassroom').find('option:not(:first)').remove();
            $.each(originalClassroomOptions, function(_, option) {
                if (availableClassrooms.indexOf(option.value) > -1) {
                    $('#filterClassroom').append(
                        $('<option>', {
                            value: option.value,
                            text: option.text
                        })
                    );
                }
            });

            let isCurrentClassroomStillAvailable = availableClassrooms.indexOf(currentClassroom) > -1;
            if (isCurrentClassroomStillAvailable) {
                $('#filterClassroom').val(currentClassroom);
            } else {
                $('#filterClassroom').val('');
            }
        }

        // Event listeners untuk filter dropdowns
        $('#filterTeacher').on('change', function() {
            syncSubjectOptions();
            syncClassroomOptions();
            tableTeacherSubjects.draw();
        });

        $('#filterSubject').on('change', function() {
            syncClassroomOptions();
            tableTeacherSubjects.draw();
        });

        $('#filterClassroom').on('change', function() {
            tableTeacherSubjects.draw();
        });


        //----------------------------------
        // CREATE
        //----------------------------------

        function updateCreateClassroomOptions() {
            let subjectId = $('#create_subject_id').val();
            let academicYearId = $('#create_academic_year_id').val();

            // Reset semua option (enable semua)
            $('#create_classroom_id option').prop('disabled', false);
            $('#create_classroom_id').trigger('change');
            $('#create_classroom_warning').remove();

            if (subjectId && academicYearId) {
                // Fetch kelas yang sudah di-assign untuk mapel+tahun ajaran ini (semua guru)
                $.get("{{ route('teacher-subjects.assigned-classrooms') }}", {
                        subject_id: subjectId,
                        academic_year_id: academicYearId
                    },
                    function(assignedClassroomIds) {
                        // Disable & deselect kelas yang sudah di-assign
                        $.each(assignedClassroomIds, function(_, classroomId) {
                            $('#create_classroom_id option[value="' + classroomId + '"]')
                                .prop('disabled', true)
                                .prop('selected', false);
                        });
                        $('#create_classroom_id').trigger('change');

                        // Cek apakah masih ada kelas yang tersedia
                        let availableCount = $('#create_classroom_id option:not(:disabled)').length;
                        if (availableCount === 0) {
                            $('#create_classroom_id').closest('.form-group').append(
                                '<div id="create_classroom_warning" class="alert alert-warning mt-2 mb-0 py-2 px-3">' +
                                '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                                'Semua kelas sudah memiliki guru pengampu untuk mata pelajaran ini.' +
                                '</div>'
                            );
                        }
                    }
                );
            }
        }

        function updateCreateSubjectOptions() {
            let academicYearId = $('#create_academic_year_id').val();

            // Reset semua subject option
            $('#create_subject_id option').prop('disabled', false).each(function() {
                let text = $(this).text().replace(' (Penuh)', '');
                $(this).text(text);
            });
            $('#create_subject_id').trigger('change');

            if (academicYearId) {
                $.get("{{ route('teacher-subjects.full-subjects') }}", {
                        academic_year_id: academicYearId
                    },
                    function(fullSubjectIds) {
                        $.each(fullSubjectIds, function(_, subjectId) {
                            let $option = $('#create_subject_id option[value="' + subjectId + '"]');
                            $option.prop('disabled', true).text($option.text() + ' (Penuh)');
                        });
                        $('#create_subject_id').trigger('change');

                        // Jika subject yang sedang dipilih ternyata penuh, reset
                        let currentSubject = $('#create_subject_id').val();
                        if (currentSubject && fullSubjectIds.indexOf(parseInt(currentSubject)) > -1) {
                            $('#create_subject_id').val('').trigger('change');
                        }
                    }
                );
            }
        }

        $('#modalCreate').on('shown.bs.modal', function() {
            $('#create_teacher_id, #create_subject_id, #create_classroom_id, #create_academic_year_id')
                .select2({
                    theme: 'bootstrap-5',
                    placeholder: '- Pilih -',
                    dropdownParent: $('#modalCreate'),
                    width: '100%'
                });
        });

        $('#create_academic_year_id').on('change', function() {
            updateCreateSubjectOptions();
            updateCreateClassroomOptions();
        });

        $('#create_subject_id').on('change', function() {
            updateCreateClassroomOptions();
        });

        $('#formCreate').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('teacher-subjects.store') }}",
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalCreate').modal('hide');
                    $('#formCreate')[0].reset();

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
                        text: xhr.responseJSON?.message ?? 'Terjadi kesalahan'
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
                $('#edit_teacher_id').val(data.teacher_id).change();
                $('#edit_subject_id').val(data.subject_id).change();
                $('#edit_academic_year_id').val(data.academic_year_id).change();

                // Fetch kelas yang sudah di-assign di guru+mapel+tahun ajaran ini
                $.get("{{ route('teacher-subjects.classrooms', ['teacher' => '__TEACHER__', 'subject' => '__SUBJECT__', 'academicYear' => '__YEAR__']) }}"
                    .replace('__TEACHER__', data.teacher_id)
                    .replace('__SUBJECT__', data.subject_id)
                    .replace('__YEAR__', data.academic_year_id),
                    function(currentClassrooms) {
                        // Fetch kelas yang sudah di-assign guru lain (exclude record ini)
                        $.get("{{ route('teacher-subjects.assigned-classrooms') }}", {
                                subject_id: data.subject_id,
                                academic_year_id: data.academic_year_id,
                                exclude_id: data.id
                            },
                            function(assignedByOthers) {
                                // Enable semua dulu
                                $('#edit_classroom_id option').prop('disabled', false);

                                // Disable kelas yang sudah di-assign guru lain
                                $.each(assignedByOthers, function(_, classroomId) {
                                    $('#edit_classroom_id option[value="' +
                                            classroomId + '"]')
                                        .prop('disabled', true)
                                        .prop('selected', false);
                                });

                                // Set selected kelas milik guru ini
                                $('#edit_classroom_id').val(currentClassrooms).trigger(
                                    'change');
                            }
                        );
                    });

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
                    'classroom_id[]': $('#edit_classroom_id').val() || [],
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
                        text: xhr.responseJSON?.message ?? 'Terjadi kesalahan'
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
                                text: xhr.responseJSON?.message ??
                                    'Terjadi kesalahan'
                            });
                        }
                    });
                }
            });
        });


        //----------------------------------
        // DELETE MULTIPLE
        //----------------------------------

        function updateTeacherSubjectBulkBtn() {
            let count = $('.check-teacher-subject:checked').length;
            $('#selectedCountTeacherSubjects').text(count);
            $('#btnDeleteMultipleTeacherSubjects').toggleClass('d-none', count === 0);
        }

        $('#checkAllTeacherSubjects').on('change', function() {
            $('.check-teacher-subject').prop('checked', this.checked);
            updateTeacherSubjectBulkBtn();
        });

        $(document).on('change', '.check-teacher-subject', function() {
            if (!this.checked) $('#checkAllTeacherSubjects').prop('checked', false);
            updateTeacherSubjectBulkBtn();
        });

        $('#btnDeleteMultipleTeacherSubjects').on('click', function() {
            let ids = $('.check-teacher-subject:checked').map(function() {
                return this.value;
            }).get();
            Swal.fire({
                title: 'Hapus ' + ids.length + ' guru pengampu?',
                text: 'Data tidak dapat dikembalikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('teacher-subjects.destroy-multiple') }}",
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
