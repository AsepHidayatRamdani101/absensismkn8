@csrf

<div class="form-row">
    <div class="form-group col-md-4">
        <label>Kode Reward <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $reward->code ?? '') }}"
            maxlength="40" required autofocus>
        @error('code')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group col-md-8">
        <label>Nama Reward <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $reward->name ?? '') }}"
            maxlength="150" required>
        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label>Kategori Reward <span class="text-danger">*</span></label>
        <select name="reward_category_id" class="form-control select2" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    {{ (string) old('reward_category_id', $reward->reward_category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
                    {{ $category->name }}</option>
            @endforeach
        </select>
        @error('reward_category_id')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group col-md-2">
        <label>Point <span class="text-danger">*</span></label>
        <input type="number" name="point" class="form-control" value="{{ old('point', $reward->point ?? 0) }}"
            required>
        @error('point')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group col-md-3">
        <label>Character Dimension <span class="text-danger">*</span></label>
        <select name="character_dimension_id" class="form-control select2" required>
            <option value="">Pilih dimensi</option>
            @foreach ($dimensions as $dimension)
                <option value="{{ $dimension->id }}"
                    {{ (string) old('character_dimension_id', $selectedMapping->character_dimension_id ?? '') === (string) $dimension->id ? 'selected' : '' }}>
                    {{ $dimension->name }}</option>
            @endforeach
        </select>
        @error('character_dimension_id')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group col-md-3">
        <label>Weight <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="weight" class="form-control"
            value="{{ old('weight', $selectedMapping->weight ?? 1) }}" required>
        @error('weight')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
</div>

<div class="form-group">
    <label>Deskripsi</label>
    <textarea name="description" rows="3" class="form-control" maxlength="1000">{{ old('description', $reward->description ?? '') }}</textarea>
    @error('description')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="form-group">
    <label>Status <span class="text-danger">*</span></label>
    <select name="is_active" class="form-control" required>
        <option value="1"
            {{ (string) old('is_active', isset($reward) ? (int) $reward->is_active : 1) === '1' ? 'selected' : '' }}>
            Aktif</option>
        <option value="0"
            {{ (string) old('is_active', isset($reward) ? (int) $reward->is_active : 1) === '0' ? 'selected' : '' }}>
            Nonaktif</option>
    </select>
    @error('is_active')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>
