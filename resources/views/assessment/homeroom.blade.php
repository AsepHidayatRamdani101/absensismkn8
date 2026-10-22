@extends('adminlte::page')

@section('title', 'Rapor dan Absensi Kelas')

@section('content_header')
    <h1>Nilai Akhir dan Absensi Kelas {{ $teacher->waliClassroom->nama_kelas ?? '' }}</h1>
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
                    <button class="btn btn-primary btn-sm">Tampilkan</button>
                </div>
                <div class="form-group col-md-5 text-right">
                    <a href="{{ route('guru.wali-kelas.assessments.download', ['format' => 'pdf', 'academic_year_id' => $year->id]) }}"
                        class="btn btn-danger btn-sm">Download PDF</a>
                    <a href="{{ route('guru.wali-kelas.assessments.download', ['format' => 'excel', 'academic_year_id' => $year->id]) }}"
                        class="btn btn-success btn-sm">Download Excel</a>
                </div>
            </form>
            <p class="text-muted">Nilai akhir ditampilkan per mata pelajaran. Absensi merupakan rekap siswa pada semester terpilih; Hadir, Terlambat, dan Dispen digabung.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead><tr>
                        @foreach (array_keys($rows->first() ?? ['NIS' => '', 'Siswa' => '', 'Mata Pelajaran' => '', 'Guru' => '', 'Metode Nilai' => '', 'Nilai Akhir' => '', 'Hadir/Terlambat/Dispen' => '', 'Sakit' => '', 'Izin' => '', 'Alpa' => '', 'Bolos' => '']) as $heading)
                            <th>{{ $heading }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>@foreach ($row as $value)<td>{{ $value ?? '-' }}</td>@endforeach</tr>
                        @empty
                            <tr><td colspan="11" class="text-center">Belum ada data nilai untuk semester ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
