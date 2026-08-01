@extends('adminlte::page')

@section('title', 'Tambah Violation Transaction')
@section('plugins.Select2', true)

@section('content_header')
    <h1>Pancawaluya - Tambah Violation Transaction</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('pancawaluya.violation-transactions.store') }}" enctype="multipart/form-data">
        @include('admin.pancawaluya.violation_transactions._form')
    </form>
@stop

@section('js')
    @include('admin.pancawaluya.violation_transactions._script')
@stop

@section('footer')
    @include('components.app-footer')
@stop
