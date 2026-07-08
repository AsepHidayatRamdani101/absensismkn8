<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formEdit">

                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Edit Jurusan
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>

                <div class="modal-body">

                    <div class="form-group">

                        <label>Kode Jurusan</label>

                        <input type="text" id="edit_kode_jurusan" name="kode_jurusan" class="form-control" required>

                    </div>

                    <div class="form-group">

                        <label>Nama Jurusan</label>

                        <input type="text" id="edit_nama_jurusan" name="nama_jurusan" class="form-control" required>

                    </div>

                    <div class="form-group">

                        <label>Singkatan</label>

                        <input type="text" id="edit_singkatan" name="singkatan" class="form-control" required>

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
