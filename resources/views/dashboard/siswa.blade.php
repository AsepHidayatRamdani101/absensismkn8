<x-app-layout>

    <div class="container">

        <h2>Dashboard Siswa</h2>

        <div class="alert alert-info">

            Halo,
            {{ auth()->user()->name }}

        </div>

    </div>

</x-app-layout>
