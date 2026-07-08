<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formCreate">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Tambah Jurusan
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>

                <div class="modal-body">

                    <div class="form-group">

                        <label>Kode Jurusan</label>

                        <input type="text" name="kode_jurusan" class="form-control" required>

                    </div>

                    <div class="form-group">

                        <label>Nama Jurusan</label>

                        <input type="text" name="nama_jurusan" class="form-control" required>

                    </div>

                    <div class="form-group">

                        <label>Singkatan</label>

                        <input type="text" name="singkatan" class="form-control" required>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-dismiss="modal">

                        Batal

                    </button>

                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save"></i>
                        Simpan

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
