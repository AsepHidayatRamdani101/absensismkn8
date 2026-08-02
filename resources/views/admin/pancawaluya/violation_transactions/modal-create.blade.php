<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formCreate" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Tambah Violation Transaction</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="createErrors" class="alert alert-danger d-none"></div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Tahun Ajaran <span class="text-danger">*</span></label>
                            <select name="academic_year_id" id="createAcademicYear" class="form-control select2-modal"
                                required>
                                <option value="">Pilih Tahun Ajaran</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->tahun_ajaran }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Semester <span class="text-danger">*</span></label>
                            <select name="semester" id="createSemester" class="form-control" required>
                                <option value="">Pilih</option>
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" id="createTransactionDate"
                                class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="form-group col-md-5">
                            <label>Siswa <span class="text-danger">*</span></label>
                            <select name="student_id" id="createStudentSelect" class="form-control" required></select>
                            <input type="hidden" name="classroom_id" id="createClassroomId">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kategori Pelanggaran <span class="text-danger">*</span></label>
                            <select name="violation_category_id" id="createViolationCategory"
                                class="form-control select2-modal" required>
                                <option value="">Pilih Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Pelanggaran <span class="text-danger">*</span></label>
                            <select name="violation_item_id" id="createViolationItem" class="form-control select2-modal"
                                required>
                                <option value="">Pilih Pelanggaran</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}"
                                        data-category-id="{{ $item->violation_category_id }}">
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Sumber <span class="text-danger">*</span></label>
                            <input type="text" name="source" id="createSource" class="form-control"
                                value="Observasi Guru" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Point (Otomatis)</label>
                            <input type="text" id="createPointPreview" class="form-control" readonly>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" id="createStatus" class="form-control" required>
                                <option value="pending">pending</option>
                                <option value="draft">draft</option>
                                <option value="validated">validated</option>
                                <option value="approved">approved</option>
                                <option value="rejected">rejected</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Attachment</label>
                            <input type="file" name="attachment" class="form-control-file pt-1"
                                accept=".jpg,.jpeg,.png,.pdf,.webp">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" id="createDescription" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
