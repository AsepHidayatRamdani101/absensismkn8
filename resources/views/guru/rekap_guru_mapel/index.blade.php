@extends('adminlte::page')

@include('guru.partials.mobile-ux')

@section('title', 'Rekap Guru Mapel')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Rekap Guru Mapel</h1>
            <p class="text-muted mb-0">{{ $teacher->nama_lengkap }} - Rekap absensi siswa per mapel yang diampu.</p>
        </div>
        <div class="d-flex mt-2 mt-md-0">
            <a href="{{ route('guru.mapel.rekap.pdf', request()->query()) }}" class="btn btn-danger btn-sm mr-2">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            <a href="{{ route('guru.mapel.rekap.excel', request()->query()) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Download Excel
            </a>
        </div>
    </div>
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('guru.mapel.rekap') }}">
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

                    @if ($showSubjectFilter)
                        <div class="form-group col-md-3">
                            <label>Mapel</label>
                            <select name="subject_id" class="form-control form-control-sm">
                                <option value="">Semua Mapel</option>
                                @foreach ($subjectOptions as $subject)
                                    <option value="{{ $subject->id }}"
                                        {{ (string) ($filters['subject_id'] ?? '') === (string) $subject->id ? 'selected' : '' }}>
                                        {{ $subject->nama_mapel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($showClassroomFilter)
                        <div class="form-group col-md-3">
                            <label>Kelas</label>
                            <select name="classroom_id" class="form-control form-control-sm">
                                <option value="">Semua Kelas</option>
                                @foreach ($classroomOptions as $classroom)
                                    <option value="{{ $classroom->id }}"
                                        {{ (string) ($filters['classroom_id'] ?? '') === (string) $classroom->id ? 'selected' : '' }}>
                                        {{ $classroom->nama_kelas }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-filter"></i> Terapkan
                        </button>
                        <a href="{{ route('guru.mapel.rekap') }}" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            <p class="text-muted mb-2"><strong>Periode:</strong> {{ $periodLabel }}</p>

            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-light border mb-0">
                        <div class="d-flex flex-wrap" style="gap: 1rem;">
                            <span><strong>Total Data:</strong> {{ $totals['total'] }}</span>
                            <span><strong>Hadir:</strong> {{ $totals['hadir'] }}</span>
                            <span><strong>Sakit:</strong> {{ $totals['sakit'] }}</span>
                            <span><strong>Izin:</strong> {{ $totals['izin'] }}</span>
                            <span><strong>Alpa:</strong> {{ $totals['alpa'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tableGuruMapelRecap" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Mapel</th>
                            <th>Kelas</th>
                            <th>Siswa</th>
                            <th>Status</th>
                            <th>Jam Absen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $item)
                            @php
                                $status = $item->status === 'Alpha' ? 'Alpa' : $item->status;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->teacherAttendance->tanggal ?? '-' }}</td>
                                <td>{{ $item->teacherAttendance->subject->nama_mapel ?? '-' }}</td>
                                <td>{{ $item->teacherAttendance->classroom->nama_kelas ?? '-' }}</td>
                                <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                                <td>{{ $status }}</td>
                                <td>{{ $item->jam_absen ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
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
            $('#tableGuruMapelRecap').DataTable({
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
        });
    </script>
@stop
