<script>
    $(function() {
        $('.select2').select2({
            width: '100%'
        });

        const table = $('#tableViolations').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('pancawaluya.violations.datatable') }}",
                data: function(d) {
                    d.status = $('#filterStatus').val();
                    d.category_id = $('#filterCategory').val();
                    d.dimension_id = $('#filterDimension').val();
                    d.only_trashed = $('#filterTrashed').is(':checked') ? 1 : 0;
                }
            },
            columns: [{
                    data: 'checkbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'no'
                }, {
                    data: 'code'
                }, {
                    data: 'category'
                }, {
                    data: 'name'
                }, {
                    data: 'point'
                },
                {
                    data: 'dimension'
                }, {
                    data: 'weight'
                }, {
                    data: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_by',
                    searchable: false
                }, {
                    data: 'updated_at'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                },
            ]
        });

        $('#filterStatus, #filterCategory, #filterDimension, #filterTrashed').on('change', function() {
            table.ajax.reload();
        });

        function updateBulkButtons() {
            const checked = $('.check-item:checked').length;
            $('#btnDeleteSelected').toggleClass('d-none', checked === 0);
            $('#btnRestoreSelected').toggleClass('d-none', checked === 0);
        }

        $(document).on('change', '#checkAll', function() {
            $('.check-item').prop('checked', this.checked);
            updateBulkButtons();
        });
        $(document).on('change', '.check-item', function() {
            if (!this.checked) $('#checkAll').prop('checked', false);
            updateBulkButtons();
        });

        $('.btn-import').on('click', function() {
            const form = $(this).closest('form');
            const fileInput = form.find('.input-import-file');
            const previewInput = form.find('.input-import-preview');

            fileInput.off('change.importMode').on('change.importMode', function() {
                if (this.files.length === 0) {
                    return;
                }

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

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus data?',
                icon: 'warning',
                showCancelButton: true
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: "{{ url('pancawaluya/violations') }}/" + id,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(resp) {
                        Swal.fire('Berhasil', resp.message, 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    }
                });
            });
        });

        $(document).on('click', '.btn-restore', function() {
            const id = $(this).data('id');
            $.post("{{ url('pancawaluya/violations') }}/" + id + "/restore", {
                _token: '{{ csrf_token() }}'
            }, function(resp) {
                Swal.fire('Berhasil', resp.message, 'success');
                table.ajax.reload();
            }).fail(function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            });
        });

        $(document).on('click', '.btn-force-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus permanen?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: "{{ url('pancawaluya/violations') }}/" + id + "/force-delete",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(resp) {
                        Swal.fire('Berhasil', resp.message, 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    }
                });
            });
        });

        $('#btnDeleteSelected').on('click', function() {
            const ids = $('.check-item:checked').map(function() {
                return this.value;
            }).get();
            if (!ids.length) return;
            $.ajax({
                url: "{{ route('pancawaluya.violations.bulk-delete') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE',
                    ids: ids
                },
                success: function(resp) {
                    Swal.fire('Berhasil', resp.message, 'success');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan',
                        'error');
                }
            });
        });

        $('#btnRestoreSelected').on('click', function() {
            const ids = $('.check-item:checked').map(function() {
                return this.value;
            }).get();
            if (!ids.length) return;
            $.post("{{ route('pancawaluya.violations.bulk-restore') }}", {
                _token: '{{ csrf_token() }}',
                ids: ids
            }, function(resp) {
                Swal.fire('Berhasil', resp.message, 'success');
                table.ajax.reload();
            }).fail(function(xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            });
        });

        // ---- Modal Create ----
        $('#modalCreate').on('show.bs.modal', function() {
            $('#formCreate')[0].reset();
            $('#createErrors').addClass('d-none').html('');
        });

        $('#modalCreate').on('shown.bs.modal', function() {
            const modal = $(this);
            modal.find('.select2-modal').each(function() {
                if ($(this).data('select2')) $(this).select2('destroy');
            }).select2({
                width: '100%',
                dropdownParent: modal
            });
        });

        $('#formCreate').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('[type=submit]').prop('disabled', true);
            $('#createErrors').addClass('d-none').html('');
            $.ajax({
                url: "{{ route('pancawaluya.violations.store') }}",
                type: 'POST',
                data: $(this).serialize(),
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
                url: "{{ url('pancawaluya/violations') }}/" + id + "/edit",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(data) {
                    pendingEditData = data;
                    $('#editId').val(data.id);
                    $('#editCode').val(data.code);
                    $('#editName').val(data.name);
                    $('#editPoint').val(data.point);
                    $('#editWeight').val(data.weight);
                    $('#editDescription').val(data.description || '');
                    $('#editIsActive').val(data.is_active ? '1' : '0');
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
            if (pendingEditData) {
                $('#editViolationCategoryId').val(pendingEditData.violation_category_id).trigger(
                    'change');
                $('#editCharacterDimensionId').val(pendingEditData.character_dimension_id).trigger(
                    'change');
                pendingEditData = null;
            }
        });

        $('#formEdit').on('submit', function(e) {
            e.preventDefault();
            const id = $('#editId').val();
            const btn = $(this).find('[type=submit]').prop('disabled', true);
            $('#editErrors').addClass('d-none').html('');
            $.ajax({
                url: "{{ url('pancawaluya/violations') }}/" + id,
                type: 'POST',
                data: $(this).serialize(),
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
