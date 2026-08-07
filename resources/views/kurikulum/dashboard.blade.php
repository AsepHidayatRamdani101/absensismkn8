@extends('adminlte::page')

@section('title', 'Dashboard Kurikulum')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">Dashboard Kurikulum</h1>
            <p class="text-muted mb-0">Akses cepat untuk absensi, setting jadwal, approval, dan laporan.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
            <a href="{{ route('kurikulum.dashboard.dss', ['mode' => $mode ?? 'ringkas']) }}"
                class="btn btn-sm btn-outline-secondary">
                Dashboard Pancawaluya
            </a>
            <div class="btn-group btn-group-sm" role="group" aria-label="Mode tampilan dashboard kurikulum">
                <a href="{{ route('kurikulum.dashboard', ['mode' => 'ringkas']) }}"
                    class="btn {{ ($mode ?? 'ringkas') === 'ringkas' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Ringkas
                </a>
                <a href="{{ route('kurikulum.dashboard', ['mode' => 'detail']) }}"
                    class="btn {{ ($mode ?? 'ringkas') === 'detail' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Detail
                </a>
            </div>
            @if (!empty($isPklModeActive))
                <span class="badge badge-warning px-3 py-2">
                    Mode PKL XII Aktif
                </span>
            @endif
        </div>
    </div>
@stop

@section('content')
    @php
        $isDetail = ($mode ?? 'ringkas') === 'detail';
    @endphp

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $pendingGuruLeaveRequests }}</h3>
                    <p>Pengajuan Izin Guru Menunggu</p>
                </div>
                <div class="icon"><i class="fas fa-file-signature"></i></div>
                <a href="{{ route('kurikulum.teacher-leave-requests.index') }}" class="small-box-footer">
                    Buka Approval <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $pendingOfficerAttendancePermits }}</h3>
                    <p>Izin Absen Kelas Menunggu</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
                <a href="{{ route('kurikulum.officer-attendance-permits.index') }}" class="small-box-footer">
                    Buka Approval <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $pendingStudentLeaveRequests }}</h3>
                    <p>Pengajuan Izin Siswa Menunggu</p>
                </div>
                <div class="icon"><i class="fas fa-user-clock"></i></div>
                <a href="{{ route('reports.student-leave') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalTeacherAttendancesToday }}</h3>
                    <p>Absen Guru Hari Ini</p>
                </div>
                <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <a href="{{ route('teacher-attendances.index') }}" class="small-box-footer">
                    Lihat Data <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    @if ($isDetail)
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="{{ route('reports.teacher-attendance') }}"
                            class="btn btn-outline-primary btn-block">Laporan
                            Guru</a>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="{{ route('reports.student-attendance') }}"
                            class="btn btn-outline-primary btn-block">Laporan
                            Siswa</a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('reports.teacher-agenda') }}" class="btn btn-outline-primary btn-block">Laporan
                            Agenda
                            Guru</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('footer')
    @include('components.app-footer')
@stop
