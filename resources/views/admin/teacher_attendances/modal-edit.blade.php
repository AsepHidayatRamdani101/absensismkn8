<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEdit">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Absensi Guru</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Guru</label>
                            <select id="edit_teacher_id" name="teacher_id" class="form-control" required>
                                <option value="">- Pilih Guru -</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->nama_lengkap }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Jadwal</label>
                            <select id="edit_schedule_id" name="schedule_id" class="form-control" required>
                                <option value="">- Pilih Jadwal -</option>
                                @foreach ($schedules as $schedule)
                                    <option value="{{ $schedule->id }}">
                                        {{ $schedule->hari }} {{ $schedule->jam_mulai }}-{{ $schedule->jam_selesai }} |
                                        {{ $schedule->teacherSubject->subject->nama_mapel ?? '-' }} |
                                        {{ $schedule->teacherSubject->classroom->nama_kelas ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kelas</label>
                            <select id="edit_classroom_id" name="classroom_id" class="form-control" required>
                                <option value="">- Pilih Kelas -</option>
                                @foreach ($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}">{{ $classroom->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mata Pelajaran</label>
                            <select id="edit_subject_id" name="subject_id" class="form-control" required>
                                <option value="">- Pilih Mata Pelajaran -</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->nama_mapel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Tahun Ajaran</label>
                            <select id="edit_academic_year_id" name="academic_year_id" class="form-control" required>
                                <option value="">- Pilih Tahun Ajaran -</option>
                                @foreach ($academicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}">
                                        {{ $academicYear->tahun_ajaran }} - {{ $academicYear->semester }}
                                    </option>
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
                            <label>Pertemuan</label>
                            <input type="number" id="edit_pertemuan" name="pertemuan" class="form-control"
                                min="1" max="255" required>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Status</label>
                            <select id="edit_status" name="status" class="form-control" required>
                                <option value="Draft">Draft</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Materi Pembelajaran</label>
                        <textarea id="edit_materi_pembelajaran" name="materi_pembelajaran" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label>Catatan Guru</label>
                        <textarea id="edit_catatan_guru" name="catatan_guru" class="form-control" rows="2"></textarea>
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
