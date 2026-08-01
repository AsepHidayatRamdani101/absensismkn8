@csrf

<div class="form-row">
    <div class="form-group col-md-4">
        <label>Kode <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $rewardCategory->code ?? '') }}"
            maxlength="30" required autofocus>
        @error('code')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="form-group col-md-8">
        <label>Nama Kategori <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $rewardCategory->name ?? '') }}"
            maxlength="120" required>
        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
</div>

<div class="form-group">
    <label>Deskripsi</label>
    <textarea name="description" rows="3" class="form-control" maxlength="1000">{{ old('description', $rewardCategory->description ?? '') }}</textarea>
    @error('description')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="form-group">
    <label>Status <span class="text-danger">*</span></label>
    <select name="is_active" class="form-control" required>
        <option value="1"
            {{ (string) old('is_active', isset($rewardCategory) ? (int) $rewardCategory->is_active : 1) === '1' ? 'selected' : '' }}>
            Aktif</option>
        <option value="0"
            {{ (string) old('is_active', isset($rewardCategory) ? (int) $rewardCategory->is_active : 1) === '0' ? 'selected' : '' }}>
            Nonaktif</option>
    </select>
    @error('is_active')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>
