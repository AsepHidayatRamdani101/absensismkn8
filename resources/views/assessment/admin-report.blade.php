@extends('adminlte::page')

@section('title', $kind === 'daily' ? 'Laporan Penilaian Harian' : 'Laporan Penilaian Akhir')
@section('plugins.Datatables', true)

@section('content_header')
    <h1>{{ $kind === 'daily' ? 'Laporan Penilaian Harian' : 'Laporan Penilaian Akhir' }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route($routePrefix) }}" class="mb-3">
                <input type="hidden" name="apply_filters" value="1">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Semester</label>
                        <select name="academic_year_id" class="form-control form-control-sm" required>
                            <option value="">Pilih semester</option>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}" @selected((string) ($filters['academic_year_id'] ?? '') === (string) $year->id)>
                                    {{ $year->tahun_ajaran }} - {{ $year->semester }}{{ $year->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Kelas</label>
                        <select name="classroom_id" class="form-control form-control-sm">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected((string) ($filters['classroom_id'] ?? '') === (string) $classroom->id)>
                                    {{ $classroom->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Guru</label>
                        <select name="teacher_id" class="form-control form-control-sm">
                            <option value="">Semua Guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((string) ($filters['teacher_id'] ?? '') === (string) $teacher->id)>
                                    {{ $teacher->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm mr-2" type="submit">Tampilkan</button>
                        <a href="{{ route($routePrefix) }}" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            @if ($hasFilters)
                <div class="mb-3">
                    <a class="btn btn-danger btn-sm mr-2"
                        href="{{ route($routePrefix . '.pdf', request()->query()) }}">Download PDF</a>
                    <a class="btn btn-success btn-sm"
                        href="{{ route($routePrefix . '.excel', request()->query()) }}">Download Excel</a>
                </div>
                <table id="assessmentAdminTable" class="table table-bordered table-striped table-sm w-100"
                    data-url="{{ route($routePrefix . '.data', ['kind' => $kind]) }}">
                    <thead>
                        <tr>
                            @if ($kind === 'daily')
                                <th>Kelas</th><th>Guru</th><th>Mata Pelajaran</th><th>NIS</th><th>Siswa</th>
                                <th>Penilaian</th><th>Jenis</th><th>Tanggal</th><th>Nilai</th><th>Maks.</th><th>Bobot</th>
                            @else
                                <th>Kelas</th><th>Guru</th><th>Mata Pelajaran</th><th>NIS</th><th>Siswa</th>
                                <th>Metode Nilai</th><th>Nilai Akhir</th><th>Hadir/Terlambat/Dispen</th>
                                <th>Sakit</th><th>Izin</th><th>Alpa</th><th>Bolos</th>
                            @endif
                        </tr>
                    </thead>
                </table>
            @else
                <div class="alert alert-secondary mb-0">Pilih semester, kelas, atau guru lalu tekan Tampilkan.</div>
            @endif
        </div>
    </div>
@stop

@if ($hasFilters)
    @php
        $fields = $kind === 'daily'
            ? ['Kelas', 'Guru', 'Mata Pelajaran', 'NIS', 'Siswa', 'Penilaian', 'Jenis', 'Tanggal', 'Nilai', 'Skor Maksimum', 'Bobot']
            : ['Kelas', 'Guru', 'Mata Pelajaran', 'NIS', 'Siswa', 'Metode Nilai', 'Nilai Akhir', 'Hadir/Terlambat/Dispen', 'Sakit', 'Izin', 'Alpa', 'Bolos'];
    @endphp
    @section('js')
        <script>
            $(function () {
                const table = $('#assessmentAdminTable');
                const form = $('form');
                const fields = @json($fields);

                table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: table.data('url'),
                        data: function (data) {
                            form.serializeArray().forEach(function (field) { data[field.name] = field.value; });
                        }
                    },
                    columns: fields.map(function (field) { return { data: field, name: field }; })
                });
            });
        </script>
    @stop
@endif
