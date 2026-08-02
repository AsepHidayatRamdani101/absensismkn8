<script>
    $(function() {
        $('.select2').select2({
            width: '100%'
        });

        const studentSelect = $('#studentSelect');
        if (studentSelect.length) {
            studentSelect.select2({
                width: '100%',
                ajax: {
                    url: "{{ route('pancawaluya.violation-transactions.students.options') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return data;
                    }
                },
                placeholder: 'Cari NIS/Nama/Kelas/Jurusan',
                minimumInputLength: 1
            });

            @if (!empty($row?->student))
                const selectedStudent = {
                    id: "{{ $row->student->id }}",
                    text: "{{ $row->student->nis }} - {{ $row->student->nama_lengkap }} ({{ $row->classroom?->nama_kelas ?? '-' }})",
                    classroom_id: "{{ $row->classroom_id }}"
                };
                const option = new Option(selectedStudent.text, selectedStudent.id, true, true);
                studentSelect.append(option).trigger('change');
                $('#classroomIdHidden').val(selectedStudent.classroom_id);
            @endif

            studentSelect.on('select2:select', function(e) {
                $('#classroomIdHidden').val(e.params.data.classroom_id || '');
            });
        }

        const itemSelect = $('#violationItemSelect');
        if (itemSelect.length) {
            itemSelect.on('change', function() {
                const itemId = $(this).val();
                if (!itemId) {
                    $('#pointPreview').val('');
                    $('#weightPreview').val('');
                    $('#dimensionPreview').html('-');
                    return;
                }

                $.get("{{ route('pancawaluya.violation-transactions.violation-item-preview') }}", {
                        violation_item_id: itemId
                    })
                    .done(function(resp) {
                        $('#violationCategorySelect').val(resp.category_id).trigger(
                            'change.select2');
                        $('#pointPreview').val(resp.point);
                        $('#weightPreview').val(resp.weight_total);

                        const rows = (resp.dimensions || []).map(function(dimension) {
                            return `<div class="d-flex justify-content-between border-bottom py-1">
                                <span>${dimension.dimension_name}</span>
                                <span>W: ${dimension.weight} | P: ${dimension.point} | WP: ${dimension.weighted_point}</span>
                            </div>`;
                        }).join('');

                        $('#dimensionPreview').html(rows || '-');
                    });
            });

            @if (old('violation_item_id', $row->violation_item_id ?? false))
                itemSelect.trigger('change');
            @endif
        }

        const tableEl = $('#tableViolationTransactions');
        if (!tableEl.length) {
            return;
        }

        const table = tableEl.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('pancawaluya.violation-transactions.datatable') }}",
                data: function(data) {
                    data.academic_year_id = $('#filterAcademicYear').val();
                    data.semester = $('#filterSemester').val();
                    data.classroom_id = $('#filterClassroom').val();
                    data.category_id = $('#filterCategory').val();
                    data.item_id = $('#filterItem').val();
                    data.status = $('#filterStatus').val();
                    data.only_trashed = $('#filterTrashed').is(':checked') ? 1 : 0;
                }
            },
            order: [
                [1, 'desc']
            ],
            columns: [{
                    data: 'checkbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'no',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'date'
                },
                {
                    data: 'student'
                },
                {
                    data: 'class'
                },
                {
                    data: 'category'
                },
                {
                    data: 'item'
                },
                {
                    data: 'point'
                },
                {
                    data: 'dimensions'
                },
                {
                    data: 'source'
                },
                {
                    data: 'creator'
                },
                {
                    data: 'status'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                },
            ],
            columnDefs: [{
                    targets: [0, 11, 12],
                    className: 'text-center'
                },
                {
                    targets: [0, 11, 12],
                    render: function(data) {
                        return data;
                    }
                },
            ],
        });

        $('#filterAcademicYear, #filterSemester, #filterClassroom, #filterCategory, #filterItem, #filterStatus, #filterTrashed')
            .on('change', function() {
                table.ajax.reload();
            });

        function selectedIds() {
            return $('.check-item:checked').map(function() {
                return $(this).val();
            }).get();
        }

        function updateBulkButtons() {
            const total = selectedIds().length;
            const trashed = $('#filterTrashed').is(':checked');
            $('#btnDeleteSelected').toggleClass('d-none', total === 0 || trashed);
            $('#btnRestoreSelected').toggleClass('d-none', total === 0 || !trashed);
        }

        $('#checkAll').on('change', function() {
            $('.check-item').prop('checked', this.checked);
            updateBulkButtons();
        });

        $(document).on('change', '.check-item', updateBulkButtons);

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus transaksi?',
                icon: 'warning',
                showCancelButton: true
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.post("{{ url('pancawaluya/violation-transactions') }}/" + id, {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                }).done(function(resp) {
                    Swal.fire('Berhasil', resp.message, 'success');
                    table.ajax.reload();
                });
            });
        });

        $(document).on('click', '.btn-restore', function() {
            const id = $(this).data('id');
            $.post("{{ url('pancawaluya/violation-transactions') }}/" + id + '/restore', {
                _token: '{{ csrf_token() }}'
            }).done(function(resp) {
                Swal.fire('Berhasil', resp.message, 'success');
                table.ajax.reload();
            });
        });

        $(document).on('click', '.btn-force-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus permanen?',
                icon: 'warning',
                showCancelButton: true
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.post("{{ url('pancawaluya/violation-transactions') }}/" + id +
                    '/force-delete', {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    }).done(function(resp) {
                    Swal.fire('Berhasil', resp.message, 'success');
                    table.ajax.reload();
                });
            });
        });

        $('#btnDeleteSelected').on('click', function() {
            const ids = selectedIds();
            if (!ids.length) return;
            $.post("{{ route('pancawaluya.violation-transactions.bulk-delete') }}", {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                })
                .done(function(resp) {
                    Swal.fire('Berhasil', resp.message, 'success');
                    table.ajax.reload();
                });
        });

        $('#btnRestoreSelected').on('click', function() {
            const ids = selectedIds();
            if (!ids.length) return;
            $.post("{{ route('pancawaluya.violation-transactions.bulk-restore') }}", {
                    _token: '{{ csrf_token() }}',
                    ids: ids
                })
                .done(function(resp) {
                    Swal.fire('Berhasil', resp.message, 'success');
                    table.ajax.reload();
                });
        });

        $('.btn-import').on('click', function() {
            const form = $(this).closest('form');
            const fileInput = form.find('.input-import-file');
            const previewInput = form.find('.input-import-preview');

            fileInput.off('change.importMode').on('change.importMode', function() {
                if (this.files.length === 0) return;
                Swal.fire({
                    title: 'Pilih mode import',
                    text: 'Gunakan Preview untuk validasi sebelum commit data.',
                    icon: 'question',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'Import Sekarang',
                    denyButtonText: 'Preview Dulu',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        previewInput.val('0');
                        form.submit();
                    } else if (result.isDenied) {
                        previewInput.val('1');
                        form.submit();
                    } else {
                        fileInput.val('');
                    }
                });
            });

            fileInput.trigger('click');
        });

        // ---- Modal Create ----
        $('#modalCreate').on('show.bs.modal', function() {
            $('#formCreate')[0].reset();
            $('#createErrors').addClass('d-none').html('');
            $('#createPointPreview').val('');
        });

        $('#modalCreate').on('shown.bs.modal', function() {
            const modal = $(this);
            modal.find('.select2-modal').each(function() {
                if ($(this).data('select2')) $(this).select2('destroy');
            }).select2({
                width: '100%',
                dropdownParent: modal
            });
            if (!$('#createStudentSelect').data('select2')) {
                $('#createStudentSelect').select2({
                    width: '100%',
                    dropdownParent: $('#modalCreate'),
                    ajax: {
                        url: "{{ route('pancawaluya.violation-transactions.students.options') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            return data;
                        }
                    },
                    placeholder: 'Cari NIS/Nama/Kelas/Jurusan',
                    minimumInputLength: 1
                });
            }
            $('#createStudentSelect').val(null).trigger('change');
        });

        $('#createStudentSelect').on('select2:select', function(e) {
            $('#createClassroomId').val(e.params.data.classroom_id || '');
        });

        $('#createViolationItem').on('change', function() {
            const itemId = $(this).val();
            if (!itemId) {
                $('#createPointPreview').val('');
                return;
            }
            $.get("{{ route('pancawaluya.violation-transactions.violation-item-preview') }}", {
                    violation_item_id: itemId
                })
                .done(function(resp) {
                    $('#createViolationCategory').val(resp.category_id).trigger('change');
                    $('#createPointPreview').val(resp.point);
                });
        });

        $('#formCreate').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('[type=submit]').prop('disabled', true);
            $('#createErrors').addClass('d-none').html('');
            const fd = new FormData(this);
            $.ajax({
                url: "{{ route('pancawaluya.violation-transactions.store') }}",
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(resp) {
                    $('#modalCreate').modal('hide');
                    Swal.fire('Berhasil', resp.message, 'success');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        const list = Object.values(errors).flat().map(m => `<li>${m}</li>`)
                            .join('');
                        $('#createErrors').removeClass('d-none').html('<ul class="mb-0">' +
                            list + '</ul>');
                    } else {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan',
                            'error');
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });

        // ---- Modal Edit ----
        let pendingEditData = null;

        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#editErrors').addClass('d-none').html('');
            $.ajax({
                url: "{{ url('pancawaluya/violation-transactions') }}/" + id + "/edit",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(data) {
                    pendingEditData = data;
                    $('#editTxId').val(data.id);
                    $('#editSemester').val(data.semester);
                    $('#editTransactionDate').val(data.transaction_date);
                    $('#editSource').val(data.source);
                    $('#editStatus').val(data.status);
                    $('#editDescription').val(data.description || '');
                    $('#editClassroomId').val(data.classroom_id);
                    $('#modalEdit').modal('show');
                },
                error: function() {
                    Swal.fire('Gagal', 'Gagal memuat data.', 'error');
                }
            });
        });

        $('#modalEdit').on('shown.bs.modal', function() {
            const modal = $(this);
            modal.find('.select2-modal').each(function() {
                if ($(this).data('select2')) $(this).select2('destroy');
            }).select2({
                width: '100%',
                dropdownParent: modal
            });
            if (!$('#editStudentSelect').data('select2')) {
                $('#editStudentSelect').select2({
                    width: '100%',
                    dropdownParent: $('#modalEdit'),
                    ajax: {
                        url: "{{ route('pancawaluya.violation-transactions.students.options') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            return data;
                        }
                    },
                    placeholder: 'Cari NIS/Nama/Kelas/Jurusan',
                    minimumInputLength: 1
                });
            }
            if (pendingEditData) {
                const d = pendingEditData;
                $('#editAcademicYear').val(d.academic_year_id).trigger('change');
                $('#editViolationCategory').val(d.violation_category_id).trigger('change');
                $('#editViolationItem').val(d.violation_item_id).trigger('change');
                if (d.student_id && d.student_text) {
                    const option = new Option(d.student_text, d.student_id, true, true);
                    $('#editStudentSelect').empty().append(option).trigger('change');
                }
                pendingEditData = null;
            }
        });

        $('#editStudentSelect').on('select2:select', function(e) {
            $('#editClassroomId').val(e.params.data.classroom_id || '');
        });

        $('#editViolationItem').on('change', function() {
            const itemId = $(this).val();
            if (!itemId) {
                $('#editPointPreview').val('');
                return;
            }
            $.get("{{ route('pancawaluya.violation-transactions.violation-item-preview') }}", {
                    violation_item_id: itemId
                })
                .done(function(resp) {
                    $('#editViolationCategory').val(resp.category_id).trigger('change');
                    $('#editPointPreview').val(resp.point);
                });
        });

        $('#formEdit').on('submit', function(e) {
            e.preventDefault();
            const id = $('#editTxId').val();
            const btn = $(this).find('[type=submit]').prop('disabled', true);
            $('#editErrors').addClass('d-none').html('');
            const fd = new FormData(this);
            $.ajax({
                url: "{{ url('pancawaluya/violation-transactions') }}/" + id,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(resp) {
                    $('#modalEdit').modal('hide');
                    Swal.fire('Berhasil', resp.message, 'success');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        const list = Object.values(errors).flat().map(m => `<li>${m}</li>`)
                            .join('');
                        $('#editErrors').removeClass('d-none').html('<ul class="mb-0">' +
                            list + '</ul>');
                    } else {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan',
                            'error');
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });
    });
</script>
