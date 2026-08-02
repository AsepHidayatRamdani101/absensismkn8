<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCreate">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Master Reward</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="createErrors" class="alert alert-danger d-none"></div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kode Reward <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" maxlength="40" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Nama Reward <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" maxlength="150" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kategori Reward <span class="text-danger">*</span></label>
                            <select name="reward_category_id" class="form-control select2-modal" required>
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Point <span class="text-danger">*</span></label>
                            <input type="number" name="point" class="form-control" value="0" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Character Dimension <span class="text-danger">*</span></label>
                            <select name="character_dimension_id" class="form-control select2-modal" required>
                                <option value="">Pilih dimensi</option>
                                @foreach ($dimensions as $dimension)
                                    <option value="{{ $dimension->id }}">{{ $dimension->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Weight <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="weight" class="form-control"
                                value="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" rows="2" class="form-control" maxlength="1000"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="is_active" class="form-control" required>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
