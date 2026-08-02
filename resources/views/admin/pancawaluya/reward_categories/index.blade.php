@extends('adminlte::page')

@section('title', 'Master Reward Category')
@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Pancawaluya - Master Reward Category</h1>
        <div>
            <a href="{{ route('pancawaluya.reward-categories.template') }}" class="btn btn-outline-secondary mr-1"><i
                    class="fas fa-file-download"></i> Template</a>
            <a href="{{ route('pancawaluya.reward-categories.export.excel', request()->query()) }}"
                class="btn btn-success mr-1"><i class="fas fa-file-excel"></i> Excel</a>
            <a href="{{ route('pancawaluya.reward-categories.export.csv', request()->query()) }}" class="btn btn-info mr-1"><i
                    class="fas fa-file-csv"></i> CSV</a>
            <a href="{{ route('pancawaluya.reward-categories.export.pdf', request()->query()) }}"
                class="btn btn-danger mr-1"><i class="fas fa-file-pdf"></i> PDF</a>
            <a href="{{ route('pancawaluya.reward-categories.print', request()->query()) }}" target="_blank"
                class="btn btn-dark mr-1"><i class="fas fa-print"></i> Print</a>
            <form action="{{ route('pancawaluya.reward-categories.import') }}" method="POST" enctype="multipart/form-data"
                class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" class="d-none input-import-file" accept=".xlsx,.xls,.csv">
                <input type="hidden" name="preview" value="0" class="input-import-preview">
                <button type="button" class="btn btn-warning btn-import"><i class="fas fa-file-import"></i> Import</button>
            </form>
            <a href="{{ route('pancawaluya.reward-categories.create') }}" class="btn btn-primary d-none"><i
                    class="fas fa-plus"></i> Tambah</a>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCreate"><i
                    class="fas fa-plus"></i> Tambah</button>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->has('file'))
                <div class="alert alert-danger">{{ $errors->first('file') }}</div>
            @endif

            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="filterTrashed">
                        <label class="custom-control-label" for="filterTrashed">Tampilkan Trashed</label>
                    </div>
                </div>
                <div class="col-md-6 text-right d-flex align-items-end justify-content-end">
                    <button id="btnRestoreSelected" class="btn btn-success btn-sm d-none mr-2"><i class="fas fa-undo"></i>
                        Restore Terpilih</button>
                    <button id="btnDeleteSelected" class="btn btn-danger btn-sm d-none"><i class="fas fa-trash"></i> Hapus
                        Terpilih</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tableRewardCategories" width="100%">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkAll"></th>
                            <th width="5%">No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Updated At</th>
                            <th width="12%">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('admin.pancawaluya.reward_categories.modal-create')
    @include('admin.pancawaluya.reward_categories.modal-edit')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('admin.pancawaluya.reward_categories._script')
@stop

@section('footer')
    @include('components.app-footer')
@stop
