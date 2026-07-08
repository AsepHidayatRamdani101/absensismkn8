<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEdit">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Perangkat IoT</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Device</label>
                        <input type="text" id="edit_nama_device" name="nama_device" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Device Code</label>
                        <input type="text" id="edit_device_code" name="device_code" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis</label>
                        <select id="edit_jenis" name="jenis" class="form-control" required>
                            <option value="RFID">RFID</option>
                            <option value="FACE">FACE</option>
                            <option value="QR">QR</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" id="edit_lokasi" name="lokasi" class="form-control">
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active" name="is_active"
                                value="1">
                            <label class="custom-control-label" for="edit_is_active">Aktif</label>
                        </div>
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
