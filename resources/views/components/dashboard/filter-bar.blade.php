<div class="card card-outline card-primary mb-3">
    <div class="card-body">
        <form id="dashboardFilterForm" class="row g-2">
            <div class="col-md-2">
                <label class="small text-muted">Tahun Ajaran</label>
                <select class="form-control form-control-sm select2" name="academic_year_id"
                    id="filterAcademicYear"></select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Semester</label>
                <select class="form-control form-control-sm" name="semester" id="filterSemester">
                    <option value="">Semua</option>
                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Jurusan</label>
                <select class="form-control form-control-sm select2" name="major_id" id="filterMajor"></select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Kelas</label>
                <select class="form-control form-control-sm select2" name="classroom_id" id="filterClassroom"></select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Siswa</label>
                <select class="form-control form-control-sm select2" name="student_id" id="filterStudent"></select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Guru</label>
                <select class="form-control form-control-sm select2" name="teacher_id" id="filterTeacher"></select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Tanggal Mulai</label>
                <input type="date" class="form-control form-control-sm" name="date_from" id="filterDateFrom">
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Tanggal Selesai</label>
                <input type="date" class="form-control form-control-sm" name="date_to" id="filterDateTo">
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Sumber</label>
                <select class="form-control form-control-sm select2" name="source" id="filterSource"></select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Jenis Kelamin</label>
                <select class="form-control form-control-sm" name="gender" id="filterGender">
                    <option value="">Semua</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Tingkat</label>
                <select class="form-control form-control-sm" name="grade_level" id="filterGradeLevel">
                    <option value="">Semua</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Top Ranking</label>
                <select class="form-control form-control-sm" name="top_limit" id="filterTopLimit">
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="50">Top 50</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Mode Komparasi</label>
                <select class="form-control form-control-sm" name="compare_mode" id="filterCompareMode">
                    <option value="">Default</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end justify-content-end">
                <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="btnResetFilter">Atur
                    Ulang</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnApplyFilter">Terapkan</button>
            </div>
        </form>
    </div>
</div>
