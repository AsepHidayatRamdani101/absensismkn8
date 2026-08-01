<script>
    (() => {
        const state = {
            trendChart: null,
            growthChart: null,
            spChart: null,
            radarChart: null,
            activitiesTable: null,
        };

        function filtersPayload() {
            const data = {};
            $('#dashboardFilterForm').serializeArray().forEach((item) => {
                data[item.name] = item.value;
            });
            data.mode = window.dashboardMode || 'ringkas';
            return data;
        }

        function initSelect2() {
            $('.select2').select2({
                width: '100%'
            });
        }

        function putOptions(selector, rows, mapText) {
            const el = $(selector);
            el.empty();
            el.append('<option value="">Semua</option>');
            rows.forEach((row) => {
                el.append(`<option value="${row.id}">${mapText(row)}</option>`);
            });
            el.trigger('change.select2');
        }

        function loadOptions() {
            return $.get(window.dashboardEndpoints.options, filtersPayload()).done((resp) => {
                putOptions('#filterAcademicYear', resp.academic_years || [], (r) => r.tahun_ajaran);
                putOptions('#filterMajor', resp.majors || [], (r) => r.nama_jurusan);
                putOptions('#filterClassroom', resp.classrooms || [], (r) => r.nama_kelas);
                putOptions('#filterStudent', resp.students || [], (r) => `${r.nis} - ${r.nama_lengkap}`);
                putOptions('#filterTeacher', resp.teachers || [], (r) => `${r.nip} - ${r.nama_lengkap}`);

                const source = $('#filterSource');
                source.empty().append('<option value="">Semua</option>');
                (resp.sources || []).forEach((s) => source.append(`<option value="${s}">${s}</option>`));
                source.trigger('change.select2');

                const gender = $('#filterGender');
                gender.empty().append('<option value="">Semua</option>');
                (resp.genders || []).forEach((s) => gender.append(`<option value="${s}">${s}</option>`));

                const grade = $('#filterGradeLevel');
                grade.empty().append('<option value="">Semua</option>');
                (resp.grade_levels || []).forEach((s) => grade.append(
                    `<option value="${s}">${s}</option>`));

                const topLimit = $('#filterTopLimit');
                const existingTop = topLimit.val() || '10';
                topLimit.empty();
                (resp.top_limits || [10, 20, 50]).forEach((n) => topLimit.append(
                    `<option value="${n}">Top ${n}</option>`));
                topLimit.val(existingTop);

                const compareMode = $('#filterCompareMode');
                const existingCompare = compareMode.val() || '';
                compareMode.empty().append('<option value="">Default</option>');
                (resp.compare_modes || []).forEach((mode) => compareMode.append(
                    `<option value="${mode}">${mode}</option>`));
                compareMode.val(existingCompare);
            });
        }

        function cardTemplate(label, value, colorClass) {
            return `<div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                <div class="card dss-kpi-card border-${colorClass}">
                    <div class="card-body">
                        <div class="label">${label}</div>
                        <div class="metric counter-anim" data-value="${value}">0</div>
                    </div>
                </div>
            </div>`;
        }

        function animateCounters() {
            $('.counter-anim').each(function() {
                const el = $(this);
                const target = Number(el.data('value')) || 0;
                const start = 0;
                const duration = 450;
                const startTime = performance.now();

                const tick = (now) => {
                    const p = Math.min((now - startTime) / duration, 1);
                    const val = start + (target - start) * p;
                    el.text(Number.isInteger(target) ? Math.round(val) : val.toFixed(2));
                    if (p < 1) requestAnimationFrame(tick);
                };

                requestAnimationFrame(tick);
            });
        }

        function renderKpi(kpi) {
            const items = [
                ['Total Siswa', kpi.total_students, 'primary'],
                ['Total Guru', kpi.total_teachers, 'info'],
                ['Total Penghargaan', kpi.total_rewards, 'success'],
                ['Total Pelanggaran', kpi.total_violations, 'danger'],
                ['SP1', kpi.total_sp1, 'warning'],
                ['SP2', kpi.total_sp2, 'warning'],
                ['SP3', kpi.total_sp3, 'danger'],
                ['Rata-rata Karakter', kpi.character_average, 'secondary'],
                ['Validasi Tertunda', kpi.pending_validation, 'dark'],
            ];

            const html = items.map((x) => cardTemplate(x[0], x[1], x[2])).join('');
            $('#kpiCards').html(html);
            animateCounters();
        }

        function safeDestroy(chart) {
            if (chart) chart.destroy();
        }

        function renderCharts(trends, radar) {
            if (window.dashboardMode !== 'detail') {
                safeDestroy(state.trendChart);
                safeDestroy(state.growthChart);
                safeDestroy(state.spChart);
                safeDestroy(state.radarChart);
                state.trendChart = null;
                state.growthChart = null;
                state.spChart = null;
                state.radarChart = null;
                return;
            }

            const trendEl = document.getElementById('trendChart');
            const growthEl = document.getElementById('growthChart');
            const spEl = document.getElementById('spChart');
            const radarEl = document.getElementById('radarChart');

            if (!trendEl || !growthEl || !spEl || !radarEl) {
                return;
            }

            safeDestroy(state.trendChart);
            safeDestroy(state.growthChart);
            safeDestroy(state.spChart);
            safeDestroy(state.radarChart);

            state.trendChart = new Chart(trendEl, {
                type: 'line',
                data: {
                    labels: trends.labels || [],
                    datasets: [{
                            label: 'Penghargaan',
                            data: trends.reward_trend || [],
                            borderColor: '#1e9f6e',
                            backgroundColor: 'rgba(30,159,110,.15)',
                            tension: .35
                        },
                        {
                            label: 'Pelanggaran',
                            data: trends.violation_trend || [],
                            borderColor: '#d64545',
                            backgroundColor: 'rgba(214,69,69,.15)',
                            tension: .35
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                },
            });

            state.growthChart = new Chart(growthEl, {
                type: 'bar',
                data: {
                    labels: trends.labels || [],
                    datasets: [{
                        label: 'Pertumbuhan Karakter',
                        data: trends.character_growth || [],
                        backgroundColor: '#3b82f6'
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                },
            });

            state.spChart = new Chart(spEl, {
                type: 'doughnut',
                data: {
                    labels: ['SP1', 'SP2', 'SP3'],
                    datasets: [{
                        data: [trends.sp_distribution?.SP1 || 0, trends.sp_distribution?.SP2 || 0,
                            trends.sp_distribution?.SP3 || 0
                        ],
                        backgroundColor: ['#f59e0b', '#ef4444', '#7f1d1d']
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                },
            });

            state.radarChart = new Chart(radarEl, {
                type: 'radar',
                data: {
                    labels: radar.labels || [],
                    datasets: [{
                            label: 'Saat Ini',
                            data: radar.current || [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,.15)'
                        },
                        {
                            label: 'Semester Sebelumnya',
                            data: radar.previous || [],
                            borderColor: '#64748b',
                            backgroundColor: 'rgba(100,116,139,.12)'
                        },
                        {
                            label: 'Rata-rata Sekolah',
                            data: radar.school_average || [],
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22,163,74,.1)'
                        },
                        {
                            label: 'Rata-rata Kelas',
                            data: radar.class_average || [],
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249,115,22,.1)'
                        },
                        {
                            label: 'Rata-rata Jurusan',
                            data: radar.department_average || [],
                            borderColor: '#9333ea',
                            backgroundColor: 'rgba(147,51,234,.08)'
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true
                        }
                    }
                },
            });
        }

        function renderTable(selector, rows, mapper) {
            const html = (rows || []).map(mapper).join('');
            $(`${selector} tbody`).html(html ||
                '<tr><td colspan="6" class="text-center text-muted">Tidak ada data</td></tr>');
        }

        function renderRankings(rankings) {
            if (window.dashboardMode !== 'detail') {
                return;
            }

            renderTable('#tblBestStudents', rankings.best_students, (r) =>
                `<tr><td>${r.student}</td><td>${r.score}</td><td>${r.reward_count}</td><td>${r.violation_count}</td></tr>`
            );
            renderTable('#tblHighestViolations', rankings.highest_violations, (r) =>
                `<tr><td>${r.student}</td><td>${r.total}</td></tr>`);
            renderTable('#tblHighestRewards', rankings.highest_rewards, (r) =>
                `<tr><td>${r.student}</td><td>${r.total}</td></tr>`);
            renderTable('#tblActiveTeachers', rankings.most_active_teachers, (r) =>
                `<tr><td>${r.teacher}</td><td>${r.total}</td></tr>`);
        }

        function renderAlerts(alerts) {
            const items = [];
            (alerts.near_sp || []).forEach((row) => items.push(
                `<div class="mb-2"><span class="badge badge-danger mr-1">Mendekati SP</span>${row.student} (${row.sp_level})</div>`
            ));
            (alerts.low_character || []).forEach((row) => items.push(
                `<div class="mb-2"><span class="badge badge-warning mr-1">Skor Rendah</span>${row.student} (${row.score})</div>`
            ));
            (alerts.without_reward || []).forEach((row) => items.push(
                `<div class="mb-2"><span class="badge badge-secondary mr-1">Belum Ada Penghargaan</span>${row.student}</div>`
            ));
            $('#alertList').html(items.join('') || '<span class="text-muted">Tidak ada peringatan aktif</span>');
        }

        function renderRecommendations(rows) {
            const priorityLabel = {
                high: 'tinggi',
                medium: 'sedang',
                low: 'rendah',
            };

            const html = (rows || []).map((row) =>
                `<div class="mb-2"><span class="badge badge-${row.priority === 'high' ? 'danger' : (row.priority === 'medium' ? 'warning' : 'info')} mr-1">${priorityLabel[row.priority] || row.priority}</span>${row.message}</div>`
            ).join('');
            $('#recommendationList').html(html || '<span class="text-muted">Tidak ada rekomendasi</span>');
        }

        function renderExecutiveSummary(summary) {
            const narrative = summary?.narrative || [];
            const html = narrative.map((item) => `<li>${item}</li>`).join('');
            $('#executiveSummaryList').html(html || '<li class="text-muted">Belum ada data ringkasan.</li>');
        }

        function renderPredictiveSummary(predictive) {
            const parts = [
                ['Berpotensi menerima SP', predictive?.likely_receive_sp?.length || 0],
                ['Berpotensi membaik', predictive?.likely_to_improve?.length || 0],
                ['Perlu konseling', predictive?.requiring_counseling?.length || 0],
                ['Layak diapresiasi', predictive?.deserving_appreciation?.length || 0],
                ['Karakter menurun', predictive?.declining_character?.length || 0],
            ];

            const html = parts
                .map((row) => `<div class="mb-1"><strong>${row[0]}:</strong> ${row[1]} siswa</div>`)
                .join('');

            $('#predictiveSummary').html(html || '<span class="text-muted">Belum ada data prediksi.</span>');
        }

        function renderComparativeSummary(comparative) {
            const compareMode = ($('#filterCompareMode').val() || '').trim();
            const fallback = 'class_vs_class';
            const key = compareMode !== '' ? compareMode : fallback;
            const rows = comparative?.[key] || [];

            const html = rows.slice(0, 10).map((row) => {
                const label = row.label ?? row.kelas ?? row.jurusan ?? row.guru ?? '-';
                const value = row.nilai ?? row.total ?? '-';
                return `<tr><td>${label}</td><td>${value}</td></tr>`;
            }).join('');

            $('#tblComparativePrimary tbody').html(html ||
                '<tr><td colspan="2" class="text-center text-muted">Belum ada data</td></tr>');
        }

        function renderCorrelationSummary(correlation) {
            const matrix = correlation?.coefficient || {};
            const rows = Object.keys(matrix).map((key) => {
                return `<tr><td>${key.replaceAll('_', ' ')}</td><td>${matrix[key]}</td></tr>`;
            }).join('');

            $('#tblCorrelationSummary tbody').html(rows ||
                '<tr><td colspan="2" class="text-center text-muted">Belum ada data</td></tr>');
        }

        function initActivitiesTable() {
            if (window.dashboardMode !== 'detail' || $('#tblActivities').length === 0) {
                return;
            }

            state.activitiesTable = $('#tblActivities').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                info: true,
                language: {
                    processing: 'Memproses...',
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                    infoFiltered: '(disaring dari _MAX_ total data)',
                    loadingRecords: 'Memuat...',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Tidak ada data',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya',
                    },
                },
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: window.dashboardEndpoints.activities,
                    data: function(d) {
                        Object.assign(d, filtersPayload());
                    },
                },
                columns: [{
                        data: 'date'
                    },
                    {
                        data: 'student'
                    },
                    {
                        data: 'action'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'source'
                    },
                    {
                        data: 'actor'
                    },
                ],
            });
        }

        function renderActivities() {
            if (window.dashboardMode === 'detail' && state.activitiesTable) {
                state.activitiesTable.ajax.reload(null, false);
            }
        }

        function refreshDashboard() {
            return $.get(window.dashboardEndpoints.data, filtersPayload()).done((resp) => {
                renderKpi(resp.kpi || {});
                renderCharts(resp.trends || {}, resp.radar || {});
                renderRankings(resp.rankings || {});
                renderAlerts(resp.alerts || {});
                renderRecommendations(resp.recommendations || []);
                renderExecutiveSummary(resp.executive_summary || {});
                renderPredictiveSummary(resp.predictive_analytics || {});
                renderComparativeSummary(resp.comparative_analytics || {});
                renderCorrelationSummary(resp.correlation_analytics || {});
                renderActivities();
                $('#lastRefresh').text(new Date().toLocaleTimeString());
            });
        }

        function buildExportUrl(baseUrl) {
            const query = $.param(filtersPayload());
            return `${baseUrl}?${query}`;
        }

        function downloadCanvasAsPng(canvas, fileName) {
            if (!canvas) {
                return;
            }

            const link = document.createElement('a');
            link.download = fileName;
            link.href = canvas.toDataURL('image/png', 1.0);
            link.click();
        }

        $(function() {
            initSelect2();
            initActivitiesTable();

            loadOptions().then(refreshDashboard);

            $('#btnApplyFilter').on('click', function() {
                refreshDashboard();
                Swal.fire({
                    toast: true,
                    timer: 1200,
                    position: 'top-end',
                    showConfirmButton: false,
                    icon: 'success',
                    title: 'Dashboard diperbarui'
                });
            });

            $('#btnResetFilter').on('click', function() {
                document.getElementById('dashboardFilterForm').reset();
                $('.select2').val('').trigger('change');
                refreshDashboard();
            });

            $('#btnExportCsv').on('click', function() {
                window.location.href = buildExportUrl(window.dashboardEndpoints.exportCsv);
            });

            $('#btnExportPng').on('click', function() {
                const chartCandidates = [
                    document.getElementById('radarChart'),
                    document.getElementById('trendChart'),
                    document.getElementById('growthChart'),
                    document.getElementById('spChart'),
                ];
                const target = chartCandidates.find((canvas) =>
                canvas instanceof HTMLCanvasElement);

                if (!target) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Chart belum tersedia',
                        text: 'Pindah ke mode Detail untuk ekspor PNG chart.',
                    });
                    return;
                }

                downloadCanvasAsPng(target, `analytics-pancawaluya-${Date.now()}.png`);
            });

            $('#btnExportXlsx').on('click', function() {
                window.location.href = buildExportUrl(window.dashboardEndpoints.exportXlsx);
            });

            $('#btnExportPdf').on('click', function() {
                window.location.href = buildExportUrl(window.dashboardEndpoints.exportPdf);
            });

            $('#btnPrintAnalytics').on('click', function() {
                window.print();
            });

            setInterval(refreshDashboard, 60000);
        });
    })();
</script>
