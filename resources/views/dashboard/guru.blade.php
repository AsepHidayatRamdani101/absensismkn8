<x-app-layout>

    <div class="container">

        <h2>Dashboard Guru</h2>

        <div class="alert alert-success">

            Selamat datang,
            {{ auth()->user()->name }}

        </div>

    </div>

</x-app-layout>
