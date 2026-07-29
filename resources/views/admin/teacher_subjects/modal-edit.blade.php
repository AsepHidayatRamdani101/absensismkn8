<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEdit">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Guru Pengampu</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Tahun Ajaran <span class="text-danger">*</span></label>
                        <select id="edit_academic_year_id" name="academic_year_id" class="form-control select2" required
                            style="width: 100%;">
                            <option value="">- Pilih Tahun Ajaran -</option>
                            @foreach ($academicYears as $academicYear)
                                <option value="{{ $academicYear->id }}">
                                    {{ $academicYear->tahun_ajaran }} - {{ $academicYear->semester }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Guru <span class="text-danger">*</span></label>
                        <select id="edit_teacher_id" name="teacher_id" class="form-control select2" required
                            style="width: 100%;">
                            <option value="">- Cari Guru -</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Mata Pelajaran <span class="text-danger">*</span></label>
                        <select id="edit_subject_id" name="subject_id" class="form-control select2" required
                            style="width: 100%;">
                            <option value="">- Cari Mata Pelajaran -</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->nama_mapel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kelas <span class="text-danger">*</span></label>
                        <select id="edit_classroom_id" name="classroom_id[]" class="form-control select2" multiple
                            required style="width: 100%;">
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->nama_kelas }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Dapat memilih multiple kelas</small>
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
