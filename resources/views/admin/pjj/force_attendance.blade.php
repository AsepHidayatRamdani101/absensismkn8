@extends('adminlte::page')

@section('title', 'Force Absensi PJJ')

@section('plugins.Sweetalert2', true)

@section('css')
    <style>
        #force-student-table tbody td {
            vertical-align: middle;
        }

        #force-student-table tr.row-checked {
            background: #eff6ff;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-0">Force Absensi Siswa PJJ</h1>
            <p class="text-muted mb-0">Absenkan siswa yang terkendala perangkat/jaringan saat pembelajaran PJJ. Absensi tersinkron otomatis ke absensi reguler.</p>
        </div>
        <a href="{{ route('admin.pjj.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Dashboard PJJ</a>
    </div>
@stop

@section('content')
    @if (empty($pjjDates))
        <div class="alert alert-warning"><i class="fas fa-info-circle mr-1"></i> Belum ada tanggal PJJ yang diatur pada kalender sekolah. Atur tanggal PJJ terlebih dahulu di menu Hari Libur / Kalender.</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i> Pilih Tanggal PJJ dan Kelas</h3></div>
        <div class="card-body">
            <div class="form-row align-items-end">
                <div class="form-group col-md-3 mb-2">
                    <label for="force-date">Tanggal PJJ</label>
                    <select id="force-date" class="form-control form-control-sm" @disabled(empty($pjjDates))>
                        @forelse ($pjjDates as $pjjDate)
                            <option value="{{ $pjjDate }}" @selected($pjjDate === now()->toDateString())>{{ \Carbon\Carbon::parse($pjjDate)->format('d-m-Y') }}</option>
                        @empty
                            <option value="">Belum ada tanggal PJJ</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group col-md-3 mb-2">
                    <label for="force-classroom">Kelas</label>
                    <select id="force-classroom" class="form-control form-control-sm">
                        <option value="">- Pilih kelas -</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 mb-2">
                    <button type="button" id="force-load" class="btn btn-primary btn-sm" disabled><i class="fas fa-users mr-1"></i> Tampilkan Siswa</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card d-none" id="force-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-check mr-1"></i> Daftar Siswa — <span id="force-card-info"></span></h3>
        </div>
        <div class="card-body border-bottom">
            <div class="form-row align-items-end">
                <div class="form-group col-md-3 mb-2">
                    <label for="force-status">Status kehadiran</label>
                    <select id="force-status" class="form-control form-control-sm">
                        <option value="Hadir">Hadir</option>
                        <option value="Terlambat">Terlambat</option>
                    </select>
                </div>
                <div class="form-group col-md-6 mb-2">
                    <label for="force-note">Alasan / kendala siswa <span class="text-danger">*</span></label>
                    <input type="text" id="force-note" class="form-control form-control-sm" maxlength="255"
                        placeholder="Contoh: HP rusak / tidak ada kuota / gangguan jaringan di rumah siswa">
                </div>
                <div class="form-group col-md-3 mb-2">
                    <button type="button" id="force-submit" class="btn btn-success btn-sm" disabled>
                        <i class="fas fa-check-double mr-1"></i> Absenkan Siswa Terpilih (<span id="force-count">0</span>)
                    </button>
                </div>
            </div>
            <small class="text-muted">Hanya siswa yang <strong>belum tercatat absensi PJJ</strong> pada tanggal tersebut yang dapat dipilih. Jam absen dicatat pukul 06.30 dengan tanda "Force admin".</small>
        </div>
        <div class="card-body table-responsive p-0" style="max-height: 480px;">
            <table id="force-student-table" class="table table-bordered table-striped table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width:5%;" class="text-center"><input type="checkbox" id="force-check-all" title="Pilih semua siswa yang belum absen"></th>
                        <th style="width:6%;">No</th>
                        <th>Nama Siswa</th>
                        <th style="width:14%;">NIS</th>
                        <th style="width:28%;">Status Absensi PJJ Saat Ini</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function () {
            let isLoading = false;
            let isSubmitting = false;

            function updateLoadButton() {
                $('#force-load').prop('disabled', isLoading || !$('#force-date').val() || !$('#force-classroom').val());
            }

            function updateSubmitState() {
                const checked = $('#force-student-table tbody .js-force-check:checked').length;
                $('#force-count').text(checked);
                $('#force-submit').prop('disabled', isSubmitting || checked === 0);
            }

            function renderStudents(students) {
                const tbody = $('#force-student-table tbody').empty();

                if (!students.length) {
                    tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada siswa pada kelas ini.</td></tr>');
                    return;
                }

                students.forEach(function (student, index) {
                    let statusHtml;
                    if (student.attended) {
                        statusHtml = '<span class="badge badge-success">' + $('<span>').text(student.status || 'Hadir').html() + '</span>'
                            + (student.check_in ? ' <small class="text-muted">pukul ' + student.check_in + '</small>' : '')
                            + (student.forced ? ' <span class="badge badge-info">Force admin</span>' : '');
                    } else {
                        statusHtml = '<span class="badge badge-danger">Belum absen</span>';
                    }

                    tbody.append(
                        '<tr>'
                        + '<td class="text-center">' + (student.attended
                            ? '<i class="fas fa-check text-success" title="Sudah absen"></i>'
                            : '<input type="checkbox" class="js-force-check" value="' + student.id + '">')
                        + '</td>'
                        + '<td class="text-center">' + (index + 1) + '</td>'
                        + '<td>' + $('<span>').text(student.name).html() + '</td>'
                        + '<td>' + $('<span>').text(student.nis || '—').html() + '</td>'
                        + '<td>' + statusHtml + '</td>'
                        + '</tr>'
                    );
                });
            }

            function loadStudents() {
                if (isLoading || !$('#force-date').val() || !$('#force-classroom').val()) {
                    return;
                }

                isLoading = true;
                updateLoadButton();

                $.get('{{ route('admin.pjj.force-attendance.students') }}', {
                    classroom_id: $('#force-classroom').val(),
                    date: $('#force-date').val()
                }).done(function (response) {
                    $('#force-card').removeClass('d-none');
                    $('#force-card-info').text(
                        $('#force-classroom option:selected').text() + ' · ' + $('#force-date option:selected').text()
                    );
                    $('#force-check-all').prop('checked', false);
                    renderStudents(response.students);
                    updateSubmitState();
                }).fail(function (xhr) {
                    const message = xhr.responseJSON?.message || 'Data siswa gagal dimuat. Silakan coba lagi.';
                    Swal.fire('Gagal', message, 'error');
                }).always(function () {
                    isLoading = false;
                    updateLoadButton();
                });
            }

            $('#force-date, #force-classroom').on('change', updateLoadButton);
            $('#force-load').on('click', loadStudents);

            $('#force-check-all').on('change', function () {
                $('#force-student-table tbody .js-force-check').prop('checked', this.checked);
                $('#force-student-table tbody tr').each(function () {
                    $(this).toggleClass('row-checked', $(this).find('.js-force-check').prop('checked') === true);
                });
                updateSubmitState();
            });

            $('#force-student-table').on('change', '.js-force-check', function () {
                $(this).closest('tr').toggleClass('row-checked', this.checked);
                updateSubmitState();
            });

            $('#force-submit').on('click', function () {
                const studentIds = $('#force-student-table tbody .js-force-check:checked')
                    .map(function () { return Number(this.value); }).get();
                const note = ($('#force-note').val() || '').trim();

                if (!studentIds.length || isSubmitting) {
                    return;
                }

                if (note === '') {
                    Swal.fire('Gagal', 'Isi alasan/kendala siswa terlebih dahulu.', 'error');
                    $('#force-note').focus();
                    return;
                }

                Swal.fire({
                    title: 'Force Absensi?',
                    html: '<strong>' + studentIds.length + ' siswa</strong> akan dicatat <strong>' +
                        $('#force-status').val() + '</strong> pada ' + $('#force-date option:selected').text() +
                        ' dan tersinkron ke absensi reguler.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    confirmButtonText: '<i class="fas fa-check mr-1"></i> Ya, Absenkan',
                    cancelButtonText: 'Batal'
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    isSubmitting = true;
                    updateSubmitState();

                    $.post('{{ route('admin.pjj.force-attendance.store') }}', {
                        _token: '{{ csrf_token() }}',
                        date: $('#force-date').val(),
                        student_ids: studentIds,
                        status: $('#force-status').val(),
                        note: note
                    }).done(function (response) {
                        Swal.fire('Berhasil', response.message, 'success');
                        loadStudents();
                    }).fail(function (xhr) {
                        const message = xhr.responseJSON?.errors
                            ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                            : (xhr.responseJSON?.message || 'Force absensi gagal disimpan. Silakan coba lagi.');
                        Swal.fire('Gagal', message, 'error');
                    }).always(function () {
                        isSubmitting = false;
                        updateSubmitState();
                    });
                });
            });

            updateLoadButton();
        });
    </script>
@stop
