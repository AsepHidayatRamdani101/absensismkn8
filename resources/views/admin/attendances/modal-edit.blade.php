<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEdit">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Absensi</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Siswa</label>
                        <select id="edit_student_id" name="student_id" class="form-control" required>
                            <option value="">- Pilih Siswa -</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->nama_lengkap }} ({{ $student->classroom->nama_kelas ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Jadwal</label>
                            <select id="edit_schedule_id" name="schedule_id" class="form-control">
                                <option value="">- Tidak ada -</option>
                                @foreach ($schedules as $schedule)
                                    <option value="{{ $schedule->id }}">
                                        {{ $schedule->hari }} {{ $schedule->jam_mulai }}-
                                        {{ $schedule->jam_selesai }} |
                                        {{ $schedule->teacherSubject->subject->nama_mapel ?? '-' }} |
                                        {{ $schedule->teacherSubject->classroom->nama_kelas ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Perangkat IoT</label>
                            <select id="edit_attendance_device_id" name="attendance_device_id" class="form-control">
                                <option value="">- Tidak ada -</option>
                                @foreach ($attendanceDevices as $device)
                                    <option value="{{ $device->id }}">{{ $device->nama_device }}
                                        ({{ $device->jenis }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tanggal</label>
                            <input type="date" id="edit_tanggal" name="tanggal" class="form-control" required>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Jam Masuk</label>
                            <input type="time" id="edit_jam_masuk" name="jam_masuk" class="form-control">
                        </div>

                        <div class="form-group col-md-4">
                            <label>Jam Keluar</label>
                            <input type="time" id="edit_jam_keluar" name="jam_keluar" class="form-control">
                        </div>
                    </div>

                    <div class="form-row mb-0">
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select id="edit_status" name="status" class="form-control" required>
                                <option value="Hadir">Hadir</option>
                                <option value="Izin">Izin</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Alpha">Alpha</option>
                                <option value="Terlambat">Terlambat</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Metode</label>
                            <select id="edit_metode" name="metode" class="form-control" required>
                                <option value="Manual">Manual</option>
                                <option value="RFID">RFID</option>
                                <option value="Face">Face</option>
                                <option value="QR">QR</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i>
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
