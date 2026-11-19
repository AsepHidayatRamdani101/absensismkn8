@extends('adminlte::page')

@section('title', 'Rapor Semester')

@section('content_header')
    <h1>Rapor Semester</h1>
@stop

@php
    $routes = $mode === 'wali'
        ? [
            'index' => route('guru.wali-kelas.report-cards'),
            'print' => route('guru.wali-kelas.report-cards.print'),
            'leger' => route('guru.wali-kelas.report-cards.leger'),
            'note' => route('guru.wali-kelas.report-cards.note', ['student' => '__STUDENT__']),
        ]
        : [
            'index' => route('report-cards.index'),
            'print' => route('report-cards.print'),
            'leger' => route('report-cards.leger'),
            'note' => route('report-cards.note', ['student' => '__STUDENT__']),
        ];
    $formatScore = fn ($value) => $value === null ? '-' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body py-3">
            <form method="GET" action="{{ $routes['index'] }}" class="form-row align-items-end">
                <div class="form-group col-md-4 mb-2">
                    <label class="small mb-1" for="academicYear">Tahun Pelajaran / Semester</label>
                    <select name="academic_year_id" id="academicYear" class="form-control form-control-sm">
                        @foreach ($years as $option)
                            <option value="{{ $option->id }}" @selected($year?->id === $option->id)>
                                {{ $option->tahun_ajaran }} - {{ $option->semester }}{{ $option->is_active ? ' (aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($mode === 'admin')
                    <div class="form-group col-md-4 mb-2">
                        <label class="small mb-1" for="classroomSelect">Kelas</label>
                        <select name="classroom_id" id="classroomSelect" class="form-control form-control-sm" required>
                            <option value="">Pilih kelas</option>
                            @foreach ($classrooms as $option)
                                <option value="{{ $option->id }}" @selected($classroom?->id === $option->id)>
                                    {{ $option->nama_kelas }}{{ $option->major ? ' — ' . $option->major->nama_jurusan : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="form-group col-md-4 mb-2">
                        <label class="small mb-1">Kelas Perwalian</label>
                        <input type="text" class="form-control form-control-sm" value="{{ $classroom?->nama_kelas }}" readonly>
                    </div>
                @endif
                <div class="form-group col-md-2 mb-2">
                    <button class="btn btn-primary btn-sm btn-block"><i class="fas fa-search mr-1"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    @if (!$year)
        <div class="alert alert-warning">Belum ada data tahun ajaran. Tambahkan tahun ajaran terlebih dahulu.</div>
    @elseif (!$report)
        <div class="alert alert-light border">Pilih kelas untuk menampilkan rapor.</div>
    @else
        @php
            $entries = $report['entries'];
            $incompleteCount = $entries->filter(fn ($entry) => $entry['incomplete_subjects'] > 0)->count();
            $settings = $report['settings'];
        @endphp

        <div class="row">
            <div class="col-md-8">
                <div class="callout callout-info py-2">
                    <p class="mb-1">
                        <strong>{{ $report['classroom']->nama_kelas }}</strong>
                        @if ($report['classroom']->major) · {{ $report['classroom']->major->nama_jurusan }} @endif
                        · {{ $report['year']->tahun_ajaran }} · Semester {{ $report['semester_label'] }}
                    </p>
                    <p class="small text-muted mb-0">
                        Wali kelas: {{ $report['homeroom_teacher']?->nama_lengkap ?? 'belum ditentukan' }} ·
                        {{ $report['subjects']->count() }} mapel · {{ $entries->count() }} siswa ·
                        Bobot Harian {{ $settings['daily_weight'] }}% / UTS {{ $settings['uts_weight'] }}% / UAS {{ $settings['uas_weight'] }}% · KKM {{ $settings['kkm'] }}
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box mb-3">
                    <span class="info-box-icon {{ $incompleteCount ? 'bg-warning' : 'bg-success' }}">
                        <i class="fas {{ $incompleteCount ? 'fa-exclamation-triangle' : 'fa-check' }}"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Kelengkapan nilai</span>
                        <span class="info-box-number">
                            {{ $incompleteCount ? $incompleteCount . ' siswa belum lengkap' : 'Semua lengkap' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if ($report['subjects']->isEmpty())
            <div class="alert alert-warning">
                Belum ada data pengampu (guru-mapel) untuk kelas ini pada semester tersebut, sehingga rapor belum bisa disusun.
            </div>
        @endif

        <form method="GET" action="{{ $routes['print'] }}" target="_blank" id="printForm">
            <input type="hidden" name="academic_year_id" value="{{ $report['year']->id }}">
            @if ($mode === 'admin')
                <input type="hidden" name="classroom_id" value="{{ $report['classroom']->id }}">
            @endif

            <div class="card">
                <div class="card-header py-2 d-flex flex-wrap align-items-center">
                    <h3 class="card-title mr-auto"><i class="fas fa-users mr-1"></i> Daftar Siswa</h3>
                    <div class="d-flex flex-wrap align-items-center">
                        <label class="small mb-0 mr-2" for="printDate">Tanggal rapor</label>
                        <input type="date" name="tanggal" id="printDate" class="form-control form-control-sm mr-2 mb-1 mb-md-0" style="width: 160px"
                            value="{{ now()->toDateString() }}">
                        <button type="submit" class="btn btn-danger btn-sm mr-2 mb-1 mb-md-0" @disabled($entries->isEmpty())>
                            <i class="fas fa-print mr-1"></i> Cetak Rapor 1 Kelas
                        </button>
                        <a href="{{ $routes['leger'] . '?' . http_build_query(array_filter(['academic_year_id' => $report['year']->id, 'classroom_id' => $mode === 'admin' ? $report['classroom']->id : null])) }}"
                            class="btn btn-success btn-sm mb-1 mb-md-0">
                            <i class="fas fa-file-excel mr-1"></i> Leger Nilai
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 44px">No</th>
                                    <th style="width: 100px">NIS</th>
                                    <th>Nama Siswa</th>
                                    <th class="text-center">Rata-rata</th>
                                    <th class="text-center">Peringkat</th>
                                    <th class="text-center">Kelengkapan</th>
                                    <th class="text-center" title="Sakit / Izin / Tanpa Keterangan (hari)">S / I / A</th>
                                    <th>Catatan Wali Kelas</th>
                                    <th class="text-center" style="width: 190px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($entries as $index => $entry)
                                    @php
                                        $student = $entry['student'];
                                        $attendance = $entry['attendance'];
                                        $note = $entry['report_card']?->homeroom_note;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student->nis }}</td>
                                        <td>
                                            {{ $student->nama_lengkap }}
                                            @if ((int) $student->classroom_id !== (int) $report['classroom']->id)
                                                <span class="badge badge-secondary" title="Siswa kini berada di kelas lain">pindah kelas</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $formatScore($entry['average']) }}</td>
                                        <td class="text-center">{{ $entry['rank'] ?? '-' }}</td>
                                        <td class="text-center">
                                            @if ($entry['incomplete_subjects'] > 0)
                                                <span class="badge badge-warning">{{ $entry['incomplete_subjects'] }} mapel belum lengkap</span>
                                            @else
                                                <span class="badge badge-success">Lengkap</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $attendance['sakit'] }} / {{ $attendance['izin'] }} / {{ $attendance['alpa'] }}
                                            @if ($attendance['is_override'])
                                                <i class="fas fa-pen text-muted small" title="Dikoreksi wali kelas (otomatis: {{ $attendance['computed']['sakit'] }}/{{ $attendance['computed']['izin'] }}/{{ $attendance['computed']['alpa'] }})"></i>
                                            @endif
                                        </td>
                                        <td class="small">{{ $note ? \Illuminate\Support\Str::limit($note, 70) : '-' }}</td>
                                        <td class="text-center text-nowrap">
                                            <button type="button" class="btn btn-outline-secondary btn-xs" data-toggle="collapse"
                                                data-target="#detail-{{ $student->id }}" title="Lihat rincian nilai">
                                                <i class="fas fa-list"></i>
                                            </button>
                                            @if ($canEdit)
                                                <button type="button" class="btn btn-outline-primary btn-xs btn-note"
                                                    data-student-id="{{ $student->id }}"
                                                    data-student-name="{{ $student->nama_lengkap }}"
                                                    data-note="{{ $note }}"
                                                    data-sick="{{ $entry['report_card']?->sick_days }}"
                                                    data-permit="{{ $entry['report_card']?->permit_days }}"
                                                    data-absent="{{ $entry['report_card']?->absent_days }}"
                                                    data-computed="{{ $attendance['computed']['sakit'] }}/{{ $attendance['computed']['izin'] }}/{{ $attendance['computed']['alpa'] }}">
                                                    <i class="fas fa-edit mr-1"></i>Catatan
                                                </button>
                                            @endif
                                            <button type="submit" name="student_id" value="{{ $student->id }}" class="btn btn-outline-danger btn-xs">
                                                <i class="fas fa-print mr-1"></i>Cetak
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="collapse bg-light" id="detail-{{ $student->id }}">
                                        <td></td>
                                        <td colspan="8" class="p-2">
                                            <table class="table table-sm table-bordered bg-white mb-0">
                                                <thead>
                                                    <tr class="small text-muted">
                                                        <th>Mata Pelajaran</th>
                                                        <th class="text-center">Harian</th>
                                                        <th class="text-center">UTS</th>
                                                        <th class="text-center">UAS</th>
                                                        <th class="text-center">Nilai Rapor</th>
                                                        <th class="text-center">Predikat</th>
                                                        <th>Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="small">
                                                    @foreach ($entry['rows'] as $row)
                                                        <tr>
                                                            <td>{{ $row['subject'] }} <span class="text-muted">— {{ $row['teachers'] ?: '-' }}</span></td>
                                                            <td class="text-center">{{ $formatScore($row['daily']) }}</td>
                                                            <td class="text-center">{{ $formatScore($row['uts']) }}</td>
                                                            <td class="text-center">{{ $formatScore($row['uas']) }}</td>
                                                            <td class="text-center font-weight-bold">{{ $row['final_rounded'] ?? '-' }}</td>
                                                            <td class="text-center">{{ $row['predicate'] ?? '-' }}</td>
                                                            <td>
                                                                @if ($row['missing'])
                                                                    <span class="text-warning">Belum ada: {{ implode(', ', $row['missing']) }}</span>
                                                                @elseif ($row['passed'] === false)
                                                                    <span class="text-danger">Belum tuntas</span>
                                                                @else
                                                                    <span class="text-success">Tuntas</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada siswa pada kelas ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer small text-muted">
                    Ketidakhadiran dihitung otomatis dari absensi KBM (jumlah hari) dan dapat dikoreksi wali kelas melalui tombol <em>Catatan</em>.
                    Rapor yang belum lengkap tetap bisa dicetak, namun komponen yang kosong tidak ikut dihitung.
                </div>
            </div>
        </form>

        @if ($canEdit)
            <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalTitle" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" class="modal-content" id="noteForm" data-action-template="{{ $routes['note'] }}">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $report['year']->id }}">
                        <input type="hidden" name="classroom_id" value="{{ $report['classroom']->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title" id="noteModalTitle">Catatan Rapor</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="homeroomNote">Catatan wali kelas</label>
                                <textarea name="homeroom_note" id="homeroomNote" rows="4" maxlength="1000" class="form-control"
                                    placeholder="Contoh: Pertahankan prestasimu, tingkatkan kedisiplinan datang tepat waktu."></textarea>
                            </div>
                            <label class="mb-1">Ketidakhadiran (hari)</label>
                            <p class="small text-muted mb-2">Kosongkan untuk memakai rekap otomatis: <strong id="computedAbsence">-</strong> (S/I/A).</p>
                            <div class="form-row">
                                <div class="col">
                                    <label class="small mb-0" for="sickDays">Sakit</label>
                                    <input type="number" min="0" max="200" name="sick_days" id="sickDays" class="form-control form-control-sm">
                                </div>
                                <div class="col">
                                    <label class="small mb-0" for="permitDays">Izin</label>
                                    <input type="number" min="0" max="200" name="permit_days" id="permitDays" class="form-control form-control-sm">
                                </div>
                                <div class="col">
                                    <label class="small mb-0" for="absentDays">Tanpa Keterangan</label>
                                    <input type="number" min="0" max="200" name="absent_days" id="absentDays" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif
@stop

@section('js')
    <script>
        $(function () {
            $('.btn-note').on('click', function () {
                const button = $(this);
                const form = $('#noteForm');
                form.attr('action', String(form.data('action-template')).replace('__STUDENT__', button.data('student-id')));
                $('#noteModalTitle').text('Catatan Rapor — ' + button.data('student-name'));
                $('#homeroomNote').val(button.attr('data-note') || '');
                $('#sickDays').val(button.attr('data-sick') || '');
                $('#permitDays').val(button.attr('data-permit') || '');
                $('#absentDays').val(button.attr('data-absent') || '');
                $('#computedAbsence').text(button.data('computed'));
                $('#noteModal').modal('show');
            });
        });
    </script>
@stop
