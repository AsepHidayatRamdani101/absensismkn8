<script>
    $(function() {
        const editUrlTemplate = @json(route('classrooms.edit', ['classroom' => '__CLASSROOM__']));
        const updateUrlTemplate = @json(route('classrooms.update', ['classroom' => '__CLASSROOM__']));
        const deleteUrlTemplate = @json(route('classrooms.destroy', ['classroom' => '__CLASSROOM__']));

        function showRequestError(xhr, fallbackMessage) {
            const errors = xhr.responseJSON && xhr.responseJSON.errors;
            const message = errors
                ? Object.values(errors).flat().join('\n')
                : (xhr.responseJSON && xhr.responseJSON.message) || fallbackMessage;

            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: message
            });
        }

        $('#tableClassrooms').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        $('#formCreate').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('classrooms.store') }}",
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalCreate').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message
                    }).then(() => location.reload());
                },
                error: function(xhr) {
                    showRequestError(xhr, 'Periksa kembali data kelas.');
                }
            });
        });

        $('.btn-edit').click(function() {
            const id = $(this).data('id');

            $.get(editUrlTemplate.replace('__CLASSROOM__', id), function(data) {
                $('#edit_id').val(data.id);
                $('#edit_major_id').val(data.major_id);
                $('#edit_tingkat').val(data.tingkat);
                $('#edit_rombel').val(data.rombel);
                $('#edit_kode_kelas').val(data.kode_kelas);
                $('#edit_nama_kelas').val(data.nama_kelas);
                $('#modalEdit').modal('show');
            }).fail(function(xhr) {
                showRequestError(xhr, 'Data kelas gagal dimuat.');
            });
        });

        $('#formEdit').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: updateUrlTemplate.replace('__CLASSROOM__', $('#edit_id').val()),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#modalEdit').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message
                    }).then(() => location.reload());
                },
                error: function(xhr) {
                    showRequestError(xhr, 'Periksa kembali data kelas.');
                }
            });
        });

        $('.btn-delete').click(function() {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Hapus data?',
                text: 'Data tidak bisa dikembalikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: deleteUrlTemplate.replace('__CLASSROOM__', id),
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
                        }).then(() => location.reload());
                    },
                    error: function(xhr) {
                        showRequestError(xhr, 'Data kelas gagal dihapus.');
                    }
                });
            });
        });
    });
</script>
