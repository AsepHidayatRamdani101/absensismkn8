@push('css')
    <style>
        @media (max-width: 767.98px) {
            .content-header h1 {
                font-size: 1.25rem;
            }

            .content-header p,
            .content-header .badge {
                font-size: .85rem;
            }

            .content .btn {
                min-height: 38px;
                border-radius: .5rem;
                font-size: .79rem;
                font-weight: 600;
                letter-spacing: .01em;
                padding: .38rem .62rem;
            }

            .content .btn.btn-xs,
            .content .btn.btn-sm {
                min-height: 36px;
                font-size: .76rem;
                padding: .34rem .56rem;
            }

            .content .table-responsive {
                border: 1px solid #e5e7eb;
                border-radius: .75rem;
                background: #fff;
            }

            .content .card {
                border-radius: .8rem;
            }

            .content .card .card-body {
                padding: .9rem;
            }

            .dataTables_wrapper .row {
                margin-left: 0;
                margin-right: 0;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                width: 100%;
                margin-bottom: .5rem;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                margin-left: 0 !important;
            }

            .dataTables_wrapper .dataTables_length select {
                width: 100% !important;
                margin-left: 0 !important;
            }
        }
    </style>
@endpush
