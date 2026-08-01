@extends('adminlte::page')

@section('title', 'Edit Master Reward')
@section('plugins.Select2', true)

@section('content_header')
    <h1>Edit Master Reward</h1>
@stop

@section('content')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Form Edit Master Reward</h3>
        </div>
        <form action="{{ route('pancawaluya.rewards.update', $reward) }}" method="POST" id="formEditReward">
            @method('PUT')
            <div class="card-body">
                @include('admin.pancawaluya.rewards._form')
                <div class="alert alert-light border mt-3 mb-0">
                    <small>Terakhir diperbarui: {{ optional($reward->updated_at)->format('d-m-Y H:i') }}</small>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('pancawaluya.rewards.index') }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-warning">Update</button>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $('.select2').select2({
            width: '100%'
        });
        $('#formEditReward').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Simpan perubahan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@stop

@section('footer')
    @include('components.app-footer')
@stop
