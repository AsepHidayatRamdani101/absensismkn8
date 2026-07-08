<div class="modal fade" id="modalCreate">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form id="formCreate">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Tambah Siswa
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>NIS</label>

                                <input type="text" name="nis" class="form-control" required>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>NISN</label>

                                <input type="text" name="nisn" class="form-control">

                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label>Nama Lengkap</label>

                        <input type="text" name="nama_lengkap" class="form-control" required>

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

                        <label>Kelas</label>

                        <select name="classroom_id" class="form-control" required>

                            <option value="">
                                -- Pilih Kelas --
                            </option>

                            @foreach ($classrooms as $kelas)
                                <option value="{{ $kelas->id }}">

                                    {{ $kelas->nama_kelas }}

                                    ({{ $kelas->major->singkatan }})
                                </option>
                            @endforeach

                        </select>

                    </div>


                    <div class="form-group">

                        <label>No HP</label>

                        <input type="text" name="no_hp" class="form-control">

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
