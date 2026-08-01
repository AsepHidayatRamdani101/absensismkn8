@extends('adminlte::page')

@section('title', 'Transaction Histories')
@section('plugins.Datatables', true)
@section('plugins.Select2', true)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Pancawaluya - Transaction History</h1>
        <div>
            <a href="{{ route('pancawaluya.transaction-histories.export.excel', request()->query()) }}"
                class="btn btn-success mr-1"><i class="fas fa-file-excel"></i> Excel</a>
            <a href="{{ route('pancawaluya.transaction-histories.export.csv', request()->query()) }}"
                class="btn btn-info mr-1"><i class="fas fa-file-csv"></i> CSV</a>
            <a href="{{ route('pancawaluya.transaction-histories.export.pdf', request()->query()) }}"
                class="btn btn-danger mr-1"><i class="fas fa-file-pdf"></i> PDF</a>
            <a href="{{ route('pancawaluya.transaction-histories.print', request()->query()) }}" target="_blank"
                class="btn btn-dark"><i class="fas fa-print"></i> Print</a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
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
                    <label>Tipe</label>
                    <select id="filterReferenceType" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="reward_transaction">Reward</option>
                        <option value="violation_transaction">Violation</option>
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
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Aksi</label>
                    <select id="filterAction" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="CREATE">CREATE</option>
                        <option value="UPDATE">UPDATE</option>
                        <option value="DELETE">DELETE</option>
                        <option value="RESTORE">RESTORE</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tableTransactionHistories" width="100%">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th>Tanggal</th>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Tipe</th>
                            <th>Aksi</th>
                            <th>Status</th>
                            <th>Skor</th>
                            <th>Sumber</th>
                            <th>Aktor</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    @include('admin.pancawaluya.transaction_histories._script')
@stop

@section('footer')
    @include('components.app-footer')
@stop
