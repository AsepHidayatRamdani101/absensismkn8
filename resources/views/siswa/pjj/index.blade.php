@extends('adminlte::page')

@section('title', 'Tugas dan Absensi PJJ')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">Pembelajaran PJJ</h1>
            <p class="text-muted mb-0">{{ $student->nama_lengkap }} · {{ $student->classroom->nama_kelas }}</p>
        </div>
        <a href="{{ route('siswa.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Dashboard Siswa</a>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><strong>Permintaan belum berhasil:</strong><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div id="pjj-submission-notice" class="alert d-none" role="alert"></div>

    @php
        $checkInTime = $todayAttendance?->check_in_at ?? $todayAttendance?->created_at;
        $checkOutTime = $todayAttendance?->check_out_at;
        $attendanceComplete = $todayAttendance && $checkOutTime;
        $attendanceMode = $todayAttendance ? 'pulang' : 'datang';
        $isLateToday = $todayAttendance?->status === 'Terlambat';
    @endphp

    <div class="card card-info card-outline">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-camera mr-1"></i> Absensi Selfie Hari Ini</h3></div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-sm-6 mb-2 mb-sm-0">
                    <div class="border rounded p-2 h-100 {{ $todayAttendance ? 'border-success bg-light' : '' }}">
                        <div class="text-muted small"><i class="fas fa-sign-in-alt mr-1"></i> Absen Datang <span class="text-muted">(tepat waktu maks. 06.30)</span></div>
                        @if ($todayAttendance)
                            <strong class="{{ $isLateToday ? 'text-warning' : 'text-success' }}">Tercatat pukul {{ $checkInTime->format('H:i') }}</strong>
                            @if ($isLateToday)
                                <span class="badge badge-warning ml-1">Terlambat</span>
                            @endif
                        @else
                            <strong class="text-warning">Belum absen datang</strong>
                        @endif
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="border rounded p-2 h-100 {{ $checkOutTime ? 'border-success bg-light' : '' }}">
                        <div class="text-muted small"><i class="fas fa-sign-out-alt mr-1"></i> Absen Pulang <span class="text-muted">(mulai 15.00)</span></div>
                        @if ($checkOutTime)
                            <strong class="text-success">Tercatat pukul {{ $checkOutTime->format('H:i') }}</strong>
                        @elseif ($todayAttendance)
                            <strong class="text-warning">Belum absen pulang</strong>
                        @else
                            <strong class="text-muted">Menunggu absen datang</strong>
                        @endif
                    </div>
                </div>
            </div>

            @if ($attendanceComplete)
                <div class="d-flex align-items-center">
                    <span class="text-success mr-3"><i class="fas fa-check-circle fa-2x"></i></span>
                    <div><strong>Absensi hari ini sudah lengkap.</strong><div class="text-muted">Datang pukul {{ $checkInTime->format('H:i') }} · Pulang pukul {{ $checkOutTime->format('H:i') }}.</div></div>
                </div>
            @else
                <form id="pjj-attendance-form" method="POST" action="{{ route('siswa.pjj.attendance') }}">
                    @csrf
                    <div class="form-group">
                        <label for="pjj-camera">Kamera selfie (wajib kamera langsung)</label>
                        <video id="pjj-camera" class="d-block w-100 bg-dark rounded" autoplay muted playsinline style="max-width:480px; min-height:180px;"></video>
                        <canvas id="pjj-camera-canvas" class="d-none"></canvas>
                        <img id="pjj-selfie-preview" class="d-none img-thumbnail mt-2" alt="Pratinjau selfie" style="max-width:240px;">
                        <input id="selfie-data" type="hidden" name="selfie_data">
                        <input id="pjj-latitude" type="hidden" name="latitude">
                        <input id="pjj-longitude" type="hidden" name="longitude">
                        <small id="compression-status" class="form-text text-muted">Izinkan akses kamera dan lokasi. Foto dari galeri tidak dapat digunakan.</small>
                    </div>
                    <div class="form-group">
                        <button type="button" id="capture-selfie" class="btn btn-outline-primary btn-sm" disabled><i class="fas fa-camera mr-1"></i> Ambil selfie</button>
                        <button type="button" id="retake-selfie" class="btn btn-outline-secondary btn-sm d-none"><i class="fas fa-redo mr-1"></i> Ulangi</button>
                        <button type="submit" id="attendance-submit" class="btn btn-primary btn-sm" disabled>
                            <i class="fas fa-paper-plane mr-1"></i> Kirim Absen {{ ucfirst($attendanceMode) }}
                        </button>
                    </div>
                    <div class="text-muted small">
                        @if ($attendanceMode === 'datang')
                            Absen datang tepat waktu maksimal pukul <strong>06.30</strong>; setelah itu tetap tercatat hadir dengan status <strong>Terlambat</strong>.
                            Jika tidak absen sama sekali dalam satu hari, Anda dianggap <strong>Alpa</strong>. Absen pulang dibuka mulai pukul <strong>15.00</strong>.
                        @else
                            Absen pulang hanya dapat dikirim mulai pukul <strong>15.00</strong>.
                        @endif
                        Absensi memerlukan lokasi perangkat.
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="card card-outline card-success">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i> Rekap Absensi Saya</h3></div>
        <div class="card-body">
            @if ($attendanceHistory->isEmpty())
                <p class="text-muted mb-0">Belum ada riwayat absensi.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm mb-0">
                        <thead><tr><th>Tanggal</th><th>Jam Datang</th><th>Jam Pulang</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($attendanceHistory as $entry)
                                @php
                                    $historyAttendance = $entry['attendance'];
                                    $historyIn = $historyAttendance?->check_in_at ?? $historyAttendance?->created_at;
                                    $historyOut = $historyAttendance?->check_out_at;
                                    $isHistoryToday = $entry['date']->isToday();
                                @endphp
                                <tr>
                                    <td>{{ $entry['date']->format('d-m-Y') }}</td>
                                    <td>{{ $historyIn?->format('H:i') ?? '—' }}</td>
                                    <td>{{ $historyOut?->format('H:i') ?? '—' }}</td>
                                    <td>
                                        @if (!$historyAttendance)
                                            @if ($isHistoryToday)
                                                <span class="badge badge-secondary">Belum absen</span>
                                            @else
                                                <span class="badge badge-danger">Alpa</span>
                                            @endif
                                        @else
                                            @if ($historyAttendance->status === 'Terlambat')
                                                <span class="badge badge-warning">Terlambat</span>
                                            @else
                                                <span class="badge badge-success">Hadir</span>
                                            @endif
                                            @if (!$historyOut)
                                                <span class="badge badge-secondary">Belum absen pulang</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mt-2">Menampilkan maksimal 30 hari PJJ terakhir sebagai bukti kehadiran. Tidak absen seharian penuh = Alpa.</small>
            @endif
        </div>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-book mr-1"></i> Daftar Tugas</h3></div>
        <div class="card-body">
            <form id="pjj-student-filter-form" class="form-row align-items-end">
                <div class="form-group col-lg-2 col-md-4">
                    <label for="student-date-from">Dibuat dari</label>
                    <select id="student-date-from" name="date_from" class="form-control form-control-sm">
                        <option value="">Semua tanggal PJJ</option>
                        @foreach ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}">{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label for="student-date-to">Sampai</label>
                    <select id="student-date-to" name="date_to" class="form-control form-control-sm">
                        <option value="">Semua tanggal PJJ</option>
                        @foreach ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}">{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-3 col-md-4">
                    <label for="student-subject">Mata pelajaran</label>
                    <select id="student-subject" name="subject_id" class="form-control form-control-sm">
                        <option value="">Semua mata pelajaran</option>
                        @foreach ($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->nama_mapel }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group col-lg-3 col-md-4">
                    <label for="student-submission-status">Status tugas</label>
                    <select id="student-submission-status" name="submission_status" class="form-control form-control-sm">
                        <option value="">Semua status</option><option value="pending">Belum dikumpulkan</option><option value="submitted">Sudah dikumpulkan</option><option value="graded">Sudah dinilai</option>
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <button type="button" id="student-filter-apply" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                    <button type="button" id="student-filter-reset" class="btn btn-outline-secondary btn-sm">Reset</button>
                </div>
            </form>
            <div class="table-responsive">
                <table id="pjj-student-assignments" class="table table-bordered table-striped table-hover w-100">
                    <thead><tr><th>Tanggal</th><th>Mata Pelajaran</th><th>Guru</th><th>Tugas</th><th>Deadline</th><th>Status</th><th>Materi</th><th>Aksi</th></tr></thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pjj-submission-modal" tabindex="-1" role="dialog" aria-labelledby="pjj-submission-title" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form id="pjj-submission-form" method="POST" enctype="multipart/form-data" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 id="pjj-submission-title" class="modal-title">Kumpulkan Tugas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Jawaban sebelumnya akan diperbarui jika Anda mengirim ulang.</p>
                    <div id="pjj-existing-answer" class="alert alert-light border d-none"></div>
                    <div id="pjj-assignment-material" class="mb-3"></div>
                    <div class="form-group">
                        <label for="pjj-answer">Jawaban teks (opsional jika melampirkan file)</label>
                        <textarea id="pjj-answer" name="answer" class="form-control" rows="4" maxlength="10000"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label for="pjj-answer-file">File jawaban (PDF atau gambar; min. 1, maks. 10 file, 10 MB per file)</label>
                        <input id="pjj-answer-file" type="file" name="attachments[]" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple>
                        <small id="pjj-file-status" class="form-text text-muted">Gambar akan dikompresi otomatis agar tugas lebih cepat terkirim.</small>
                        <ul id="pjj-file-list" class="list-unstyled small mb-0 mt-1"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button class="btn btn-success btn-sm"><i class="fas fa-paper-plane mr-1"></i> Kirim jawaban</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="pjj-text-modal" tabindex="-1" role="dialog" aria-labelledby="pjj-text-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="pjj-text-modal-title" class="modal-title">Detail Tugas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p id="pjj-text-modal-body" class="mb-0" style="white-space: pre-wrap; word-break: break-word;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function () {
            const form = $('#pjj-student-filter-form');
            const table = $('#pjj-student-assignments').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: @json(route('siswa.pjj.index')),
                    data: function (data) { form.serializeArray().forEach(function (item) { data[item.name] = item.value; }); }
                },
                columns: [
                    { data: 'date', name: 'date' },
                    { data: 'subject', name: 'subject', orderable: false },
                    { data: 'teacher', name: 'teacher', orderable: false },
                    { data: 'title', name: 'title' },
                    { data: 'deadline', name: 'deadline' },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'material', name: 'material', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [],
                language: {
                    processing: 'Memuat data...', search: 'Cari tugas:', lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan', emptyTable: 'Belum ada tugas untuk kelas Anda',
                    paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                }
            });
            $('#student-filter-apply').on('click', function () { table.ajax.reload(); });
            $('#student-filter-reset').on('click', function () { form[0].reset(); table.ajax.reload(); });
            form.on('change', 'input, select', function () { table.ajax.reload(); });
            const MAX_FILES = 10;
            const MAX_FILE_SIZE = 10 * 1024 * 1024;
            const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
            const fileInput = document.getElementById('pjj-answer-file');
            const fileStatus = document.getElementById('pjj-file-status');
            const fileList = document.getElementById('pjj-file-list');
            let preparedFiles = [];

            function formatSize(bytes) {
                return bytes >= 1024 * 1024
                    ? (bytes / (1024 * 1024)).toFixed(1) + ' MB'
                    : Math.ceil(bytes / 1024) + ' KB';
            }

            function renderFileList() {
                fileList.innerHTML = '';
                preparedFiles.forEach(function (file, index) {
                    $('<li>').text((index + 1) + '. ' + file.name + ' (' + formatSize(file.size) + ')').appendTo(fileList);
                });
            }

            // Kompresi gambar di browser: resize maks 1600px + JPEG 80% sebelum upload.
            function compressImage(file) {
                return new Promise(function (resolve) {
                    const objectUrl = URL.createObjectURL(file);
                    const image = new Image();
                    image.onload = function () {
                        const scale = Math.min(1, 1600 / Math.max(image.width, image.height));
                        const canvas = document.createElement('canvas');
                        canvas.width = Math.max(1, Math.round(image.width * scale));
                        canvas.height = Math.max(1, Math.round(image.height * scale));
                        canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
                        canvas.toBlob(function (blob) {
                            URL.revokeObjectURL(objectUrl);
                            // Gunakan hasil kompresi hanya jika memang lebih kecil.
                            if (blob && blob.size < file.size) {
                                resolve(new File([blob], file.name.replace(/\.(png|webp|jpeg|jpg)$/i, '.jpg'), { type: 'image/jpeg' }));
                            } else {
                                resolve(file);
                            }
                        }, 'image/jpeg', 0.8);
                    };
                    image.onerror = function () { URL.revokeObjectURL(objectUrl); resolve(file); };
                    image.src = objectUrl;
                });
            }

            fileInput.addEventListener('change', async function () {
                const selected = Array.from(fileInput.files || []);
                preparedFiles = [];
                fileList.innerHTML = '';

                if (!selected.length) {
                    fileStatus.textContent = 'Gambar akan dikompresi otomatis agar tugas lebih cepat terkirim.';
                    return;
                }
                if (selected.length > MAX_FILES) {
                    fileInput.value = '';
                    fileStatus.textContent = 'Maksimal ' + MAX_FILES + ' file per tugas. Silakan pilih ulang.';
                    return;
                }
                const invalid = selected.find(function (file) { return ALLOWED_TYPES.indexOf(file.type) === -1; });
                if (invalid) {
                    fileInput.value = '';
                    fileStatus.textContent = 'File "' + invalid.name + '" tidak didukung. Gunakan PDF atau gambar (JPG/PNG/WEBP).';
                    return;
                }

                fileStatus.textContent = 'Memproses ' + selected.length + ' file...';
                // Kompresi seluruh gambar secara paralel.
                preparedFiles = await Promise.all(selected.map(function (file) {
                    return file.type.indexOf('image/') === 0 ? compressImage(file) : Promise.resolve(file);
                }));

                const oversize = preparedFiles.find(function (file) { return file.size > MAX_FILE_SIZE; });
                if (oversize) {
                    preparedFiles = [];
                    fileInput.value = '';
                    fileStatus.textContent = 'File "' + oversize.name + '" melebihi 10 MB setelah kompresi. Perkecil file tersebut.';
                    return;
                }

                renderFileList();
                fileStatus.textContent = preparedFiles.length + ' file siap dikirim.';
            });

            $('#pjj-submission-form').on('submit', function (event) {
                event.preventDefault();
                const submissionForm = this;
                const submitButton = $(submissionForm).find('button[type="submit"], button:not([type])');

                if (!$('#pjj-answer').val().trim() && !preparedFiles.length) {
                    $('#pjj-existing-answer').removeClass('d-none alert-light').addClass('alert-danger')
                        .text('Isi jawaban teks atau lampirkan minimal 1 file (maksimal 10 file).');
                    return;
                }

                // Bangun FormData manual agar memakai file hasil kompresi, bukan file asli.
                const formData = new FormData(submissionForm);
                formData.delete('attachments[]');
                preparedFiles.forEach(function (file) { formData.append('attachments[]', file, file.name); });

                submitButton.prop('disabled', true);
                fileStatus.textContent = preparedFiles.length ? 'Mengunggah ' + preparedFiles.length + ' file...' : '';
                $.ajax({
                    url: submissionForm.action,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { Accept: 'application/json' }
                }).done(function (response) {
                    preparedFiles = [];
                    fileList.innerHTML = '';
                    $('#pjj-submission-modal').modal('hide');
                    $('#pjj-submission-notice').removeClass('d-none alert-danger').addClass('alert-success').text(response.message);
                    table.ajax.reload(null, false);
                }).fail(function (xhr) {
                    const messages = xhr.responseJSON?.errors
                        ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                        : (xhr.responseJSON?.message || 'Tugas gagal dikirim. Silakan periksa kembali jawaban.');
                    $('#pjj-existing-answer').removeClass('d-none alert-light').addClass('alert-danger').text(messages);
                    fileStatus.textContent = preparedFiles.length ? preparedFiles.length + ' file siap dikirim.' : '';
                }).always(function () {
                    submitButton.prop('disabled', false);
                });
            });

            $(document).on('click', '.pjj-view-text', function () {
                const button = $(this);
                $('#pjj-text-modal-title').text(button.attr('data-title') || 'Detail Tugas');
                $('#pjj-text-modal-body').text(button.attr('data-text') || '');
                $('#pjj-text-modal').modal('show');
            });

            $(document).on('click', '.pjj-open-submission', function () {
                const button = $(this);
                const existingAnswer = button.attr('data-answer') || '';
                $('#pjj-submission-form').attr('action', button.attr('data-url'));
                $('#pjj-submission-title').text('Kumpulkan: ' + (button.attr('data-title') || 'Tugas'));
                $('#pjj-answer').val(existingAnswer);
                $('#pjj-answer-file').val('');
                preparedFiles = [];
                fileList.innerHTML = '';
                fileStatus.textContent = 'Gambar akan dikompresi otomatis agar tugas lebih cepat terkirim.';
                $('#pjj-existing-answer').removeClass('alert-danger').addClass('alert-light')
                    .toggleClass('d-none', !existingAnswer).text(existingAnswer ? 'Jawaban terakhir: ' + existingAnswer : '');
                const materialUrl = button.attr('data-attachment');
                $('#pjj-assignment-material').html(materialUrl ? $('<a>', { href: materialUrl, text: 'Lihat materi/lampiran', class: 'btn btn-xs btn-outline-secondary' }) : '');
                $('#pjj-submission-modal').modal('show');
            });

            const attendanceForm = document.getElementById('pjj-attendance-form');
            if (!attendanceForm) return;
            const video = document.getElementById('pjj-camera');
            const canvas = document.getElementById('pjj-camera-canvas');
            const selfieData = document.getElementById('selfie-data');
            const latitudeInput = document.getElementById('pjj-latitude');
            const longitudeInput = document.getElementById('pjj-longitude');
            const preview = document.getElementById('pjj-selfie-preview');
            const captureButton = document.getElementById('capture-selfie');
            const retakeButton = document.getElementById('retake-selfie');
            const button = document.getElementById('attendance-submit');
            const status = document.getElementById('compression-status');
            let cameraStream = null;

            async function startCamera() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    status.textContent = 'Kamera langsung memerlukan browser yang mendukung akses kamera melalui HTTPS.';
                    return;
                }

                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: { facingMode: 'user' }
                    });
                    video.srcObject = cameraStream;
                    await video.play();
                    captureButton.disabled = false;
                    status.textContent = 'Kamera aktif. Ambil foto langsung untuk melanjutkan.';
                } catch (error) {
                    status.textContent = error.name === 'NotAllowedError'
                        ? 'Izin kamera ditolak. Izinkan akses kamera lalu muat ulang halaman.'
                        : 'Kamera tidak dapat diakses. Pastikan kamera tersedia dan halaman dibuka melalui HTTPS.';
                }
            }

            captureButton.addEventListener('click', function () {
                if (!cameraStream || !video.videoWidth || !video.videoHeight) {
                    status.textContent = 'Kamera belum siap. Tunggu sebentar lalu coba lagi.';
                    return;
                }

                const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
                canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
                canvas.height = Math.max(1, Math.round(video.videoHeight * scale));
                const context = canvas.getContext('2d');
                if (!context) {
                    status.textContent = 'Kamera tidak dapat mengambil foto. Muat ulang halaman dan coba lagi.';
                    return;
                }

                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                selfieData.value = canvas.toDataURL('image/jpeg', 0.82);
                preview.src = selfieData.value;
                preview.classList.remove('d-none');
                captureButton.classList.add('d-none');
                retakeButton.classList.remove('d-none');
                button.disabled = false;
                status.textContent = 'Foto diambil langsung dari kamera. Ambil ulang jika wajah belum terlihat jelas.';
            });

            retakeButton.addEventListener('click', function () {
                selfieData.value = '';
                latitudeInput.value = '';
                longitudeInput.value = '';
                preview.removeAttribute('src');
                preview.classList.add('d-none');
                retakeButton.classList.add('d-none');
                captureButton.classList.remove('d-none');
                button.disabled = true;
                status.textContent = 'Ambil selfie langsung dari kamera untuk melanjutkan.';
            });

            attendanceForm.addEventListener('submit', function (event) {
                if (!selfieData.value) {
                    event.preventDefault();
                    status.textContent = 'Ambil selfie langsung dari kamera sebelum mengirim absensi.';
                    return;
                }

                event.preventDefault();
                if (!navigator.geolocation) {
                    status.textContent = 'Browser tidak mendukung lokasi. Izinkan lokasi atau gunakan browser lain.';
                    return;
                }

                button.disabled = true;
                status.textContent = 'Meminta lokasi perangkat...';
                navigator.geolocation.getCurrentPosition(function (position) {
                    latitudeInput.value = String(position.coords.latitude);
                    longitudeInput.value = String(position.coords.longitude);
                    status.textContent = 'Lokasi berhasil diperoleh. Mengirim absensi...';
                    HTMLFormElement.prototype.submit.call(attendanceForm);
                }, function () {
                    button.disabled = false;
                    status.textContent = 'Lokasi tidak berhasil diperoleh. Izinkan akses lokasi lalu kirim kembali.';
                }, {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: 15000
                });
            });

            window.addEventListener('beforeunload', function () {
                if (cameraStream) {
                    cameraStream.getTracks().forEach(function (track) { track.stop(); });
                }
            });

            startCamera();
        });
    </script>
@stop
