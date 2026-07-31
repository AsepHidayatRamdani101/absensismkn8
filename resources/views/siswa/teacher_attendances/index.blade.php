@extends('adminlte::page')

@section('title', 'Absen Guru - Siswa')
@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Absensi Guru</h1>
            <p class="text-muted mb-0">Jika guru tidak mengajukan izin, absensi tetap aktif. Jika guru mengajukan izin,
                absensi aktif setelah disetujui Kurikulum.
            </p>
        </div>
        <span class="badge badge-light border px-3 py-2 mt-2 mt-md-0">
            {{ $today->format('d M Y') }} - {{ $todayDayName }}
        </span>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (!$canSubmitTeacherAttendance)
        <div class="alert alert-warning">
            Hanya siswa dengan jabatan <strong>KM</strong>, <strong>Sekretaris</strong>, atau <strong>Bendahara</strong>
            yang bisa mengisi absensi guru.
        </div>
    @endif

    @if ($isWeekendHoliday)
        <div class="alert alert-info">
            Hari {{ $todayDayName }} otomatis libur. Absensi siswa tidak dibuka.
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <strong>Nama Siswa:</strong> {{ $student->nama_lengkap }}<br>
                <strong>Kelas:</strong> {{ $student->classroom->nama_kelas ?? '-' }}
                ({{ $student->classroom->kode_kelas ?? '-' }})<br>
                <strong>Jabatan:</strong> {{ $student->jabatan_kelas_label }}
            </div>

            <form method="GET" action="{{ route('siswa.teacher-attendances.index') }}" class="mb-3">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="tanggal" class="mb-1">Filter Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control"
                            value="{{ $selectedDate ?? now()->toDateString() }}">
                    </div>
                    <div class="col-md-5 mt-2 mt-md-0">
                        <label for="guru_mapel" class="mb-1">Filter Guru-Mapel</label>
                        <select id="guru_mapel" name="guru_mapel" class="form-control">
                            <option value="0">Semua Guru-Mapel</option>
                            @foreach ($guruMapelOptions as $option)
                                <option value="{{ $option['id'] }}" @selected((int) ($selectedGuruMapelId ?? 0) === (int) $option['id'])>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-primary mr-2">Terapkan</button>
                        <a href="{{ route('siswa.teacher-attendances.index') }}"
                            class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table id="tableSiswaTeacherAttendances" class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Jadwal</th>
                            <th width="20%">Mapel & Guru</th>
                            <th width="12%">Status Guru</th>
                            <th width="14%">Tugas</th>
                            <th width="10%">Foto</th>
                            <th width="10%">Keterangan Guru</th>
                            <th width="18%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scheduleRows as $row)
                            @php
                                $schedule = $row['schedule'];
                                $selectedAction = $row['selected_action'];
                                $teacherAttendance = $row['teacher_attendance'];
                                $fotoGuruPath = $teacherAttendance?->foto_guru_path;
                                $approvedLeave = $row['approved_leave'];
                                $todayLeaveRequest = $row['today_leave_request'];
                                $guruStatus = $row['guru_status'];
                                $canOfficerSubmitForRow = $row['can_officer_submit'];
                                $submissionBlockReason = $row['submission_block_reason'];
                                $isApprovedIzin = $row['is_approved_izin'] ?? false;
                                $tugasDeskripsi =
                                    $teacherAttendance?->tugas_deskripsi ?? $approvedLeave?->deskripsi_tugas;
                                $tugasLampiranPath =
                                    $teacherAttendance?->tugas_file_path ?? $approvedLeave?->lampiran_tugas_path;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="font-weight-semibold">{{ $schedule->hari }}</div>
                                    <small class="text-muted d-block">
                                        {{ substr($schedule->jam_mulai, 0, 5) }} -
                                        {{ substr($schedule->jam_selesai, 0, 5) }}
                                    </small>
                                    <small class="text-muted d-block">Ruang: {{ $schedule->ruangan ?? '-' }}</small>
                                </td>
                                <td>
                                    <div class="font-weight-semibold">
                                        {{ $schedule->teacherSubject->subject->nama_mapel ?? '-' }}</div>
                                    <small
                                        class="text-muted d-block">{{ $schedule->teacherSubject->teacher->nama_lengkap ?? '-' }}</small>
                                </td>
                                <td>
                                    @php
                                        $guruBadgeClass = 'badge-secondary';
                                        $guruTooltip = 'Belum diabsen';
                                        if ($approvedLeave) {
                                            $guruBadgeClass = 'badge-warning';
                                            $guruTooltip = 'Guru izin/cuti';
                                        } elseif ($selectedAction === 'Hadir') {
                                            $guruBadgeClass = 'badge-success';
                                            $guruTooltip = 'Hadir';
                                        } elseif ($selectedAction === 'Tugas') {
                                            $guruBadgeClass = 'badge-info';
                                            $guruTooltip = 'Tugas';
                                        } elseif ($selectedAction === 'Tanpa Keterangan') {
                                            $guruBadgeClass = 'badge-danger';
                                            $guruTooltip = 'Tanpa Keterangan';
                                        }
                                    @endphp
                                    <span class="badge {{ $guruBadgeClass }}" data-toggle="tooltip" data-placement="top"
                                        title="{{ $guruTooltip }}">{{ $guruStatus }}</span>

                                    @if ($todayLeaveRequest)
                                        @php
                                            $pengajuanStatus = (string) ($todayLeaveRequest->status_pengajuan ?? '');
                                            $pengajuanBadgeClass = match ($pengajuanStatus) {
                                                'Disetujui' => 'badge-success',
                                                'Menunggu' => 'badge-warning',
                                                'Ditolak' => 'badge-danger',
                                                default => 'badge-secondary',
                                            };
                                        @endphp
                                        <small class="d-block mt-1">
                                            <span class="badge {{ $pengajuanBadgeClass }}">{{ $pengajuanStatus }}</span>
                                        </small>
                                    @else
                                        <small class="d-block mt-1">
                                            <span class="badge badge-light border">Tanpa Pengajuan</span>
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if ($approvedLeave)
                                        @if (!empty($tugasDeskripsi))
                                            <small
                                                class="d-block">{{ \Illuminate\Support\Str::limit($tugasDeskripsi, 80) }}</small>
                                        @endif

                                        @if (!empty($tugasLampiranPath))
                                            <a href="{{ asset('storage/' . $tugasLampiranPath) }}" target="_blank"
                                                class="btn btn-outline-primary btn-xs mt-1">
                                                <i class="fas fa-paperclip"></i> Lihat Lampiran
                                            </a>
                                        @endif

                                        @if (empty($tugasDeskripsi) && empty($tugasLampiranPath))
                                            <span class="text-muted">Tidak ada tugas</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Belum ada tugas</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($fotoGuruPath)
                                        <a href="{{ asset('storage/' . $fotoGuruPath) }}" target="_blank"
                                            class="d-inline-block">
                                            <img src="{{ asset('storage/' . $fotoGuruPath) }}" alt="Foto guru"
                                                style="width: 62px; height: 62px; object-fit: cover; border-radius: 6px; border: 1px solid #ced4da;">
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($selectedAction === 'Hadir')
                                        <span class="badge badge-success" data-toggle="tooltip" data-placement="top"
                                            title="Hadir">Hadir</span>
                                    @elseif ($selectedAction === 'Tugas')
                                        <span class="badge badge-warning" data-toggle="tooltip" data-placement="top"
                                            title="Tugas">Tugas</span>
                                    @elseif ($selectedAction === 'Tanpa Keterangan')
                                        <span class="badge badge-danger" data-toggle="tooltip" data-placement="top"
                                            title="Tanpa Keterangan">Tanpa Keterangan</span>
                                    @else
                                        <span class="badge badge-secondary" data-toggle="tooltip" data-placement="top"
                                            title="Belum diabsen">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($canOfficerSubmitForRow)
                                        <form action="{{ route('siswa.teacher-attendances.submit', $schedule->id) }}"
                                            method="POST" enctype="multipart/form-data" class="form-absen-guru"
                                            id="form_absen_{{ $schedule->id }}">
                                            @csrf
                                            <input type="file" name="foto_guru" class="d-none foto-input"
                                                id="foto_input_{{ $schedule->id }}" accept="image/*">
                                            <input type="file" id="kamera_input_{{ $schedule->id }}"
                                                name="foto_guru_kamera" class="d-none kamera-input" accept="image/*"
                                                capture="environment">

                                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.3rem;">
                                                <button type="button" class="btn btn-light btn-xs btn-open-kamera"
                                                    data-form-id="form_absen_{{ $schedule->id }}" title="Open Kamera">
                                                    <i class="fas fa-camera"></i>
                                                </button>
                                                <button type="submit" class="btn btn-success btn-xs btn-hadir-submit"
                                                    name="action" disabled value="Hadir">H</button>
                                                <button type="submit" class="btn btn-warning btn-xs" name="action"
                                                    value="Tugas">T</button>
                                                <button type="submit" class="btn btn-danger btn-xs" name="action"
                                                    value="Tanpa Keterangan">TK</button>
                                            </div>

                                            <small class="text-muted d-block mt-1 kamera-file-label"
                                                id="kamera_label_{{ $schedule->id }}">Gunakan ikon kamera.</small>
                                            <small class="text-muted d-block upload-status-label"
                                                id="status_label_{{ $schedule->id }}">Hadir butuh foto.</small>
                                        </form>
                                    @else
                                        @if (!$canSubmitTeacherAttendance)
                                            <span class="text-muted">Tidak memiliki akses</span>
                                        @elseif ($isApprovedIzin)
                                            <span class="text-muted">Tanpa aksi, guru izin sudah disetujui</span>
                                        @else
                                            <span
                                                class="text-muted">{{ $submissionBlockReason ?? 'Belum dapat melakukan absensi' }}</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    @if ($isWeekendHoliday)
                                        Hari {{ $todayDayName }} libur otomatis.
                                    @else
                                        Tidak ada jadwal untuk hari ini.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kameraModal" tabindex="-1" aria-hidden="true" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title mb-0">Ambil Foto Guru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                        id="btnCloseKameraModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <video id="kameraPreview" autoplay playsinline muted
                        style="width: 100%; max-height: 60vh; background: #111; border-radius: 8px;"></video>
                    <small id="kameraPreviewHint" class="text-muted d-block mt-2">
                        Arahkan kamera ke guru, lalu tekan Ambil Foto.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        id="btnBatalKamera">Batal</button>
                    <button type="button" class="btn btn-info" id="btnAmbilFotoKamera">
                        <i class="fas fa-camera"></i> Ambil Foto
                    </button>
                </div>
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
            $('#tableSiswaTeacherAttendances').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                },
                columnDefs: [{
                    orderable: false,
                    targets: [4, 5, 6, 7]
                }],
                initComplete: function() {
                    $('[data-toggle="tooltip"]').tooltip();
                }
            });

            let kameraStream = null;
            let activeForm = null;

            function stopKameraStream() {
                if (!kameraStream) {
                    return;
                }

                kameraStream.getTracks().forEach(track => track.stop());
                kameraStream = null;

                const video = document.getElementById('kameraPreview');
                if (video) {
                    video.srcObject = null;
                }
            }

            function syncHadirButton(formElement) {
                const form = $(formElement);
                const hasGalleryFile = form.find('input[name="foto_guru"]')[0]?.files?.length > 0;
                const hasCameraFile = form.find('input[name="foto_guru_kamera"]')[0]?.files?.length > 0;
                const enableHadir = hasGalleryFile || hasCameraFile;

                form.find('.btn-hadir-submit').prop('disabled', !enableHadir);

                const statusLabel = form.find('.upload-status-label');
                if (statusLabel.length) {
                    statusLabel.text(enableHadir ? 'Foto siap. Tombol Hadir sudah aktif.' :
                        'Hadir butuh foto.');
                }
            }

            $('.form-absen-guru').each(function() {
                syncHadirButton(this);
            });

            async function openLiveKamera(formElement) {
                activeForm = $(formElement);
                $('#kameraPreviewHint').text('Arahkan kamera ke guru, lalu tekan Ambil Foto.');

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    const fallbackInput = activeForm.find('input[name="foto_guru_kamera"]')[0];
                    if (fallbackInput) {
                        fallbackInput.click();
                    }
                    return;
                }

                try {
                    kameraStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: {
                                ideal: 'environment'
                            }
                        },
                        audio: false
                    });

                    const video = document.getElementById('kameraPreview');
                    video.srcObject = kameraStream;
                    $('#kameraModal').modal('show');
                } catch (error) {
                    const fallbackInput = activeForm.find('input[name="foto_guru_kamera"]')[0];
                    if (fallbackInput) {
                        fallbackInput.click();
                    }

                    const statusLabel = activeForm.find('.upload-status-label');
                    if (statusLabel.length) {
                        statusLabel.text(
                            'Tidak bisa membuka kamera langsung. Coba izinkan akses kamera browser lalu ulangi.'
                        );
                    }
                }
            }

            $(document).on('click', '.btn-open-kamera', function() {
                const formId = $(this).data('form-id');
                const form = document.getElementById(formId);

                if (!form) {
                    return;
                }

                openLiveKamera(form);
            });

            $('#btnAmbilFotoKamera').on('click', function() {
                if (!activeForm || !activeForm.length) {
                    return;
                }

                const video = document.getElementById('kameraPreview');
                if (!video || !video.videoWidth || !video.videoHeight) {
                    $('#kameraPreviewHint').text('Kamera belum siap. Tunggu sebentar lalu coba lagi.');
                    return;
                }

                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                const context = canvas.getContext('2d');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                canvas.toBlob(function(blob) {
                    if (!blob) {
                        $('#kameraPreviewHint').text('Gagal mengambil foto. Coba ulangi.');
                        return;
                    }

                    const cameraInput = activeForm.find('input[name="foto_guru_kamera"]')[0];
                    if (!cameraInput) {
                        return;
                    }

                    const fileName = 'foto-guru-' + Date.now() + '.jpg';
                    const file = new File([blob], fileName, {
                        type: 'image/jpeg'
                    });

                    if (typeof DataTransfer !== 'undefined') {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        cameraInput.files = dt.files;
                    }

                    $(cameraInput).trigger('change');
                    $('#kameraModal').modal('hide');
                }, 'image/jpeg', 0.92);
            });

            $('#kameraModal').on('hidden.bs.modal', function() {
                stopKameraStream();
            });

            $('#btnCloseKameraModal, #btnBatalKamera').on('click', function() {
                stopKameraStream();
            });

            $(document).on('change', '.kamera-input', function() {
                const fileName = this.files && this.files.length ? this.files[0].name : null;
                const labelId = this.id.replace('input', 'label');
                const label = document.getElementById(labelId);

                if (!label) {
                    return;
                }

                if (fileName) {
                    label.textContent = 'Foto kamera dipilih: ' + fileName;
                } else {
                    label.textContent = 'Tekan Open Kamera agar kamera langsung terbuka.';
                }

                syncHadirButton($(this).closest('form'));
            });

            $(document).on('change', '.foto-input', function() {
                syncHadirButton($(this).closest('form'));
            });
        });
    </script>
@stop
