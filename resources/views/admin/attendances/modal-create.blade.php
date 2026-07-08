<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCreate">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Absensi</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Siswa</label>
                        <select name="student_id" class="form-control" required>
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
                            <select name="schedule_id" class="form-control">
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
                            <select name="attendance_device_id" class="form-control">
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
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Jam Masuk</label>
                            <input type="time" name="jam_masuk" class="form-control">
                        </div>

                        <div class="form-group col-md-4">
                            <label>Jam Keluar</label>
                            <input type="time" name="jam_keluar" class="form-control">
                        </div>
                    </div>

                    <div class="form-row mb-0">
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Hadir">Hadir</option>
                                <option value="Izin">Izin</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Alpha">Alpha</option>
                                <option value="Terlambat">Terlambat</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Metode</label>
                            <select name="metode" class="form-control" required>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
