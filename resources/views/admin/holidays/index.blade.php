@extends('adminlte::page')

@section('title', 'Data Hari Libur')

@section('plugins.Datatables', true)

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Master Data Hari Libur</h1>

        <button class="btn btn-primary" data-toggle="modal" data-target="#modalCreate">
            <i class="fas fa-plus"></i>
            Tambah Hari Libur
        </button>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table id="tableHolidays" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="25%">Rentang Tanggal</th>
                        <th>Keterangan</th>
                        <th width="15%">Mode</th>
                        <th width="15%">Kategori</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($holidays as $holiday)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @php
                                    $startDate = \Carbon\Carbon::parse($holiday->tanggal);
                                    $endDate = \Carbon\Carbon::parse($holiday->tanggal_akhir ?? $holiday->tanggal);
                                @endphp
                                @if ($startDate->isSameDay($endDate))
                                    {{ $startDate->format('d-m-Y') }}
                                @else
                                    {{ $startDate->format('d-m-Y') }} s.d. {{ $endDate->format('d-m-Y') }}
                                @endif
                            </td>
                            <td>{{ $holiday->keterangan }}</td>
                            <td>
                                @if ($holiday->is_pjj)
                                    <span class="badge badge-info">Belajar PJJ</span>
                                @else
                                    <span class="badge badge-warning">Hari Libur</span>
                                @endif
                            </td>
                            <td>
                                @if ($holiday->is_pjj)
                                    <span class="text-muted">—</span>
                                @elseif ($holiday->is_national)
                                    <span class="badge badge-success">Nasional</span>
                                @else
                                    <span class="badge badge-secondary">Sekolah</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-warning btn-xs btn-edit" data-id="{{ $holiday->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-xs btn-delete" data-id="{{ $holiday->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.holidays.modal-create')
    @include('admin.holidays.modal-edit')
@stop

@section('footer')
    @include('components.app-footer')
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @include('admin.holidays._script')
@stop
