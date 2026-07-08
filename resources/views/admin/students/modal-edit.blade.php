<div class="modal fade" id="modalEdit">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form id="formEdit">

                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Edit Siswa
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

                                <input id="edit_nis" type="text" class="form-control">

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>NISN</label>

                                <input id="edit_nisn" type="text" class="form-control">

                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label>Nama Lengkap</label>

                        <input id="edit_nama_lengkap" type="text" class="form-control">

                    </div>


                    <div class="form-group">

                        <label>Jenis Kelamin</label>

                        <select id="edit_jenis_kelamin" class="form-control">

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

                        <select id="edit_classroom_id" class="form-control">

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

                        <input id="edit_no_hp" type="text" class="form-control">

                    </div>


                    <div class="form-group">

                        <label>Alamat</label>

                        <textarea id="edit_alamat" class="form-control" rows="3"></textarea>

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
