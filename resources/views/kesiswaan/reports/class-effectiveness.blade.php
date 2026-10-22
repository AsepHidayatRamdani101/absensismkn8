@extends('adminlte::page')

@section('title', 'Laporan Efektivitas Belajar Kelas')

@section('plugins.Datatables', true)

@section('content_header')
    @php
        $reportRoute = request()->routeIs('admin.reports.class-effectiveness*')
            ? 'admin.reports.class-effectiveness'
            : 'kesiswaan.reports.class-effectiveness';
    @endphp
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Laporan Efektivitas Belajar Kelas</h1>
            <p class="text-muted mb-0">Persentase efektivitas belajar, kehadiran guru, dan status absensi siswa.</p>
        </div>
        @if ($hasFilters)
            <div class="mt-2">
            <a href="{{ route($reportRoute . '.pdf', request()->query()) }}"
                class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route($reportRoute . '.excel', request()->query()) }}"
                class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            </div>
        @endif
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route($reportRoute) }}" id="effectivenessFilterForm" class="mb-4">
                <input type="hidden" name="apply_filters" value="1">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="date_from">Tanggal Mulai</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                            value="{{ $filters['date_from'] }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="date_to">Tanggal Selesai</label>
                        <input type="date" id="date_to" name="date_to" class="form-control"
                            value="{{ $filters['date_to'] }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="classroom_id">Kelas</label>
                        <select id="classroom_id" name="classroom_id" class="form-control">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}"
                                    @selected((string) ($filters['classroom_id'] ?? '') === (string) $classroom->id)>
                                    {{ $classroom->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-filter"></i> Tampilkan
                        </button>
                        <a href="{{ route($reportRoute) }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>

            @if ($hasFilters)
                <div class="alert alert-info">
                    <strong>Periode:</strong> {{ $periodLabel }}.
                    Efektivitas kelas = (Hadir + Terlambat + Dispen) ÷ seluruh catatan status absensi siswa.
                    Dispen dihitung hadir. Persentase kehadiran guru = sesi jadwal efektif tercatat Hadir ÷ seluruh
                    sesi jadwal efektif. Status siswa ditampilkan sebagai jumlah dan persentase catatan absensi kelas.
                </div>
                <h4 class="mt-4">Laporan Efektivitas dan Kehadiran Siswa</h4>
                <div class="table-responsive mb-4">
                    <table id="classEffectivenessStudentsTable" class="table table-bordered table-striped table-sm w-100"
                        data-url="{{ route($reportRoute . '.students') }}">
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th>Jurusan</th>
                                <th>Efektivitas</th>
                                <th>Catatan Hadir / Total</th>
                                <th>Rincian Status Siswa</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <h4>Kehadiran Guru</h4>
                <div class="table-responsive">
                    <table id="classEffectivenessTeachersTable" class="table table-bordered table-striped table-sm w-100"
                        data-url="{{ route($reportRoute . '.teachers') }}">
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th>Nama Guru</th>
                                <th>Sesi Hadir</th>
                                <th>Sesi Jadwal Efektif</th>
                                <th>Persentase Kehadiran</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @else
                <div class="alert alert-secondary mb-0">
                    Pilih periode tanggal, lalu tekan <strong>Tampilkan</strong> untuk memuat data laporan.
                </div>
            @endif
        </div>
    </div>
@stop

@if ($hasFilters)
    @section('js')
        <script>
            $(function () {
                const form = $('#effectivenessFilterForm');
                const studentsTable = $('#classEffectivenessStudentsTable');
                const teachersTable = $('#classEffectivenessTeachersTable');

                function escapeHtml(value) {
                    return $('<div>').text(value ?? '').html();
                }

                function createTable(table, columns, order) {
                    table.DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    pageLength: 10,
                    ajax: {
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            const message = response && response.message
                                ? response.message
                                : 'Data laporan gagal dimuat. Silakan periksa filter dan coba kembali.';
                            table.closest('.table-responsive').before(
                                $('<div class="alert alert-danger mt-2"></div>').text(message)
                            );
                        },
                        url: table.data('url'),
                        data: function (data) {
                            form.serializeArray().forEach(function (field) {
                                data[field.name] = field.value;
                            });
                        }
                    },
                    columns: columns,
                    order: order
                    });
                }

                createTable(studentsTable, [
                        { data: 'classroom', name: 'classroom' },
                        { data: 'major', name: 'major' },
                        {
                            data: 'effectiveness',
                            name: 'effectiveness',
                            render: function (value) { return Number(value).toFixed(2) + '%'; }
                        },
                        {
                            data: null,
                            name: 'student_records',
                            render: function (_, type, row) {
                                return escapeHtml(row.student_present_records) + ' / ' + escapeHtml(row.student_records);
                            }
                        },
                        {
                            data: 'status_summary',
                            name: 'status_summary',
                            orderable: false,
                            searchable: true,
                            render: function (value) { return escapeHtml(value).split(' | ').join('<br>'); }
                        }
                    ], [[0, 'asc']]);

                createTable(teachersTable, [
                        { data: 'classroom', name: 'classroom' },
                        { data: 'teacher', name: 'teacher' },
                        { data: 'present', name: 'present' },
                        { data: 'expected', name: 'expected' },
                        {
                            data: 'percentage',
                            name: 'percentage',
                            render: function (value) { return Number(value).toFixed(2) + '%'; }
                        }
                    ], [[0, 'asc']]);
            });
        </script>
    @stop
@endif
