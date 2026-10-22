<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCreate">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">
                        Tambah Hari Libur
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_akhir" class="form-control" required>
                        <small class="form-text text-muted">Jam mulai libur hanya berlaku pada tanggal mulai. Tanggal berikutnya dalam rentang ini libur sehari penuh.</small>
                    </div>

                    <div class="form-group">
                        <label>Jam Mulai Libur</label>
                        <input type="time" name="jam_mulai_libur" class="form-control" value="00:00" required>
                        <small class="form-text text-muted">Pukul 00:00 berarti libur sehari penuh. Jadwal yang mulai sebelum jam ini tetap berjalan.</small>
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" class="form-control"
                            placeholder="Contoh: Tahun Baru Islam" required>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_is_national"
                                name="is_national" value="1" checked>
                            <label class="custom-control-label" for="create_is_national">Libur nasional</label>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_is_pjj"
                                name="is_pjj" value="1">
                            <label class="custom-control-label" for="create_is_pjj">Mode belajar PJJ</label>
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

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
