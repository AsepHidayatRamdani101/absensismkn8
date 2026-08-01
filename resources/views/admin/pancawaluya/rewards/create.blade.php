@extends('adminlte::page')

@section('title', 'Tambah Master Reward')
@section('plugins.Select2', true)

@section('content_header')
    <h1>Tambah Master Reward</h1>
@stop

@section('content')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Form Master Reward</h3>
        </div>
        <form action="{{ route('pancawaluya.rewards.store') }}" method="POST">
            <div class="card-body">
                @include('admin.pancawaluya.rewards._form', ['reward' => null, 'selectedMapping' => null])
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('pancawaluya.rewards.index') }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script>
        $('.select2').select2({
            width: '100%'
        });
    </script>
@stop

@section('footer')
    @include('components.app-footer')
@stop
