@extends('adminlte::page')

@section('title', 'Rekap Nilai Wali Kelas')

@section('content_header')
    <h1>Rekap Nilai Wali Kelas {{ $teacher->waliClassroom->nama_kelas ?? '' }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="form-row align-items-end">
                <div class="form-group col-md-4">
                    <label>Tahun Ajaran / Semester</label>
                    <select name="academic_year_id" class="form-control form-control-sm">
                        @foreach ($years as $yearOption)
                            <option value="{{ $yearOption->id }}" @selected($year->id === $yearOption->id)>
                                {{ $yearOption->tahun_ajaran }} - {{ $yearOption->semester }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Tipe Periode</label>
                    <select name="period_type" id="period_type" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="tanggal" @selected(request('period_type') === 'tanggal')>Tanggal</option>
                        <option value="mingguan" @selected(request('period_type') === 'mingguan')>Mingguan</option>
                        <option value="bulanan" @selected(request('period_type') === 'bulanan')>Per Bulan</option>
                        <option value="tahunan" @selected(request('period_type') === 'tahunan')>Tahunan</option>
                    </select>
                </div>
                <div class="form-group col-md-3 period-field" data-type="tanggal">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ request('tanggal') }}">
                </div>
                <div class="form-group col-md-3 period-field" data-type="mingguan">
                    <label>Minggu</label>
                    <input type="week" name="minggu" class="form-control form-control-sm" value="{{ request('minggu') }}">
                </div>
                <div class="form-group col-md-3 period-field" data-type="bulanan">
                    <label>Bulan Rekap Nilai</label>
                    <input type="month" name="bulan" class="form-control form-control-sm" value="{{ request('bulan', now()->format('Y-m')) }}">
                </div>
                <div class="form-group col-md-3 period-field" data-type="tahunan">
                    <label>Tahun</label>
                    <input type="number" name="tahun" class="form-control form-control-sm" value="{{ request('tahun') }}" min="2000" max="2100">
                </div>
                <div class="form-group col-md-2">
                    <button class="btn btn-primary btn-sm">Tampilkan</button>
                </div>
                <div class="form-group col-md-3 text-right">
                    <a href="{{ route('guru.wali-kelas.assessments.download', array_merge(['format' => 'pdf', 'academic_year_id' => $year->id], request()->only(['period_type', 'tanggal', 'minggu', 'bulan', 'tahun']))) }}"
                        class="btn btn-danger btn-sm">Download PDF</a>
                    <a href="{{ route('guru.wali-kelas.assessments.download', array_merge(['format' => 'excel', 'academic_year_id' => $year->id], request()->only(['period_type', 'tanggal', 'minggu', 'bulan', 'tahun']))) }}"
                        class="btn btn-success btn-sm">Download Excel</a>
                </div>
            </form>
            <p class="text-muted">Rata-rata tugas/penilaian harian setiap mata pelajaran ditampilkan per siswa. Rekap mengikuti bulan yang dipilih.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead><tr>
                        @foreach (array_keys($rows->first() ?? ['NIS' => '', 'Siswa' => '']) as $heading)
                            <th>{{ $heading }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>@foreach ($row as $value)<td>{{ $value ?? '-' }}</td>@endforeach</tr>
                        @empty
                            <tr><td colspan="2" class="text-center">Belum ada data nilai untuk semester ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    $(function () {
        function togglePeriodField() {
            const type = $('#period_type').val();
            $('.period-field').hide();
            if (type) $('.period-field[data-type="' + type + '"]').show();
        }
        $('#period_type').on('change', togglePeriodField);
        togglePeriodField();
    });
</script>
@stop
