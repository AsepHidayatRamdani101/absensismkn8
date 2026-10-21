<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEdit">
                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Hari Libur
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" id="edit_tanggal" name="tanggal" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Selesai</label>
                        <input type="date" id="edit_tanggal_akhir" name="tanggal_akhir" class="form-control" required>
                        <small class="form-text text-muted">Semua tanggal dalam rentang ini menerapkan mode libur atau PJJ yang dipilih.</small>
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" id="edit_keterangan" name="keterangan" class="form-control" required>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_national" name="is_national"
                                value="1">
                            <label class="custom-control-label" for="edit_is_national">Libur nasional</label>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_pjj"
                                name="is_pjj" value="1">
                            <label class="custom-control-label" for="edit_is_pjj">Mode belajar PJJ</label>
                        </div>
                        <small class="form-text text-muted">
                            PJJ tetap dihitung sebagai hari belajar efektif dan absensi siswa/agenda guru tetap berjalan.
                            Foto kehadiran guru secara fisik dinonaktifkan.
                        </small>
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
