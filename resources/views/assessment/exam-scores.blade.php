@extends('adminlte::page')

@section('title', 'Nilai UTS & UAS')
@section('plugins.Sweetalert2', true)

@section('content_header')
    <h1>Nilai UTS &amp; UAS</h1>
@stop

@php
    $formatScore = fn ($value) => $value === null ? '' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
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
        <div class="card-header py-2">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Pilih Kelas &amp; Mata Pelajaran</h3>
        </div>
        <div class="card-body py-2">
            <form id="filterForm" method="GET" action="{{ route('guru.assessments.exams') }}" class="form-row">
                <input type="hidden" name="teacher_subject_id" id="teacherSubjectInput" value="{{ $assignment?->id }}">
                <div class="form-group col-md-4 mb-1">
                    <label class="mb-0 small" for="filterMajor">Jurusan</label>
                    <select id="filterMajor" class="form-control form-control-sm"></select>
                </div>
                <div class="form-group col-md-4 mb-1">
                    <label class="mb-0 small" for="filterClassroom">Kelas</label>
                    <select id="filterClassroom" class="form-control form-control-sm"></select>
                </div>
                <div class="form-group col-md-4 mb-1">
                    <label class="mb-0 small" for="filterSubject">Mata Pelajaran</label>
                    <select id="filterSubject" class="form-control form-control-sm"></select>
                </div>
            </form>
            @if ($assignments->isEmpty())
                <p class="text-muted small mb-0 mt-1">Belum ada penugasan mengajar pada tahun ajaran aktif.</p>
            @endif
        </div>
    </div>

    @if ($assignment)
        <div class="callout callout-info py-2">
            <p class="mb-1">
                <strong>{{ $assignment->classroom?->nama_kelas }}</strong> ·
                {{ $assignment->subject?->nama_mapel }} ·
                {{ $assignment->academicYear?->tahun_ajaran }} {{ $assignment->academicYear?->semester }}
            </p>
            <p class="small text-muted mb-0">
                Nilai Rapor = Harian {{ $settings['daily_weight'] }}% + UTS {{ $settings['uts_weight'] }}% + UAS {{ $settings['uas_weight'] }}%
                · KKM {{ $settings['kkm'] }}.
                Nilai Harian diambil dari menu <a href="{{ route('guru.assessments.final', ['teacher_subject_id' => $assignment->id]) }}">Nilai Akhir</a>
                (manual/otomatis). Kosongkan kolom bila siswa belum ujian; komponen kosong tidak dihitung dan ditandai belum lengkap.
            </p>
        </div>

        <div class="card">
            <div class="card-header py-2 d-flex flex-wrap align-items-center">
                <h3 class="card-title mr-auto"><i class="fas fa-pen mr-1"></i> Input Nilai</h3>
                <div class="d-flex flex-wrap align-items-center mt-1 mt-md-0">
                    <a href="{{ route('guru.assessments.exams.template', $assignment) }}" class="btn btn-outline-success btn-sm mr-2 mb-1">
                        <i class="fas fa-file-download mr-1"></i> Download Template Excel
                    </a>
                    <form method="POST" action="{{ route('guru.assessments.exams.import', $assignment) }}" enctype="multipart/form-data"
                        class="d-inline-flex align-items-center mb-1" id="importForm">
                        @csrf
                        <input type="file" name="file" id="importFile" class="d-none" accept=".xlsx,.xls,.csv">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnImport">
                            <i class="fas fa-file-upload mr-1"></i> Import Excel
                        </button>
                    </form>
                </div>
            </div>
            <form method="POST" action="{{ route('guru.assessments.exams.update', $assignment) }}" id="examForm">
                @csrf
                <div class="card-body p-0">
                    <div class="px-3 pt-3">
                        <input type="search" id="studentSearch" class="form-control form-control-sm mb-2" placeholder="Cari nama atau NIS siswa...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0" id="examTable">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 48px">No</th>
                                    <th style="width: 110px">NIS</th>
                                    <th>Nama Siswa</th>
                                    <th class="text-center" style="width: 110px">Harian</th>
                                    <th class="text-center" style="width: 120px">UTS</th>
                                    <th class="text-center" style="width: 120px">UAS</th>
                                    <th class="text-center" style="width: 110px">Nilai Rapor</th>
                                    <th class="text-center" style="width: 90px">Predikat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $index => $row)
                                    @php $studentId = $row['student']->id; @endphp
                                    <tr data-search="{{ mb_strtolower($row['student']->nama_lengkap . ' ' . $row['student']->nis) }}"
                                        data-daily="{{ $row['daily'] ?? '' }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row['student']->nis }}</td>
                                        <td>{{ $row['student']->nama_lengkap }}</td>
                                        <td class="text-center">{{ $row['daily'] === null ? '-' : $formatScore($row['daily']) }}</td>
                                        <td>
                                            <input type="number" name="scores[{{ $studentId }}][uts]" class="form-control form-control-sm text-center score-input"
                                                min="0" max="100" step="0.01" inputmode="decimal"
                                                value="{{ old('scores.' . $studentId . '.uts', $formatScore($row['uts'])) }}"
                                                aria-label="Nilai UTS {{ $row['student']->nama_lengkap }}">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[{{ $studentId }}][uas]" class="form-control form-control-sm text-center score-input"
                                                min="0" max="100" step="0.01" inputmode="decimal"
                                                value="{{ old('scores.' . $studentId . '.uas', $formatScore($row['uas'])) }}"
                                                aria-label="Nilai UAS {{ $row['student']->nama_lengkap }}">
                                        </td>
                                        <td class="text-center">
                                            <span class="font-weight-bold final-cell"
                                                title="{{ $row['final'] === null ? '' : 'Nilai tepat: ' . $formatScore($row['final']) }}">{{ $row['final'] === null ? '-' : (int) round($row['final']) }}</span>
                                            <div class="small text-warning missing-hint">{{ $row['final'] !== null && $row['missing'] ? 'Belum ada ' . implode(', ', $row['missing']) : '' }}</div>
                                        </td>
                                        <td class="text-center predicate-cell">
                                            {{ $row['predicate'] ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada siswa di kelas ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($rows->isNotEmpty())
                    <div class="card-footer d-flex flex-wrap align-items-center">
                        <span class="text-muted small mr-auto" id="dirtyHint">Perubahan belum disimpan akan hilang bila halaman ditinggalkan.</span>
                        <button type="submit" class="btn btn-primary btn-sm mt-1 mt-md-0">
                            <i class="fas fa-save mr-1"></i> Simpan Semua Nilai
                        </button>
                    </div>
                @endif
            </form>
        </div>
    @endif
@stop

@php
    $assignmentOptions = $assignments->map(fn ($item) => [
        'id' => $item->id,
        'major_id' => $item->classroom?->major_id,
        'major' => $item->classroom?->major?->nama_jurusan ?? 'Tanpa Jurusan',
        'classroom_id' => $item->classroom_id,
        'classroom' => $item->classroom?->nama_kelas,
        'subject' => $item->subject?->nama_mapel,
    ])->values();
@endphp

@section('js')
    <script>
        $(function () {
            const assignments = @json($assignmentOptions);
            const selectedId = @json($assignment?->id);
            const settings = @json($settings);
            const current = assignments.find(a => a.id === selectedId) || null;
            const majorSel = $('#filterMajor'), classSel = $('#filterClassroom'), subjSel = $('#filterSubject');

            function fill(select, items, selectedVal, placeholder) {
                select.empty().append($('<option>').val('').text(placeholder));
                items.forEach(i => select.append($('<option>').val(i.value).text(i.text)));
                select.val(selectedVal !== null && selectedVal !== undefined ? String(selectedVal) : '');
            }
            function uniqueBy(list, key) {
                const seen = new Set();
                return list.filter(i => !seen.has(i[key]) && seen.add(i[key]));
            }
            function fillMajors() {
                fill(majorSel, uniqueBy(assignments, 'major_id').map(a => ({ value: a.major_id ?? '', text: a.major })),
                    current?.major_id, 'Pilih jurusan');
            }
            function fillClassrooms() {
                const majorId = majorSel.val();
                const items = uniqueBy(assignments.filter(a => String(a.major_id ?? '') === majorId), 'classroom_id');
                fill(classSel, items.map(a => ({ value: a.classroom_id, text: a.classroom })),
                    current && String(current.major_id ?? '') === majorId ? current.classroom_id : null, 'Pilih kelas');
                classSel.prop('disabled', majorId === '');
            }
            function fillSubjects() {
                const classroomId = classSel.val();
                const items = assignments.filter(a => String(a.classroom_id) === classroomId);
                fill(subjSel, items.map(a => ({ value: a.id, text: a.subject })),
                    current && String(current.classroom_id) === classroomId ? current.id : null, 'Pilih mapel');
                subjSel.prop('disabled', classroomId === '');
            }
            fillMajors(); fillClassrooms(); fillSubjects();
            majorSel.on('change', function () { fillClassrooms(); fillSubjects(); });
            classSel.on('change', fillSubjects);
            subjSel.on('change', function () {
                $('#teacherSubjectInput').val($(this).val());
                if ($(this).val() !== '') $('#filterForm').trigger('submit');
            });

            function toNumber(value) {
                if (value === '' || value === null || value === undefined) return null;
                const number = parseFloat(String(value).replace(',', '.'));
                return Number.isFinite(number) ? number : null;
            }
            function compose(daily, uts, uas) {
                const parts = [['Harian', daily, settings.daily_weight], ['UTS', uts, settings.uts_weight], ['UAS', uas, settings.uas_weight]];
                let sum = 0, weight = 0;
                const missing = [];
                parts.forEach(([label, value, w]) => {
                    if (w <= 0) return;
                    if (value === null) { missing.push(label); return; }
                    sum += value * w; weight += w;
                });
                return { score: weight > 0 ? Math.round((sum / weight) * 100) / 100 : null, missing: missing };
            }
            function predicate(score) {
                if (score === null) return '-';
                const kkm = settings.kkm, range = (100 - kkm) / 3;
                if (score >= kkm + 2 * range) return 'A';
                if (score >= kkm + range) return 'B';
                if (score >= kkm) return 'C';
                return 'D';
            }
            function roundHalfUp(value) {
                return Math.floor(value + 0.5 + 1e-9);
            }

            let dirty = false;
            $('#examTable').on('input', '.score-input', function () {
                dirty = true;
                const row = $(this).closest('tr');
                const inputs = row.find('.score-input');
                const result = compose(toNumber(row.data('daily')), toNumber(inputs.eq(0).val()), toNumber(inputs.eq(1).val()));
                const rounded = result.score === null ? null : roundHalfUp(result.score);
                row.find('.final-cell')
                    .text(rounded === null ? '-' : rounded)
                    .attr('title', result.score === null ? '' : 'Nilai tepat: ' + result.score);
                row.find('.missing-hint').text(result.score !== null && result.missing.length ? 'Belum ada ' + result.missing.join(', ') : '');
                row.find('.predicate-cell').text(predicate(rounded));
            });

            $('#examForm').on('submit', function () { dirty = false; });
            window.addEventListener('beforeunload', function (event) {
                if (dirty) { event.preventDefault(); event.returnValue = ''; }
            });

            $('#examTable').on('keydown', '.score-input', function (event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                const inputs = $('#examTable .score-input:visible');
                inputs.eq(inputs.index(this) + 2).trigger('focus').trigger('select');
            });

            $('#studentSearch').on('input', function () {
                const keyword = $(this).val().toLowerCase().trim();
                $('#examTable tbody tr[data-search]').each(function () {
                    $(this).toggle(keyword === '' || String($(this).data('search')).includes(keyword));
                });
            });

            $('#btnImport').on('click', function () { $('#importFile').trigger('click'); });
            $('#importFile').on('change', function () {
                if (!this.files.length) return;
                Swal.fire({
                    title: 'Import nilai dari Excel?',
                    html: 'Kolom yang dibaca: <b>NIS</b>, <b>UTS</b>, <b>UAS</b>. Sel kosong tidak mengubah nilai yang sudah ada.<br>Bila ada baris yang salah, seluruh import dibatalkan.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, import',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        dirty = false;
                        $('#importForm').trigger('submit');
                    } else {
                        $('#importFile').val('');
                    }
                });
            });
        });
    </script>
@stop
