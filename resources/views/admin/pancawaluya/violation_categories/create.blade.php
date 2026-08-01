@extends('adminlte::page')

@section('title', 'Tambah Kategori Pelanggaran')

@section('content_header')
    <h1>Tambah Kategori Pelanggaran</h1>
@stop

@section('content')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Form Kategori Pelanggaran</h3>
        </div>
        <form action="{{ route('pancawaluya.violation-categories.store') }}" method="POST">
            <div class="card-body">
                @include('admin.pancawaluya.violation_categories._form')
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('pancawaluya.violation-categories.index') }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@stop

@section('footer')
    @include('components.app-footer')
@stop
