@extends('adminlte::page')

@section('title', 'Absensi Guru (Kombinasi)')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Absensi Guru (Kombinasi)</h1>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.teacher-attendance-recognition') }}" class="mb-3">
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
                        <a href="{{ route('reports.teacher-attendance-recognition') }}"
                            class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </form>

            <p class="text-muted mb-2"><strong>Periode:</strong> {{ $periodLabel }}</p>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $summary['total_sesi'] }}</h3>
                            <p>Total Sesi</p>
                        </div>
                        <div class="icon"><i class="fas fa-layer-group"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $summary['total_diakui'] }}</h3>
                            <p>Total Diakui (Semua Komponen Lengkap)</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ number_format($summary['persentase_diakui'], 2, ',', '.') }}%</h3>
                            <p>Persentase Diakui</p>
                        </div>
                        <div class="icon"><i class="fas fa-percent"></i></div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <a href="{{ route('reports.teacher-attendance-recognition.missing-teachers', array_merge(['type' => 'absen-siswa'], request()->query())) }}"
                        class="text-decoration-none">
                        <div class="small-box bg-danger mb-0">
                            <div class="inner">
                                <h3>{{ $missingTeacherCards['absen_siswa_belum'] ?? 0 }}</h3>
                                <p>Guru Belum Melakukan Absen Siswa</p>
                            </div>
                            <div class="icon"><i class="fas fa-user-times"></i></div>
                            <div class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('reports.teacher-attendance-recognition.missing-teachers', array_merge(['type' => 'agenda'], request()->query())) }}"
                        class="text-decoration-none">
                        <div class="small-box bg-secondary mb-0">
                            <div class="inner">
                                <h3>{{ $missingTeacherCards['agenda_belum'] ?? 0 }}</h3>
                                <p>Guru Belum Mengisi Agenda</p>
                            </div>
                            <div class="icon"><i class="fas fa-book-dead"></i></div>
                            <div class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('reports.teacher-attendance-recognition.missing-teachers', array_merge(['type' => 'foto'], request()->query())) }}"
                        class="text-decoration-none">
                        <div class="small-box bg-warning mb-0">
                            <div class="inner">
                                <h3>{{ $missingTeacherCards['foto_belum'] ?? 0 }}</h3>
                                <p>Guru Tidak Difoto Oleh Siswa</p>
                            </div>
                            <div class="icon"><i class="fas fa-camera-retro"></i></div>
                            <div class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tableTeacherRecognition" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Guru</th>
                            <th>Mapel</th>
                            <th>Jurusan</th>
                            <th>Kelas</th>
                            <th>Absensi Guru Oleh Siswa (Kamera)</th>
                            <th>Agenda Oleh Guru</th>
                            <th>Absensi Siswa Oleh Guru</th>
                            <th>Skor Diakui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row['item']->tanggal }}</td>
                                <td>{{ $row['item']->teacher->nama_lengkap ?? '-' }}</td>
                                <td>{{ $row['item']->subject->nama_mapel ?? '-' }}</td>
                                <td>{{ $row['item']->classroom->major->nama_jurusan ?? '-' }}</td>
                                <td>{{ $row['item']->classroom->nama_kelas ?? '-' }}</td>
                                <td>
                                    @if ($row['has_absensi_guru_siswa_kamera'])
                                        <span class="badge badge-success">Terisi</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['has_agenda_guru'])
                                        <span class="badge badge-success">Terisi</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['has_absensi_siswa_oleh_guru'])
                                        <span class="badge badge-success">Terisi</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['recognized_point'] === 1)
                                        <span class="badge badge-primary">1</span>
                                    @else
                                        <span class="badge badge-light">0</span>
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
            $('#tableTeacherRecognition').DataTable({
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
