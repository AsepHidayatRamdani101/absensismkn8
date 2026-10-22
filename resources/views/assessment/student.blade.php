@extends('adminlte::page')

@section('title', 'Nilai dan Rekap Absensi Saya')

@section('content_header')
    <h1>Nilai dan Rekap Absensi</h1>
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
                <div class="form-group col-md-3"><button class="btn btn-primary btn-sm">Tampilkan</button></div>
            </form>
        </div>
    </div>
    <div class="card card-outline card-info">
        <div class="card-body">
            <strong>Rata-rata kumulatif nilai akhir semester:</strong>
            {{ $cumulativeGrade === null ? 'Belum tersedia' : number_format($cumulativeGrade, 2) }}
        </div>
    </div>
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Nilai Harian (Project/Tugas)</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Tanggal</th><th>Mata Pelajaran</th><th>Penilaian</th><th>Jenis</th><th>Nilai</th><th>Skor Maksimum</th><th>Bobot</th></tr></thead>
                <tbody>
                    @forelse ($dailyAssessments as $assessment)
                        <tr>
                            <td>{{ $assessment->assessment_date->format('d-m-Y') }}</td>
                            <td>{{ $assessment->teacherSubject->subject->nama_mapel ?? '-' }}</td>
                            <td>{{ $assessment->title }}</td>
                            <td>{{ $assessment->type === 'project' ? 'Project' : 'Tugas' }}</td>
                            <td>{{ $assessment->scores->first()?->score ?? '-' }}</td>
                            <td>{{ $assessment->max_score }}</td><td>{{ $assessment->weight }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Belum ada nilai harian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card card-outline card-success">
        <div class="card-header"><h3 class="card-title">Nilai Akhir</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Mata Pelajaran</th><th>Guru</th><th>Metode</th><th>Rata-rata Harian</th><th>Nilai Akhir</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr><td>{{ $row['subject'] }}</td><td>{{ $row['teacher'] }}</td>
                            <td>{{ $row['grading_method'] === 'automatic' ? 'Otomatis' : 'Manual' }}</td>
                            <td>{{ $row['daily_average'] ?? '-' }}</td><td>{{ $row['final_score'] ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center">Belum ada nilai akhir.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card card-outline card-info">
        <div class="card-header"><h3 class="card-title">Komulatif Absensi Semester</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm"><thead><tr><th>Hadir/Terlambat/Dispen</th><th>Sakit</th><th>Izin</th><th>Alpa</th><th>Bolos</th></tr></thead>
                <tbody><tr>@foreach ($attendance as $count)<td>{{ $count }}</td>@endforeach</tr></tbody>
            </table>
        </div>
    </div>
@stop
