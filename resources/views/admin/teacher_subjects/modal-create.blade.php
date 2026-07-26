<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCreate">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Guru Pengampu</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="form-group ">
                        <label>Tahun Ajaran <span class="text-danger">*</span></label>
                        <select name="academic_year_id" id="create_academic_year_id" class="form-control select2"
                            required style="width: 100%;">
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
                        <select name="teacher_id" id="create_teacher_id" class="form-control select2" required
                            style="width: 100%;">
                            <option value="">- Cari Guru -</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Mata Pelajaran <span class="text-danger">*</span></label>
                        <select name="subject_id" id="create_subject_id" class="form-control select2" required
                            style="width: 100%;">
                            <option value="">- Cari Mata Pelajaran -</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->nama_mapel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kelas <span class="text-danger">*</span></label>
                        <select name="classroom_id[]" id="create_classroom_id" class="form-control select2" multiple
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
