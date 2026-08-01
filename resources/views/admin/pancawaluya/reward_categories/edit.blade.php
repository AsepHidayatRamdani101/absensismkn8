@extends('adminlte::page')

@section('title', 'Edit Kategori Reward')

@section('content_header')
    <h1>Edit Kategori Reward</h1>
@stop

@section('content')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Form Edit Kategori Reward</h3>
        </div>
        <form action="{{ route('pancawaluya.reward-categories.update', $rewardCategory) }}" method="POST"
            id="formEditRewardCategory">
            @method('PUT')
            <div class="card-body">
                @include('admin.pancawaluya.reward_categories._form')
                <div class="alert alert-light border mt-3 mb-0">
                    <small>Terakhir diperbarui: {{ optional($rewardCategory->updated_at)->format('d-m-Y H:i') }}</small>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('pancawaluya.reward-categories.index') }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-warning">Update</button>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $('#formEditRewardCategory').on('submit', function(e) {
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
