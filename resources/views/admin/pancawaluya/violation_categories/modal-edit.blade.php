<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEdit">
                @csrf
                @method('PUT')
                <input type="hidden" id="editId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori Pelanggaran</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="editErrors" class="alert alert-danger d-none"></div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kode <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="editCode" class="form-control" maxlength="30"
                                required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editName" class="form-control" maxlength="120"
                                required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" id="editDescription" rows="3" class="form-control" maxlength="1000"></textarea>
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
