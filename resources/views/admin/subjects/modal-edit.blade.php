<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEdit">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Mata Pelajaran
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Mata Pelajaran</label>
                        <input type="text" id="edit_kode_mapel" name="kode_mapel" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Nama Mata Pelajaran</label>
                        <input type="text" id="edit_nama_mapel" name="nama_mapel" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select id="edit_kategori" name="kategori" class="form-control" required>
                            <option value="Umum">Umum</option>
                            <option value="Kejuruan">Kejuruan</option>
                            <option value="Muatan Lokal">Muatan Lokal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jam per Minggu</label>
                        <input type="number" id="edit_jam_per_minggu" name="jam_per_minggu" class="form-control"
                            min="0" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i>
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
