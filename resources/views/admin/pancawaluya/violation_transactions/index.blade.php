@extends('adminlte::page')

@section('title', 'Violation Transaction')
@section('plugins.Datatables', true)
@section('plugins.Select2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Pancawaluya - Violation Transaction</h1>
        <div>
            <a href="{{ route('pancawaluya.violation-transactions.template') }}" class="btn btn-outline-secondary mr-1"><i
                    class="fas fa-file-download"></i> Template</a>
            <a href="{{ route('pancawaluya.violation-transactions.export.excel', request()->query()) }}"
                class="btn btn-success mr-1"><i class="fas fa-file-excel"></i> Excel</a>
            <a href="{{ route('pancawaluya.violation-transactions.export.csv', request()->query()) }}"
                class="btn btn-info mr-1"><i class="fas fa-file-csv"></i> CSV</a>
            <a href="{{ route('pancawaluya.violation-transactions.export.pdf', request()->query()) }}"
                class="btn btn-danger mr-1"><i class="fas fa-file-pdf"></i> PDF</a>
            <a href="{{ route('pancawaluya.violation-transactions.print', request()->query()) }}" target="_blank"
                class="btn btn-dark mr-1"><i class="fas fa-print"></i> Print</a>
            <form action="{{ route('pancawaluya.violation-transactions.import') }}" method="POST"
                enctype="multipart/form-data" class="d-inline-block mr-1">
                @csrf
                <input type="file" name="file" class="d-none input-import-file" accept=".xlsx,.xls,.csv">
                <input type="hidden" name="preview" value="0" class="input-import-preview">
                <button type="button" class="btn btn-warning btn-import"><i class="fas fa-file-import"></i> Import</button>
            </form>
            <a href="{{ route('pancawaluya.violation-transactions.create') }}" class="btn btn-primary d-none"><i
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
                <div class="col-md-2">
                    <label>Tahun Ajaran</label>
                    <select id="filterAcademicYear" class="form-control form-control-sm select2">
                        <option value="">Semua</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->tahun_ajaran }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Semester</label>
                    <select id="filterSemester" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Kelas</label>
                    <select id="filterClassroom" class="form-control form-control-sm select2">
                        <option value="">Semua</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Kategori</label>
                    <select id="filterCategory" class="form-control form-control-sm select2">
                        <option value="">Semua</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Pelanggaran</label>
                    <select id="filterItem" class="form-control form-control-sm select2">
                        <option value="">Semua</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="validated">Validated</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center mb-2">
                <div class="custom-control custom-switch mr-2">
                    <input type="checkbox" class="custom-control-input" id="filterTrashed">
                    <label class="custom-control-label" for="filterTrashed">Trashed</label>
                </div>
                <button id="btnRestoreSelected" class="btn btn-success btn-sm d-none mr-2"><i
                        class="fas fa-undo"></i></button>
                <button id="btnDeleteSelected" class="btn btn-danger btn-sm d-none"><i class="fas fa-trash"></i></button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tableViolationTransactions" width="100%">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkAll"></th>
                            <th width="4%">No</th>
                            <th>Tanggal</th>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Kategori</th>
                            <th>Pelanggaran</th>
                            <th>Point</th>
                            <th>Dimensi</th>
                            <th>Sumber</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th width="12%">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('admin.pancawaluya.violation_transactions.modal-create')
    @include('admin.pancawaluya.violation_transactions.modal-edit')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('admin.pancawaluya.violation_transactions._script')
@stop

@section('footer')
    @include('components.app-footer')
@stop
