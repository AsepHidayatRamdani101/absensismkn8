@extends('adminlte::page')

@include('guru.partials.mobile-ux')

@section('title', 'Rekap Siswa Wali Kelas')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1>Rekap Siswa Wali Kelas</h1>
            <p class="text-muted mb-0">
                {{ $classroom->nama_kelas ?? '-' }}
                @if ($classroom?->major)
                    - {{ $classroom->major->nama_jurusan }}
                @endif
            </p>
        </div>
        <div class="d-flex mt-2 mt-md-0">
            <a href="{{ route('guru.wali-kelas.rekap-siswa.pdf', request()->query()) }}" class="btn btn-danger btn-sm mr-2">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            <a href="{{ route('guru.wali-kelas.rekap-siswa.excel', request()->query()) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Download Excel
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('guru.wali-kelas.rekap-siswa') }}" class="mb-3">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Tipe Periode</label>
                        <select name="period_type" id="period_type" class="form-control form-control-sm">
                            <option value="">Semua</option>
                            <option value="tanggal" {{ ($filters['period_type'] ?? '') === 'tanggal' ? 'selected' : '' }}>
                                Tanggal</option>
                            <option value="mingguan" {{ ($filters['period_type'] ?? '') === 'mingguan' ? 'selected' : '' }}>
                                Mingguan</option>
                            <option value="bulanan" {{ ($filters['period_type'] ?? '') === 'bulanan' ? 'selected' : '' }}>
                                Bulanan</option>
                            <option value="tahunan" {{ ($filters['period_type'] ?? '') === 'tahunan' ? 'selected' : '' }}>
                                Tahunan</option>
                        </select>
                    </div>

                    <div class="form-group col-md-3 period-field" data-type="tanggal">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm"
                            value="{{ $filters['tanggal'] ?? '' }}">
                    </div>

                    <div class="form-group col-md-3 period-field" data-type="mingguan">
                        <label>Minggu</label>
                        <input type="week" name="minggu" class="form-control form-control-sm"
                            value="{{ $filters['minggu'] ?? '' }}">
                    </div>

                    <div class="form-group col-md-3 period-field" data-type="bulanan">
                        <label>Bulan</label>
                        <input type="month" name="bulan" class="form-control form-control-sm"
                            value="{{ $filters['bulan'] ?? '' }}">
                    </div>

                    <div class="form-group col-md-3 period-field" data-type="tahunan">
                        <label>Tahun</label>
                        <input type="number" name="tahun" class="form-control form-control-sm"
                            value="{{ $filters['tahun'] ?? '' }}" min="2000" max="2100">
                    </div>

                    <div class="form-group col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-filter"></i> Terapkan
                        </button>
                        <a href="{{ route('guru.wali-kelas.rekap-siswa') }}" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            <p class="text-muted mb-2"><strong>Periode:</strong> {{ $periodLabel }}</p>

            <div class="table-responsive">
                <table id="tableWaliClassRecap" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Identitas</th>
                            <th>Hadir</th>
                            <th>Sakit</th>
                            <th>Izin</th>
                            <th>Alpa</th>
                            <th>Total</th>
                            <th>% Hadir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row['student']->nis }}</td>
                                <td>{{ $row['student']->nama_lengkap }}</td>
                                <td>
                                    <button type="button" class="btn btn-info btn-xs btn-student-identity"
                                        data-name="{{ $row['student']->nama_lengkap }}"
                                        data-nis="{{ $row['student']->nis }}"
                                        data-nisn="{{ $row['student']->nisn ?? '-' }}"
                                        data-parent="{{ $row['student']->nama_orang_tua_wali ?? '-' }}"
                                        data-address="{{ $row['student']->alamat ?? '-' }}"
                                        data-student-phone="{{ $row['student']->no_hp ?? '-' }}"
                                        data-parent-phone="{{ $row['student']->no_hp_orang_tua ?? '-' }}"
                                        data-height="{{ $row['student']->tinggi_badan ?? '-' }}"
                                        data-weight="{{ $row['student']->berat_badan ?? '-' }}">
                                        <i class="fas fa-id-card"></i>
                                    </button>
                                </td>
                                <td>{{ $row['hadir'] }}</td>
                                <td>{{ $row['sakit'] }}</td>
                                <td>{{ $row['izin'] }}</td>
                                <td>{{ $row['alpa'] }}</td>
                                <td>{{ $row['total'] }}</td>
                                <td>{{ $row['persen_hadir'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">Total</th>
                            <th>{{ $totals['hadir'] }}</th>
                            <th>{{ $totals['sakit'] }}</th>
                            <th>{{ $totals['izin'] }}</th>
                            <th>{{ $totals['alpa'] }}</th>
                            <th>{{ $totals['total'] }}</th>
                            <th>-</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script>
        $(function() {
            $('#tableWaliClassRecap').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });

            function togglePeriodField() {
                let periodType = $('#period_type').val();

                $('.period-field').hide();

                if (periodType) {
                    $('.period-field[data-type="' + periodType + '"]').show();
                }
            }

            $('#period_type').on('change', togglePeriodField);
            togglePeriodField();

            $('.btn-student-identity').on('click', function() {
                const button = $(this);

                Swal.fire({
                    title: button.data('name'),
                    width: 700,
                    html: `
                        <div class="text-left">
                            <p class="mb-2"><strong>NIS:</strong> ${button.data('nis') || '-'}</p>
                            <p class="mb-2"><strong>NISN:</strong> ${button.data('nisn') || '-'}</p>
                            <p class="mb-2"><strong>Orang Tua / Wali:</strong> ${button.data('parent') || '-'}</p>
                            <p class="mb-2"><strong>Alamat:</strong> ${button.data('address') || '-'}</p>
                            <p class="mb-2"><strong>No HP Siswa:</strong> ${button.data('student-phone') || '-'}</p>
                            <p class="mb-2"><strong>No HP Orang Tua:</strong> ${button.data('parent-phone') || '-'}</p>
                            <p class="mb-2"><strong>Tinggi Badan:</strong> ${button.data('height') || '-'} cm</p>
                            <p class="mb-0"><strong>Berat Badan:</strong> ${button.data('weight') || '-'} kg</p>
                        </div>
                    `,
                    confirmButtonText: 'Tutup'
                });
            });
        });
    </script>
@stop
