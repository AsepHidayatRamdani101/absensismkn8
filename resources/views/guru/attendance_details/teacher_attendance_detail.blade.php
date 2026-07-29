@extends('adminlte::page')

@section('title', 'Detail Absensi Guru')

@section('css')
    <style>
        .status-badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
        }

        .status-hadir {
            background-color: #d4edda;
            color: #155724;
        }

        .status-belum {
            background-color: #f8d7da;
            color: #721c24;
        }

        .card-teacher-status {
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card-teacher-status.absent {
            border-left-color: #dc3545;
        }

        .card-teacher-status.present {
            border-left-color: #28a745;
        }

        #tableTeacherAttendance thead th {
            background-color: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.03em;
            white-space: nowrap;
            vertical-align: middle;
        }

        #tableTeacherAttendance tbody tr:hover {
            background-color: #f9fafb;
        }

        #tableTeacherAttendance tbody td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
    </style>
@stop

@section('content_header')
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <h1 class="mb-1">Detail Status Absensi Guru</h1>
            <p class="text-muted mb-0">Daftar guru yang sudah dan belum melakukan absensi hari ini sesuai jadwal.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="{{ route('guru.attendance-details.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <span class="badge badge-light border px-3 py-2">
                <span>{{ $today->format('d M Y') }}</span> - <span>{{ $todayDayName }}</span>
                <span class="ml-2" id="guruRealtimeClock"></span>
            </span>
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
            Tidak ada jadwal untuk hari ini.
        </div>
    @else
        <!-- Statistics Cards with filter links -->
        <div class="row mb-4">
            <div class="col-md-4">
                <a href="{{ route('guru.attendance-details.teacher-attendance-detail', ['filter' => 'all']) }}"
                    class="text-decoration-none">
                    <div class="stat-card {{ $filter === 'all' ? 'border border-primary' : '' }}"
                        style="background:#e9f0fb; border:1px solid #b8d0f7;">
                        <h3 style="color:#1a56db;">{{ count($teachersData) }}</h3>
                        <p style="color:#1a56db;">Semua Guru</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('guru.attendance-details.teacher-attendance-detail', ['filter' => 'sudah']) }}"
                    class="text-decoration-none">
                    <div class="stat-card present {{ $filter === 'sudah' ? 'border border-success' : '' }}">
                        <h3>{{ count($teachersWithAttendance) }}</h3>
                        <p>Guru Sudah Absen</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('guru.attendance-details.teacher-attendance-detail', ['filter' => 'belum']) }}"
                    class="text-decoration-none">
                    <div class="stat-card absent {{ $filter === 'belum' ? 'border border-danger' : '' }}">
                        <h3>{{ count($teachersWithoutAttendance) }}</h3>
                        <p>Guru Belum Absen</p>
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
            $badgeClass = match ($filter) {
                'sudah' => 'badge-success',
                'belum' => 'badge-danger',
                default => 'badge-secondary',
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
                                                @if ($schedule['has_attendance'])
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

@section('js')
    <script>
        (function() {
            const clockElement = document.getElementById('guruRealtimeClock');

            if (!clockElement) {
                return;
            }

            const timeFormatter = new Intl.DateTimeFormat('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Jakarta'
            });

            const updateClock = () => {
                const currentDate = new Date();
                clockElement.textContent = timeFormatter.format(currentDate) + ' WIB';
            };

            updateClock();
            setInterval(updateClock, 1000);
        })();
    </script>
@stop
