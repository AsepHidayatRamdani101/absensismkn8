@extends('adminlte::page')

@section('title', 'Laporan Early Warning')
@section('plugins.Datatables', true)

@section('content_header')
    <h1>Laporan Early Warning Siswa dan Guru</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ $filters['report_url'] }}" id="earlyWarningFilterForm" class="mb-3">
                <input type="hidden" name="apply_filters" value="1">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="period">Tipe Periode</label>
                        <select id="period" name="period" class="form-control form-control-sm">
                            <option value="monthly" @selected($filters['period'] === 'monthly')>Bulanan</option>
                            <option value="semester" @selected($filters['period'] === 'semester')>Semester aktif</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3" id="monthFilter">
                        <label for="month">Bulan</label>
                        <input type="month" id="month" name="month" class="form-control form-control-sm"
                            value="{{ $filters['month'] }}"
                            min="{{ $semesterRange['start'] }}"
                            max="{{ $semesterRange['end'] }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="teacher_id">Nama Guru</label>
                        <select id="teacher_id" name="teacher_id" class="form-control form-control-sm">
                            <option value="">Semua Guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((string) $filters['teacher_id'] === (string) $teacher->id)>
                                    {{ $teacher->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-filter"></i> Tampilkan
                        </button>
                        <a href="{{ $filters['report_url'] }}" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Tahun ajaran:</strong> {{ $academicYear->tahun_ajaran }} semester {{ $academicYear->semester }}.
        @if ($hasFilters)
            <strong>Periode:</strong> {{ $periodLabel }}.
        @endif
        Ambang peringatan: 5 hari sekolah/jadwal efektif berturut-turut atau 10 hari kejadian dalam satu bulan.
        Pada tampilan semester, ambang tambahan adalah 30 hari kejadian dalam semester. Status siswa dihitung terpisah.
    </div>
    <div class="alert alert-secondary">
        <strong>Arti kolom:</strong>
        <em>Hari Terjadi</em> adalah jumlah tanggal unik dengan kejadian tersebut pada periode terpilih.
        <em>Maks. Berturut-turut</em> adalah rangkaian terpanjang hari sekolah (siswa) atau hari jadwal efektif (guru)
        tanpa jeda yang memenuhi kriteria.
        <em>Maks. dalam Sebulan</em> adalah jumlah hari kejadian terbanyak dalam satu bulan kalender.
        <em>Pemicu Peringatan</em> menyebutkan hasil aktual dan ambang yang terlampaui.
    </div>

    @if ($hasFilters)
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-graduate mr-1"></i> Siswa dengan Status Berulang</h3>
            </div>
            <div class="card-body table-responsive">
                <table id="earlyWarningStudentsTable" class="table table-bordered table-striped table-sm w-100"
                    data-url="{{ $filters['student_data_url'] }}">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Status</th>
                            <th>Hari Terjadi</th>
                            <th>Maks. Berturut-turut</th>
                            <th>Maks. dalam Sebulan</th>
                            <th>Pemicu Peringatan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chalkboard-teacher mr-1"></i> Guru dengan Aktivitas yang Belum Terpenuhi</h3>
            </div>
            <div class="card-body table-responsive">
                <p class="text-muted">
                    Data guru dikelompokkan per guru, kelas, mata pelajaran, dan jenis aktivitas.
                    Angka tidak digabung dengan kelas atau mata pelajaran lain.
                </p>
                <table id="earlyWarningTeachersTable" class="table table-bordered table-striped table-sm w-100"
                    data-url="{{ $filters['teacher_data_url'] }}">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Guru</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Aktivitas yang Belum Terpenuhi</th>
                            <th>Hari Terjadi</th>
                            <th>Maks. Berturut-turut</th>
                            <th>Maks. dalam Sebulan</th>
                            <th>Pemicu Peringatan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-secondary">
            Pilih periode laporan, lalu tekan <strong>Tampilkan</strong> untuk memuat tabel Early Warning.
        </div>
    @endif
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const period = document.getElementById('period');
            const monthFilter = document.getElementById('monthFilter');
            const month = document.getElementById('month');
            const teacher = document.getElementById('teacher_id');

            function updateMonthFilter() {
                const isMonthly = period.value === 'monthly';
                monthFilter.classList.toggle('d-none', !isMonthly);
                month.disabled = !isMonthly;
                month.required = isMonthly;
            }

            if (period && monthFilter && month && teacher) {
                period.addEventListener('change', updateMonthFilter);
            }
            if (period && monthFilter && month) {
                updateMonthFilter();
            }
        });
        @if ($hasFilters)
            $(function () {
                const filterForm = $('#earlyWarningFilterForm');

                function initEarlyWarningTable(selector, columns) {
                    const table = $(selector);
                    table.DataTable({
                        processing: true,
                        serverSide: true,
                        pageLength: 10,
                        language: {
                            emptyTable: selector === '#earlyWarningStudentsTable'
                                ? 'Tidak ada siswa yang memenuhi ambang Early Warning pada filter ini.'
                                : 'Tidak ada guru yang memenuhi ambang Early Warning pada filter ini.',
                            zeroRecords: 'Tidak ada data yang cocok dengan pencarian.',
                            processing: 'Memuat data...'
                        },
                        ajax: {
                            url: table.data('url'),
                            data: function (data) {
                                filterForm.serializeArray().forEach(function (field) {
                                    data[field.name] = field.value;
                                });
                            },
                            error: function (xhr) {
                                const response = xhr.responseJSON;
                                const message = response && response.message
                                    ? response.message
                                    : 'Data Early Warning gagal dimuat. Periksa filter atau hubungi administrator.';
                                table.closest('.table-responsive').before(
                                    $('<div class="alert alert-danger mt-2"></div>').text(message)
                                );
                            }
                        },
                        columns: columns.map(function (column, index) {
                            return {
                                data: column || 'row_number',
                                name: column || 'row_number',
                                orderable: index !== 0 && Boolean(column),
                                searchable: Boolean(column),
                                render: function (value, type, row, meta) {
                                    return index === 0
                                        ? value
                                        : $('<div>').text(value ?? '').html();
                                }
                            };
                        }),
                        order: [[1, 'asc']]
                    });
                }

                initEarlyWarningTable('#earlyWarningStudentsTable', [
                    'row_number', 'nis', 'name', 'classroom', 'status', 'total_days',
                    'consecutive_days', 'peak_month_days', 'warning'
                ]);
                initEarlyWarningTable('#earlyWarningTeachersTable', [
                    'row_number', 'name', 'classroom', 'subject', 'issue', 'total_days',
                    'consecutive_days', 'peak_month_days', 'warning'
                ]);
            });
        @endif
    </script>
@stop
