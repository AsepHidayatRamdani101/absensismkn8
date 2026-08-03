<div class="modal fade" id="modalCreate">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form id="formCreate">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Tambah Guru
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>NIP</label>

                                <input type="text" name="nip" class="form-control">

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>NUPTK</label>

                                <input type="text" name="nuptk" class="form-control">

                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label>Nama Lengkap</label>

                        <input type="text" name="nama_lengkap" class="form-control" required>

                    </div>


                    <div class="form-group">

                        <label>Jabatan</label>

                        <select name="jabatan" class="form-control" required>

                            <option value="guru">Guru</option>

                            <option value="kepala_program">Kepala Program</option>

                            <option value="kepala_sekolah">Kepala Sekolah</option>

                            <option value="bk">BK</option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>Jenis Kelamin</label>

                        <select name="jenis_kelamin" class="form-control">

                            <option value="L">
                                Laki-laki
                            </option>

                            <option value="P">
                                Perempuan
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>No HP</label>

                        <input type="text" name="no_hp" class="form-control">

                    </div>

                    <div class="form-group">

                        <label>Kelas Wali</label>

                        <select name="wali_classroom_id" id="create_wali_classroom_id" class="form-control">
                            <option value="">Kosongkan jika bukan wali kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->nama_kelas }}</option>
                            @endforeach
                        </select>

                        <small class="text-muted d-block mt-1">Isi hanya jika guru memang menjadi wali kelas.</small>

                    </div>

                    <div class="form-group">

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="create_is_kurikulum"
                                name="is_kurikulum" value="1">
                            <label class="custom-control-label" for="create_is_kurikulum">Akun Kurikulum</label>
                        </div>

                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" class="custom-control-input" id="create_is_bk" name="is_bk"
                                value="1">
                            <label class="custom-control-label" for="create_is_bk">Akun BK</label>
                        </div>

                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" class="custom-control-input" id="create_is_kesiswaan"
                                name="is_kesiswaan" value="1">
                            <label class="custom-control-label" for="create_is_kesiswaan">Akun Kesiswaan</label>
                        </div>

                        <small class="text-muted d-block mt-1">Centang sesuai akses tambahan yang dimiliki akun
                            ini.</small>

                    </div>


                    <div class="form-group">

                        <label>Alamat</label>

                        <textarea name="alamat" class="form-control" rows="3"></textarea>

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
