@csrf
<div class="card card-primary card-outline">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-control select2" required>
                        <option value="">Pilih Tahun Ajaran</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(old('academic_year_id', $row->academic_year_id ?? '') == $year->id)>{{ $year->tahun_ajaran }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" class="form-control" required>
                        <option value="">Pilih</option>
                        <option value="Ganjil" @selected(old('semester', $row->semester ?? '') == 'Ganjil')>Ganjil</option>
                        <option value="Genap" @selected(old('semester', $row->semester ?? '') == 'Genap')>Genap</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="transaction_date" class="form-control"
                        value="{{ old('transaction_date', isset($row) ? optional($row->transaction_date)->toDateString() : now()->toDateString()) }}"
                        required>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label>Siswa</label>
                    <select name="student_id" id="studentSelect" class="form-control" required></select>
                    <input type="hidden" name="classroom_id" id="classroomIdHidden"
                        value="{{ old('classroom_id', $row->classroom_id ?? '') }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Kategori Reward</label>
                    <select name="reward_category_id" id="rewardCategorySelect" class="form-control select2" required>
                        <option value="">Pilih Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('reward_category_id', $row->reward_category_id ?? '') == $category->id)>{{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Reward</label>
                    <select name="reward_item_id" id="rewardItemSelect" class="form-control select2" required>
                        <option value="">Pilih Reward</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" data-category-id="{{ $item->reward_category_id }}"
                                @selected(old('reward_item_id', $row->reward_item_id ?? '') == $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Sumber</label>
                    <input type="text" name="source" class="form-control"
                        value="{{ old('source', $row->source ?? 'Observasi Guru') }}" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Point (Otomatis)</label>
                    <input type="text" id="pointPreview" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Total Weight (Otomatis)</label>
                    <input type="text" id="weightPreview" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $row->status ?? 'pending') == $status)>{{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Dimensi Karakter (Preview)</label>
            <div id="dimensionPreview" class="border rounded p-2 bg-light">-</div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Attachment</label>
                    <input type="file" name="attachment" class="form-control-file"
                        accept=".jpg,.jpeg,.png,.pdf,.webp">
                    @if (!empty($row?->attachment_path))
                        <small class="text-muted d-block mt-1">File saat ini: {{ $row->attachment_path }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description', $row->description ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        <a href="{{ route('pancawaluya.reward-transactions.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
