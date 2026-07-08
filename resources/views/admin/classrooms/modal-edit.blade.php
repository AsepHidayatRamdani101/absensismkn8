<div class="modal fade" id="modalEdit">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formEdit">

                @csrf
                @method('PUT')

                <input type="hidden" id="edit_id">

                <div class="modal-header">

                    <h5 class="modal-title">

                        Edit Data Kelas

                    </h5>

                    <button type="button" class="close" data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>


                <div class="modal-body">

                    <div class="form-group">

                        <label>Jurusan</label>

                        <select id="edit_major_id" name="major_id" class="form-control">

                            @foreach ($majors as $major)
                                <option value="{{ $major->id }}">

                                    {{ $major->nama_jurusan }}

                                </option>
                            @endforeach

                        </select>

                    </div>


                    <div class="form-group">

                        <label>Tingkat</label>

                        <select id="edit_tingkat" name="tingkat" class="form-control">

                            <option>X</option>
                            <option>XI</option>
                            <option>XII</option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>Rombel</label>

                        <input id="edit_rombel" type="text" class="form-control">

                    </div>


                    <div class="form-group">

                        <label>Kode Kelas</label>

                        <input id="edit_kode_kelas" type="text" class="form-control">

                    </div>


                    <div class="form-group">

                        <label>Nama Kelas</label>

                        <input id="edit_nama_kelas" type="text" class="form-control">

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
