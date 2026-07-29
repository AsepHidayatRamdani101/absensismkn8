@extends('adminlte::page')

@section('title', 'Detail Status Absensi Guru Hari Ini')

@section('css')
    <style>
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: box-shadow 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-card p {
            margin-bottom: 0;
            color: #6c757d;
        }

        .stat-card.present {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .stat-card.absent {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .status-badge {
            font-weight: 600;
            padding: 0.35rem 0.9rem;
            border-radius: 0.4rem;
            display: inline-block;
        }

        .table thead th {
            background: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.82rem;
            letter-spacing: 0.03em;
            white-space: nowrap;
            vertical-align: middle;
        }

        .table tbody td {
            vertical-align: middle;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Detail Status Absensi Guru Hari Ini</h1>
            <p class="text-muted mb-0">Daftar guru yang sudah dan belum melakukan absensi hari ini sesuai jadwal.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('attendance-details.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
@stop

@section('content')
    @if ($isWeekendHoliday)
        <div class="alert alert-info">
            Hari {{ $todayDayName }} otomatis libur. Tidak ada jadwal guru untuk hari ini.
        </div>
    @elseif ($todaySchedules->isEmpty())
        <div class="alert alert-info">
            Tidak ada jadwal untuk hari ini ({{ $todayDayName }}).
        </div>
    @else
        <!-- Statistics Cards with filter links -->
        <div class="row mb-4">
            <div class="col-md-4">
                <a href="{{ route('attendance-details.teacher-attendance-detail', ['filter' => 'all']) }}"
                    class="text-decoration-none">
                    <div class="stat-card {{ $filter === 'all' ? 'border-2 border-primary' : '' }}"
                        style="background:#e9f0fb; border:1px solid {{ $filter === 'all' ? '#1a56db' : '#b8d0f7' }};">
                        <h3 style="color:#1a56db;">{{ count($teachersData) }}</h3>
                        <p style="color:#1a56db; font-weight: {{ $filter === 'all' ? '700' : 'normal' }};">Semua Guru</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('attendance-details.teacher-attendance-detail', ['filter' => 'sudah']) }}"
                    class="text-decoration-none">
                    <div class="stat-card present" style="{{ $filter === 'sudah' ? 'border: 2px solid #155724;' : '' }}">
                        <h3>{{ count($teachersWithAttendance) }}</h3>
                        <p style="font-weight: {{ $filter === 'sudah' ? '700' : 'normal' }};">Guru Sudah Absen</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('attendance-details.teacher-attendance-detail', ['filter' => 'belum']) }}"
                    class="text-decoration-none">
                    <div class="stat-card absent" style="{{ $filter === 'belum' ? 'border: 2px solid #721c24;' : '' }}">
                        <h3>{{ count($teachersWithoutAttendance) }}</h3>
                        <p style="font-weight: {{ $filter === 'belum' ? '700' : 'normal' }};">Guru Belum Absen</p>
                    </div>
                </a>
            </div>
        </div>

        @php
            $displayTeachers = match ($filter) {
                'sudah' => $teachersWithAttendance,
                'belum' => $teachersWithoutAttendance,
                default => $teachersData,
            };
            $cardClass = match ($filter) {
                'sudah' => 'card-success',
                'belum' => 'card-danger',
                default => 'card-primary',
            };
            $title = match ($filter) {
                'sudah' => 'Guru yang Sudah Absen',
                'belum' => 'Guru yang Belum Absen',
                default => 'Semua Guru',
            };
        @endphp

        @if ($displayTeachers->isEmpty())
            <div class="alert alert-secondary">Tidak ada data guru untuk filter ini.</div>
        @else
            <div class="card {{ $cardClass }} card-outline mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> {{ $title }} ({{ count($displayTeachers) }})
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama Guru</th>
                                    <th>Mapel</th>
                                    <th>Kelas</th>
                                    <th width="15%">Jam</th>
                                    <th width="15%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($displayTeachers as $item)
                                    @foreach ($item['schedules'] as $index => $schedule)
                                        <tr>
                                            @if ($index === 0)
                                                <td rowspan="{{ count($item['schedules']) }}">
                                                    {{ $loop->parent->iteration }}
                                                </td>
                                                <td rowspan="{{ count($item['schedules']) }}">
                                                    <strong>{{ $item['teacher']->nama_lengkap }}</strong>
                                                </td>
                                            @endif
                                            <td>{{ $schedule['subject']->nama_mapel ?? '-' }}</td>
                                            <td>{{ $schedule['classroom']->nama_kelas ?? '-' }}</td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($schedule['jam_mulai'])->format('H:i') }}
                                                -
                                                {{ \Carbon\Carbon::parse($schedule['jam_selesai'])->format('H:i') }}
                                            </td>
                                            <td>
                                                @if ($item['has_attendance'])
                                                    <span class="badge badge-success status-badge">Sudah Absen</span>
                                                @else
                                                    <span class="badge badge-danger status-badge">Belum Absen</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif
@stop

@section('footer')
    @include('components.app-footer')
@stop
