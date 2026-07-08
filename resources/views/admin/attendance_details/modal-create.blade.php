<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCreate">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Absensi Siswa Oleh Guru</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Sesi Absensi Guru</label>
                        <select id="create_teacher_attendance_id" name="teacher_attendance_id" class="form-control"
                            required>
                            <option value="">- Pilih Sesi -</option>
                            @foreach ($teacherAttendances as $session)
                                <option value="{{ $session->id }}" data-classroom-id="{{ $session->classroom_id }}">
                                    {{ $session->tanggal }} | {{ $session->teacher->nama_lengkap ?? '-' }} |
                                    {{ $session->subject->nama_mapel ?? '-' }} |
                                    {{ $session->classroom->nama_kelas ?? '-' }} |
                                    Pertemuan {{ $session->pertemuan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Siswa</label>
                        <select id="create_student_id" name="student_id" class="form-control" required>
                            <option value="">- Pilih Siswa -</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" data-classroom-id="{{ $student->classroom_id }}">
                                    {{ $student->nama_lengkap }} ({{ $student->classroom->nama_kelas ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row mb-0">
                        <div class="form-group col-md-4">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Hadir">Hadir</option>
                                <option value="Izin">Izin</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Alpha">Alpha</option>
                                <option value="Terlambat">Terlambat</option>
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Jam Absen</label>
                            <input type="time" name="jam_absen" class="form-control">
                        </div>

                        <div class="form-group col-md-4">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" maxlength="255"
                                placeholder="Opsional">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
