<div class="modal fade" id="modalCreate">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formCreate">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">

                        Tambah Data Kelas

                    </h5>

                    <button type="button" class="close" data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>


                <div class="modal-body">

                    <div class="form-group">

                        <label>Jurusan</label>

                        <select name="major_id" class="form-control" required>

                            <option value="">
                                -- Pilih Jurusan --
                            </option>

                            @foreach ($majors as $major)
                                <option value="{{ $major->id }}">

                                    {{ $major->nama_jurusan }}

                                </option>
                            @endforeach

                        </select>

                    </div>


                    <div class="form-group">

                        <label>Tingkat</label>

                        <select name="tingkat" class="form-control">

                            <option>X</option>
                            <option>XI</option>
                            <option>XII</option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>Rombel</label>

                        <input type="text" name="rombel" class="form-control" placeholder="1" required>

                    </div>


                    <div class="form-group">

                        <label>Kode Kelas</label>

                        <input type="text" name="kode_kelas" class="form-control" placeholder="XTJKT1" required>

                    </div>


                    <div class="form-group">

                        <label>Nama Kelas</label>

                        <input type="text" name="nama_kelas" class="form-control" placeholder="X TJKT 1" required>

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
