@extends('adminlte::page')

@section('title', 'Edit Reward Transaction')
@section('plugins.Select2', true)

@section('content_header')
    <h1>Pancawaluya - Edit Reward Transaction</h1>
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

    <form method="POST" action="{{ route('pancawaluya.reward-transactions.update', $row) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.pancawaluya.reward_transactions._form', ['row' => $row])
    </form>
@stop

@section('js')
    @include('admin.pancawaluya.reward_transactions._script')
@stop

@section('footer')
    @include('components.app-footer')
@stop
