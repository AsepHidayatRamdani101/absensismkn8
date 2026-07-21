@extends('adminlte::page')

@section('title', 'Laporan Izin Guru')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Laporan Izin Guru</h1>
        <div class="d-flex">
            <a href="{{ route('reports.teacher-leave.pdf', request()->query()) }}" class="btn btn-danger btn-sm mr-2"><i
                    class="fas fa-file-pdf"></i> Download PDF</a>
            <a href="{{ route('reports.teacher-leave.excel', request()->query()) }}" class="btn btn-success btn-sm"><i
                    class="fas fa-file-excel"></i> Download Excel</a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.teacher-leave') }}" class="mb-3">
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Tipe Periode</label><select name="period_type" id="period_type"
                            class="form-control form-control-sm">
                            <option value="">Semua</option>
                            <option value="tanggal" {{ ($filters['period_type'] ?? '') === 'tanggal' ? 'selected' : '' }}>
                                Tanggal</option>
                            <option value="mingguan" {{ ($filters['period_type'] ?? '') === 'mingguan' ? 'selected' : '' }}>
                                Mingguan</option>
                            <option value="bulanan" {{ ($filters['period_type'] ?? '') === 'bulanan' ? 'selected' : '' }}>
                                Bulanan</option>
                            <option value="tahunan" {{ ($filters['period_type'] ?? '') === 'tahunan' ? 'selected' : '' }}>
                                Tahunan</option>
                        </select></div>
                    <div class="form-group col-md-3 period-field" data-type="tanggal"><label>Tanggal</label><input
                            type="date" name="tanggal" class="form-control form-control-sm"
                            value="{{ $filters['tanggal'] ?? '' }}"></div>
                    <div class="form-group col-md-3 period-field" data-type="mingguan"><label>Minggu</label><input
                            type="week" name="minggu" class="form-control form-control-sm"
                            value="{{ $filters['minggu'] ?? '' }}"></div>
                    <div class="form-group col-md-3 period-field" data-type="bulanan"><label>Bulan</label><input
                            type="month" name="bulan" class="form-control form-control-sm"
                            value="{{ $filters['bulan'] ?? '' }}"></div>
                    <div class="form-group col-md-3 period-field" data-type="tahunan"><label>Tahun</label><input
                            type="number" name="tahun" class="form-control form-control-sm"
                            value="{{ $filters['tahun'] ?? '' }}"></div>
                    <div class="form-group col-md-3"><label>Nama Guru</label><select name="teacher_id"
                            class="form-control form-control-sm">
                            <option value="">Semua Guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}"
                                    {{ (string) ($filters['teacher_id'] ?? '') === (string) $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3"><label>Status Pengajuan</label><select name="status_pengajuan"
                            class="form-control form-control-sm">
                            <option value="">Semua Status</option>
                            <option value="Menunggu"
                                {{ ($filters['status_pengajuan'] ?? '') === 'Menunggu' ? 'selected' : '' }}>Menunggu
                            </option>
                            <option value="Disetujui"
                                {{ ($filters['status_pengajuan'] ?? '') === 'Disetujui' ? 'selected' : '' }}>Disetujui
                            </option>
                            <option value="Ditolak"
                                {{ ($filters['status_pengajuan'] ?? '') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                        </select></div>
                    <div class="form-group col-md-3 d-flex align-items-end"><button type="submit"
                            class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter"></i> Terapkan</button><a
                            href="{{ route('reports.teacher-leave') }}" class="btn btn-secondary btn-sm">Reset</a></div>
                </div>
            </form>
            <p class="text-muted mb-2"><strong>Periode:</strong> {{ $periodLabel }}</p>
            <div class="table-responsive">
                <table id="tableTeacherLeaveReport" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Guru</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Alasan</th>
                            <th>Tugas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->teacher->nama_lengkap ?? '-' }}</td>
                                <td>{{ $item->jenis_pengajuan }}</td>
                                <td>{{ optional($item->tanggal_mulai)->format('d-m-Y') }} s/d
                                    {{ optional($item->tanggal_selesai)->format('d-m-Y') }}</td>
                                <td>{{ $item->alasan }}</td>
                                <td>
                                    @if ($item->lampiran_tugas_path)
                                        Ada File
                                    @elseif ($item->deskripsi_tugas)
                                        {{ \Illuminate\Support\Str::limit($item->deskripsi_tugas, 40) }}@else-
                                    @endif
                                </td>
                                <td>{{ $item->status_pengajuan }}</td>
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
            $('#tableTeacherLeaveReport').DataTable({
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
