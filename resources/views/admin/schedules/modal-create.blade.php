<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCreate">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jadwal</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Guru Pengampu</label>
                        <select name="teacher_subject_id" class="form-control" required>
                            <option value="">- Pilih Guru Pengampu -</option>
                            @foreach ($teacherSubjects as $teacherSubject)
                                <option value="{{ $teacherSubject->id }}">
                                    {{ $teacherSubject->teacher->nama_lengkap ?? '-' }} -
                                    {{ $teacherSubject->subject->nama_mapel ?? '-' }} -
                                    {{ $teacherSubject->classroom->nama_kelas ?? '-' }} -
                                    {{ $teacherSubject->academicYear->tahun_ajaran ?? '-' }}
                                    ({{ $teacherSubject->academicYear->semester ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Hari</label>
                            <select name="hari" class="form-control" required>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ruangan</label>
                            <input type="text" name="ruangan" class="form-control" placeholder="Contoh: Lab TKJ 1">
                        </div>
                    </div>

                    <div class="form-row mb-0">
                        <div class="form-group col-md-6">
                            <label>Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" required>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" required>
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
