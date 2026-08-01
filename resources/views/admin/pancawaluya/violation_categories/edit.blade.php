@extends('adminlte::page')

@section('title', 'Edit Kategori Pelanggaran')

@section('content_header')
    <h1>Edit Kategori Pelanggaran</h1>
@stop

@section('content')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Form Edit Kategori Pelanggaran</h3>
        </div>
        <form action="{{ route('pancawaluya.violation-categories.update', $violationCategory) }}" method="POST"
            id="formEditViolationCategory">
            @method('PUT')
            <div class="card-body">
                @include('admin.pancawaluya.violation_categories._form')
                <div class="alert alert-light border mt-3 mb-0">
                    <small>Terakhir diperbarui: {{ optional($violationCategory->updated_at)->format('d-m-Y H:i') }}</small>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('pancawaluya.violation-categories.index') }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-warning">Update</button>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $('#formEditViolationCategory').on('submit', function(e) {
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
