<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" id="editId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Master Pelanggaran</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="editErrors" class="alert alert-danger d-none"></div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kode Pelanggaran <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="editCode" class="form-control" maxlength="40"
                                required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Nama Pelanggaran <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editName" class="form-control" maxlength="150"
                                required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kategori Pelanggaran <span class="text-danger">*</span></label>
                            <select name="violation_category_id" id="editViolationCategoryId"
                                class="form-control select2-modal" required>
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Point <span class="text-danger">*</span></label>
                            <input type="number" name="point" id="editPoint" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Character Dimension <span class="text-danger">*</span></label>
                            <select name="character_dimension_id" id="editCharacterDimensionId"
                                class="form-control select2-modal" required>
                                <option value="">Pilih dimensi</option>
                                @foreach ($dimensions as $dimension)
                                    <option value="{{ $dimension->id }}">{{ $dimension->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Weight <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="weight" id="editWeight" class="form-control"
                                required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" id="editDescription" rows="2" class="form-control" maxlength="1000"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="is_active" id="editIsActive" class="form-control" required>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
