@extends('adminlte::page')

@section('title', 'Laporan Absensi Siswa')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Laporan Absensi Siswa</h1>
        <div class="d-flex">
            <a href="{{ route('reports.student-attendance.pdf', request()->query()) }}" class="btn btn-danger btn-sm mr-2">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            <a href="{{ route('reports.student-attendance.excel', request()->query()) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Download Excel
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.student-attendance') }}" class="mb-3">
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
                            value="{{ $filters['tahun'] ?? '' }}" min="2000" max="2100" placeholder="Contoh: 2026">
                    </div>

                    <div class="form-group col-md-3">
                        <label>Nama Guru</label>
                        <select name="teacher_id" class="form-control form-control-sm">
                            <option value="">Semua Guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}"
                                    {{ (string) ($filters['teacher_id'] ?? '') === (string) $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Nama Siswa</label>
                        <select name="student_id" class="form-control form-control-sm">
                            <option value="">Semua Siswa</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}"
                                    {{ (string) ($filters['student_id'] ?? '') === (string) $student->id ? 'selected' : '' }}>
                                    {{ $student->nama_lengkap }} ({{ $student->classroom->nama_kelas ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Jurusan</label>
                        <select name="major_id" id="major_id" class="form-control form-control-sm">
                            <option value="">Semua Jurusan</option>
                            @foreach ($majors as $major)
                                <option value="{{ $major->id }}"
                                    {{ (string) ($filters['major_id'] ?? '') === (string) $major->id ? 'selected' : '' }}>
                                    {{ $major->nama_jurusan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Kelas</label>
                        <select name="classroom_id" id="classroom_id" class="form-control form-control-sm">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" data-major-id="{{ $classroom->major_id }}"
                                    {{ (string) ($filters['classroom_id'] ?? '') === (string) $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-filter"></i> Terapkan
                        </button>
                        <a href="{{ route('reports.student-attendance') }}" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            <p class="text-muted mb-2"><strong>Periode:</strong> {{ $periodLabel }}</p>

            <table id="tableStudentReport" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Tanggal</th>
                        <th>Guru</th>
                        <th>Siswa</th>
                        <th>Mapel</th>
                        <th>Jurusan</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Jam Absen</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->teacherAttendance->tanggal ?? '-' }}</td>
                            <td>{{ $item->teacherAttendance->teacher->nama_lengkap ?? '-' }}</td>
                            <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                            <td>{{ $item->teacherAttendance->subject->nama_mapel ?? '-' }}</td>
                            <td>{{ $item->student->classroom->major->nama_jurusan ?? '-' }}</td>
                            <td>{{ $item->student->classroom->nama_kelas ?? '-' }}</td>
                            <td>{{ $item->status }}</td>
                            <td>{{ $item->jam_absen ?? '-' }}</td>
                            <td>{{ $item->keterangan ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script>
        $(function() {
            $('#tableStudentReport').DataTable({
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

            function filterClassroomsByMajor() {
                let majorId = $('#major_id').val();
                let selectedClassroom = $('#classroom_id').val();

                $('#classroom_id option').each(function() {
                    let optionMajorId = $(this).data('major-id');
                    let isPlaceholder = !$(this).val();

                    if (isPlaceholder) {
                        $(this).prop('hidden', false);
                        return;
                    }

                    $(this).prop('hidden', majorId ? String(optionMajorId) !== String(majorId) : false);
                });

                if ($('#classroom_id option:selected').prop('hidden')) {
                    $('#classroom_id').val('');
                } else {
                    $('#classroom_id').val(selectedClassroom);
                }
            }

            $('#period_type').on('change', togglePeriodField);
            $('#major_id').on('change', filterClassroomsByMajor);

            togglePeriodField();
            filterClassroomsByMajor();
        });
    </script>
@stop
