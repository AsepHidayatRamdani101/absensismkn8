@extends('adminlte::page')

@section('title', $title)

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $title }}</h1>
        <div class="d-flex">
            <a href="{{ route('reports.teacher-attendance-recognition.missing-teacher-sessions.pdf', array_merge(['type' => $type, 'teacher' => $teacher->id], request()->query())) }}"
                class="btn btn-danger btn-sm mr-2">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            <a href="{{ route('reports.teacher-attendance-recognition.missing-teacher-sessions.excel', array_merge(['type' => $type, 'teacher' => $teacher->id], request()->query())) }}"
                class="btn btn-success btn-sm mr-2">
                <i class="fas fa-file-excel"></i> Download Excel
            </a>
            <a href="{{ route('reports.teacher-attendance-recognition.missing-teachers', array_merge(['type' => $type], request()->query())) }}"
                class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <p class="mb-1"><strong>Guru:</strong> {{ $teacher->nama_lengkap ?? '-' }}</p>
            <p class="text-muted mb-3"><strong>Periode:</strong> {{ $periodLabel }}</p>

            <div class="table-responsive">
                <table id="tableMissingTeacherSessions" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Mapel</th>
                            <th>Jurusan</th>
                            <th>Kelas</th>
                            <th>Absen Guru oleh Siswa (Kamera)</th>
                            <th>Agenda Guru</th>
                            <th>Absen Siswa oleh Guru</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row['tanggal'] }}</td>
                                <td>{{ $row['mapel'] }}</td>
                                <td>{{ $row['jurusan'] }}</td>
                                <td>{{ $row['kelas'] }}</td>
                                <td>
                                    @if ($row['absensi_guru_siswa_kamera'])
                                        <span class="badge badge-success">Terisi</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['agenda_guru'])
                                        <span class="badge badge-success">Terisi</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['absensi_siswa_oleh_guru'])
                                        <span class="badge badge-success">Terisi</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
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
            $('#tableMissingTeacherSessions').DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        });
    </script>
@stop
