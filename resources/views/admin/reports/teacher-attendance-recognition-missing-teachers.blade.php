@extends('adminlte::page')

@section('title', $title)

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $title }}</h1>
        <a href="{{ route('reports.teacher-attendance-recognition', request()->query()) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-3"><strong>Periode:</strong> {{ $periodLabel }}</p>

            <div class="table-responsive">
                <table id="tableMissingTeachers" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Guru</th>
                            <th>Total Sesi Belum</th>
                            <th>Mapel Terkait</th>
                            <th>Kelas Terkait</th>
                            <th>Tanggal Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <a
                                        href="{{ route('reports.teacher-attendance-recognition.missing-teacher-sessions', array_merge(['type' => $type, 'teacher' => $row['teacher_id']], request()->query())) }}">
                                        {{ $row['teacher_name'] }}
                                    </a>
                                </td>
                                <td>{{ $row['total_sesi_belum'] }}</td>
                                <td>{{ $row['mapel'] !== '' ? $row['mapel'] : '-' }}</td>
                                <td>{{ $row['kelas'] !== '' ? $row['kelas'] : '-' }}</td>
                                <td>{{ $row['tanggal_terakhir'] ?: '-' }}</td>
                                <td>
                                    <a class="btn btn-primary btn-xs"
                                        href="{{ route('reports.teacher-attendance-recognition.missing-teacher-sessions', array_merge(['type' => $type, 'teacher' => $row['teacher_id']], request()->query())) }}">
                                        Lihat Sesi
                                    </a>
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
            $('#tableMissingTeachers').DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        });
    </script>
@stop
