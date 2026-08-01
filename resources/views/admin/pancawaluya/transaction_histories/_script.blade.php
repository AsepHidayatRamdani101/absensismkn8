<script>
    $(function() {
        $('.select2').select2({
            width: '100%'
        });

        const table = $('#tableTransactionHistories').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('pancawaluya.transaction-histories.datatable') }}",
                data: function(data) {
                    data.academic_year_id = $('#filterAcademicYear').val();
                    data.semester = $('#filterSemester').val();
                    data.classroom_id = $('#filterClassroom').val();
                    data.reference_type = $('#filterReferenceType').val();
                    data.status = $('#filterStatus').val();
                    data.action = $('#filterAction').val();
                }
            },
            columns: [{
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
                    data: 'type'
                },
                {
                    data: 'action'
                },
                {
                    data: 'status'
                },
                {
                    data: 'score'
                },
                {
                    data: 'source'
                },
                {
                    data: 'actor'
                },
            ]
        });

        $('#filterAcademicYear, #filterSemester, #filterClassroom, #filterReferenceType, #filterStatus, #filterAction')
            .on('change', function() {
                table.ajax.reload();
            });
    });
</script>
