@extends('adminlte::page')

@include('guru.partials.mobile-ux')

@section('title', 'Agenda Guru')

@section('plugins.Datatables', true)

@push('css')
    <style>
        #tableGuruAgenda .btn-open-agenda-modal {
            font-weight: 700;
            border-radius: .5rem;
        }

        .agenda-mobile-hint {
            border-radius: .6rem;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1e3a8a;
        }

        @media (max-width: 767.98px) {

            #tableGuruAgenda th,
            #tableGuruAgenda td {
                vertical-align: middle;
            }

            #tableGuruAgenda td:last-child {
                min-width: 142px;
            }

            #tableGuruAgenda .btn-open-agenda-modal {
                width: 100%;
                min-height: 40px;
                box-shadow: 0 6px 14px rgba(37, 99, 235, .22);
            }
        }
    </style>
@endpush

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Agenda Guru</h1>
            <p class="text-muted mb-0">{{ $today->format('d M Y') }} - {{ $todayDayName ?? '-' }}</p>
        </div>
    </div>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($isWeekendHoliday)
        <div class="alert alert-info">
            Hari {{ $todayDayName }} adalah hari libur. Agenda guru tidak tersedia.
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="alert agenda-mobile-hint d-md-none py-2 px-3 mb-3">
                Fokus mobile: gunakan tombol <strong>Isi Agenda</strong> untuk input cepat setiap jam pelajaran.
            </div>

            <div class="table-responsive">
                <table id="tableGuruAgenda" class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Jam Ke-</th>
                            <th>Mata Pelajaran</th>
                            <th>Kelas</th>
                            <th>Nama Guru</th>
                            <th>Materi</th>
                            <th>Kehadiran Guru</th>
                            <th>Tugas</th>
                            <th>Jml Sis HDR</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $schedule = $row['schedule'];
                                $teacherAttendance = $row['teacher_attendance'];
                                $subjectName = $schedule->teacherSubject->subject->nama_mapel ?? '-';
                                $classroomName = $schedule->teacherSubject->classroom->nama_kelas ?? '-';
                                $materi = $teacherAttendance->materi_pembelajaran ?? '-';
                                $kehadiranGuru = $teacherAttendance->kehadiran_guru ?? 'Hadir';
                                $tugasPath = $teacherAttendance->tugas_file_path ?? null;
                                $tugasDeskripsi = $teacherAttendance->tugas_deskripsi ?? null;
                            @endphp
                            <tr>
                                <td>{{ $row['jam_ke'] }}</td>
                                <td>{{ $subjectName }}</td>
                                <td>{{ $classroomName }}</td>
                                <td>{{ $teacher->nama_lengkap }}</td>
                                <td>{{ $materi }}</td>
                                <td>{{ $kehadiranGuru }}</td>
                                <td>
                                    @if ($tugasPath)
                                        <a href="{{ asset('storage/' . $tugasPath) }}" target="_blank"
                                            class="btn btn-xs btn-outline-primary">
                                            Lihat File
                                        </a>
                                    @endif

                                    @if ($tugasDeskripsi)
                                        <div class="small text-muted mt-1">
                                            {{ \Illuminate\Support\Str::limit($tugasDeskripsi, 90) }}</div>
                                    @endif

                                    @if (!$tugasPath && !$tugasDeskripsi)
                                        -
                                    @endif
                                </td>
                                <td>{{ $row['jml_siswa_hadir'] }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary btn-open-agenda-modal"
                                        data-action="{{ route('guru.agenda.store', $schedule->id) }}"
                                        data-jam-ke="{{ $row['jam_ke'] }}" data-mapel="{{ $subjectName }}"
                                        data-kelas="{{ $classroomName }}" data-guru="{{ $teacher->nama_lengkap }}"
                                        data-materi="{{ $teacherAttendance->materi_pembelajaran ?? '' }}"
                                        data-catatan="{{ $teacherAttendance->catatan_guru ?? '' }}"
                                        data-kehadiran="{{ $kehadiranGuru }}" data-has-file="{{ $tugasPath ? '1' : '0' }}"
                                        data-has-deskripsi="{{ $tugasDeskripsi ? '1' : '0' }}"
                                        data-tugas-deskripsi="{{ $tugasDeskripsi ?? '' }}"
                                        @if ($isWeekendHoliday) disabled @endif>
                                        <i class="fas fa-pen mr-1"></i>
                                        Isi Agenda
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-3">Tidak ada jadwal mengajar untuk hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="agendaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Isi Agenda Guru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" id="agendaForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Jam Ke-</label>
                                <input type="text" id="agenda_jam_ke" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Mata Pelajaran</label>
                                <input type="text" id="agenda_mapel" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Kelas</label>
                                <input type="text" id="agenda_kelas" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Nama Guru</label>
                                <input type="text" id="agenda_guru" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Kehadiran Guru</label>
                            <select name="kehadiran_guru" id="agenda_kehadiran" class="form-control" required>
                                <option value="Hadir">Hadir</option>
                                <option value="Izin">Izin</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Dinas Luar">Dinas Luar</option>
                                <option value="Home Visit">Home Visit</option>
                            </select>
                            <small class="text-muted">Tugas wajib (file/deskripsi) untuk semua status selain Cuti.</small>
                        </div>

                        <div class="form-group">
                            <label>Materi</label>
                            <textarea name="materi_pembelajaran" id="agenda_materi" rows="4" class="form-control"
                                placeholder="Contoh: Persamaan Linear Dua Variabel"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Catatan Guru (opsional)</label>
                            <textarea name="catatan_guru" id="agenda_catatan" rows="2" class="form-control"
                                placeholder="Contoh: Kelas kondusif, lanjut latihan pertemuan berikutnya."></textarea>
                        </div>

                        <div class="form-group mb-0">
                            <label>Upload Tugas</label>
                            <input type="file" name="tugas_file" id="agenda_tugas_file" class="form-control-file"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>

                        <div class="form-group mt-3 mb-0">
                            <label>Deskripsi Tugas</label>
                            <textarea name="tugas_deskripsi" id="agenda_tugas_deskripsi" rows="3" class="form-control"
                                placeholder="Boleh diisi jika tugas tidak diupload sebagai file."></textarea>
                            <small id="agenda_file_hint" class="text-muted d-block mt-1"></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Agenda</button>
                    </div>
                </form>
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
            let isMobile = window.matchMedia('(max-width: 767.98px)').matches;

            $('#tableGuruAgenda').DataTable({
                responsive: !isMobile,
                scrollX: isMobile,
                autoWidth: false,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                },
                columnDefs: [{
                        orderable: false,
                        targets: [8]
                    },
                    ...(isMobile ? [{
                        targets: [3, 4, 5, 6, 7],
                        visible: false
                    }] : [])
                ]
            });

            function syncTugasRequirement() {
                let selectedStatus = $('#agenda_kehadiran').val();
                let isTaskRequired = selectedStatus && selectedStatus !== 'Cuti';
                let hasExistingFile = $('#agendaModal').data('has-file') === 1;
                let hasExistingDescription = $('#agendaModal').data('has-description') === 1;
                let hasTypedDescription = $.trim($('#agenda_tugas_deskripsi').val()).length > 0;

                $('#agenda_tugas_file').prop('required', false);
                $('#agenda_tugas_deskripsi').prop('required', false);

                if (isTaskRequired) {
                    if (hasExistingFile || hasExistingDescription || hasTypedDescription) {
                        $('#agenda_file_hint').text(
                            'Tugas sudah tersedia. Anda bisa update file atau deskripsi jika perlu.');
                    } else {
                        $('#agenda_file_hint').text('Wajib isi salah satu tugas (file atau deskripsi teks).');
                    }
                } else {
                    $('#agenda_file_hint').text('Untuk Cuti, tugas tidak diwajibkan.');
                }
            }

            $(document).on('click', '.btn-open-agenda-modal', function() {
                let btn = $(this);

                $('#agendaForm').attr('action', btn.data('action'));
                $('#agenda_jam_ke').val(btn.data('jam-ke'));
                $('#agenda_mapel').val(btn.data('mapel'));
                $('#agenda_kelas').val(btn.data('kelas'));
                $('#agenda_guru').val(btn.data('guru'));
                $('#agenda_materi').val(btn.data('materi'));
                $('#agenda_catatan').val(btn.data('catatan'));
                $('#agenda_kehadiran').val(btn.data('kehadiran'));
                $('#agenda_tugas_file').val('');
                $('#agenda_tugas_deskripsi').val(btn.data('tugas-deskripsi'));

                $('#agendaModal').data('has-file', Number(btn.data('has-file')));
                $('#agendaModal').data('has-description', Number(btn.data('has-deskripsi')));
                syncTugasRequirement();
                $('#agendaModal').modal('show');
            });

            $('#agenda_kehadiran').on('change', syncTugasRequirement);
            $('#agenda_tugas_deskripsi').on('input', syncTugasRequirement);
        });
    </script>
@stop
