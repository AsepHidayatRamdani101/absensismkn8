<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Exports\TemplateExport;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Support\ReferenceCache;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Permission\Models\Role;
use Throwable;

class StudentController extends Controller
{
    private const PDF_EXPORT_MAX_ROWS = 800;

    private function resolveAuthenticatedStudent(): ?Student
    {
        return Student::query()
            ->with('classroom.major')
            ->where('nisn', auth()->user()->email)
            ->orWhere('nis', auth()->user()->email)
            ->first();
    }

    public function index(Request $request)
    {
        $majorFilter     = (string) $request->input('major_id', '');
        $classroomFilter = (string) $request->input('classroom_id', '');
        $hasFilter       = $majorFilter !== '' || $classroomFilter !== '';

        $students = collect();

        $majors = Cache::remember('students:majors:list', now()->addMinutes(30), function () {
            return Major::orderBy('nama_jurusan')->get();
        });

        $classrooms = Cache::remember('students:classrooms:list', now()->addMinutes(30), function () {
            return Classroom::with('major')
                ->orderBy('tingkat')
                ->orderBy('rombel')
                ->get();
        });

        return view(
            'admin.students.index',
            compact(
                'students',
                'classrooms',
                'majors',
                'majorFilter',
                'classroomFilter',
                'hasFilter'
            )
        );
    }

    public function datatable(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max(min((int) $request->input('length', 10), 100), 10);
        $majorFilter = (string) $request->input('major_id', '');
        $classroomFilter = (string) $request->input('classroom_id', '');
        $searchValue = trim((string) data_get($request->input('search', []), 'value', ''));

        $query = Student::query()
            ->with('classroom.major')
            ->select('students.*');

        if ($majorFilter !== '') {
            $query->whereHas('classroom', fn($q) => $q->where('major_id', $majorFilter));
        }

        if ($classroomFilter !== '') {
            $query->where('classroom_id', $classroomFilter);
        }

        $recordsTotal = (clone $query)->count();

        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('students.nama_lengkap', 'like', "%{$searchValue}%")
                    ->orWhere('students.nis', 'like', "%{$searchValue}%")
                    ->orWhere('students.nisn', 'like', "%{$searchValue}%")
                    ->orWhere('students.no_hp', 'like', "%{$searchValue}%")
                    ->orWhereHas('classroom', function ($classroomQuery) use ($searchValue) {
                        $classroomQuery->where('nama_kelas', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = (int) data_get($request->input('order', []), '0.column', 1);
        $orderDirection = strtolower((string) data_get($request->input('order', []), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columnOrderMap = [
            1 => 'students.id',
            2 => 'students.nisn',
            3 => 'students.nama_lengkap',
            5 => 'students.jenis_kelamin',
            7 => 'students.jabatan_kelas',
            8 => 'students.no_hp',
        ];

        $orderColumn = $columnOrderMap[$orderColumnIndex] ?? 'students.id';
        $query->orderBy($orderColumn, $orderDirection);

        $students = $query
            ->skip($start)
            ->take($length)
            ->get();

        if ($students->isNotEmpty()) {
            $this->ensureQrTokensForCollection($students);
        }

        $accountIdentifiers = $students
            ->flatMap(fn(Student $student) => array_filter([
                trim((string) $student->nisn),
                trim((string) $student->nis),
            ]))
            ->unique()
            ->values();

        $existingAccounts = User::query()
            ->whereIn('email', $accountIdentifiers)
            ->pluck('email')
            ->all();
        $existingAccountLookup = array_fill_keys($existingAccounts, true);

        $rows = $students->values()->map(function (Student $student, int $index) use ($start, $existingAccountLookup) {
            $studentNisn = trim((string) $student->nisn);
            $studentNis = trim((string) $student->nis);
            $hasAccount = isset($existingAccountLookup[$studentNisn]) || isset($existingAccountLookup[$studentNis]);

            $statusHtml = $hasAccount
                ? '<span class="badge badge-success">Sudah</span>'
                : '<span class="badge badge-secondary">Belum</span>';

            $qrHtml = '<span class="text-muted">-</span>';
            if (!empty($student->qr_token)) {
                $qrSvg = QrCode::size(70)->margin(1)->generate(route('students.qr.show', ['token' => $student->qr_token]));
                $qrHtml = '<div class="mb-1" style="display:inline-block; line-height: 0;">' . $qrSvg . '</div>'
                    . '<a href="' . route('students.qr.show', ['token' => $student->qr_token]) . '" target="_blank" class="btn btn-info btn-xs d-block mt-1">Lihat</a>';
            }

            $aksiHtml = '<button class="btn btn-warning btn-xs btn-edit" data-id="' . $student->id . '"><i class="fas fa-edit"></i></button> '
                . '<button class="btn btn-danger btn-xs btn-delete" data-id="' . $student->id . '"><i class="fas fa-trash"></i></button> '
                . '<a href="' . route('students.qr-card', $student) . '" target="_blank" class="btn btn-dark btn-xs" title="Cetak Kartu QR"><i class="fas fa-qrcode"></i></a>';

            return [
                'checkbox' => '<input type="checkbox" class="check-student" value="' . $student->id . '">',
                'no' => $start + $index + 1,
                'nisn' => e((string) $student->nisn),
                'nama' => e((string) $student->nama_lengkap),
                'status' => $statusHtml,
                'jk' => e((string) $student->jenis_kelamin),
                'kelas' => e((string) ($student->classroom->nama_kelas ?? '-')),
                'jabatan' => e((string) $student->jabatan_kelas_label),
                'no_hp' => e((string) ($student->no_hp ?? '-')),
                'qr' => $qrHtml,
                'aksi' => $aksiHtml,
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function exportAccountsPdf(Request $request)
    {
        $majorFilter = (string) $request->input('major_id', '');
        $classroomFilter = (string) $request->input('classroom_id', '');

        $students = $this->buildStudentsWithAccountStatus($majorFilter, $classroomFilter);

        if ($students->count() > self::PDF_EXPORT_MAX_ROWS) {
            return redirect()
                ->route('students.index', $request->query())
                ->with(
                    'error',
                    'Export PDF akun dibatasi maksimal ' . self::PDF_EXPORT_MAX_ROWS . ' data per file. Gunakan filter jurusan/kelas terlebih dahulu.'
                );
        }

        $pdf = Pdf::loadView('admin.students.pdf-accounts', [
            'students' => $students,
            'majorFilter' => $majorFilter,
            'classroomFilter' => $classroomFilter,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('akun-siswa.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|unique:students',
            'nisn' => 'nullable',
            'nama_lengkap' => 'required',
            'nama_orang_tua_wali' => 'nullable|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'classroom_id' => 'required|exists:classrooms,id',
            'jabatan_kelas' => 'nullable|in:ketua_kelas,sekretaris,bendahara',
            'alamat' => 'nullable',
            'no_hp' => 'nullable',
            'no_hp_orang_tua' => 'nullable|string|max:255',
            'tinggi_badan' => 'nullable|numeric|min:0',
            'berat_badan' => 'nullable|numeric|min:0',

        ]);

        Student::create($validated);
        ReferenceCache::forgetStudentReferences();

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil ditambahkan'
        ]);
    }

    public function edit(Student $student)
    {
        return response()->json($student);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([

            'nis' => [
                'required',
                Rule::unique('students')
                    ->ignore($student->id),
            ],

            'nisn' => 'nullable',

            'nama_lengkap' => 'required',

            'nama_orang_tua_wali' => 'nullable|string|max:255',

            'jenis_kelamin' => 'required|in:L,P',

            'classroom_id' => 'required|exists:classrooms,id',

            'jabatan_kelas' => 'nullable|in:ketua_kelas,sekretaris,bendahara',

            'alamat' => 'nullable',

            'no_hp' => 'nullable',

            'no_hp_orang_tua' => 'nullable|string|max:255',

            'tinggi_badan' => 'nullable|numeric|min:0',

            'berat_badan' => 'nullable|numeric|min:0',

        ]);

        $student->update($validated);
        ReferenceCache::forgetStudentReferences();

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil diperbarui'
        ]);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        ReferenceCache::forgetStudentReferences();

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil dihapus'
        ]);
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:students,id',
        ]);

        Student::whereIn('id', $request->ids)->delete();
        ReferenceCache::forgetStudentReferences();

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' data siswa berhasil dihapus',
        ]);
    }

    public function updateOwnIdentity(Request $request): RedirectResponse
    {
        $student = $this->resolveAuthenticatedStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa untuk akun ini tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_orang_tua_wali' => 'nullable|string|max:255',
            'alamat'              => 'nullable|string',
            'no_hp'               => 'nullable|string|max:255',
            'no_hp_orang_tua'     => 'nullable|string|max:255',
            'tinggi_badan'        => 'nullable|numeric|min:0',
            'berat_badan'         => 'nullable|numeric|min:0',
            'jenis_kelamin'       => 'nullable|in:L,P',
            'current_password'    => 'nullable|string',
            'password'            => 'nullable|string|min:8|confirmed',
        ]);

        $student->update(collect($validated)->except(['current_password', 'password', 'password_confirmation'])->filter(fn($v) => $v !== null)->toArray());

        // Handle password change
        if (!empty($validated['password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], auth()->user()->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.'])->withInput();
            }
            auth()->user()->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()->route('siswa.identity.edit')->with('success', 'Identitas siswa berhasil diperbarui.');
    }

    public function editOwnIdentity()
    {
        $student = $this->resolveAuthenticatedStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa untuk akun ini tidak ditemukan.');
        }

        $student->ensureQrToken();

        return view('siswa.identity.edit', compact('student'));
    }

    public function downloadOwnQrCode()
    {
        $student = $this->resolveAuthenticatedStudent();

        if (!$student) {
            return redirect()->route('siswa.dashboard')->with('error', 'Data siswa untuk akun ini tidak ditemukan.');
        }

        $token = $student->ensureQrToken();
        $qrUrl = route('students.qr.show', ['token' => $token]);

        $requestedFormat = strtolower((string) request()->query('format', 'png'));
        if (!in_array($requestedFormat, ['png', 'jpg', 'jpeg'], true)) {
            $requestedFormat = 'png';
        }

        $qrPng = $this->generateQrPngBinary($qrUrl);
        if ($qrPng === null) {
            return redirect()->route('siswa.identity.edit')->with(
                'error',
                'Gagal membuat file QR PNG/JPG. Coba lagi beberapa saat.'
            );
        }

        $binary = $qrPng;
        $extension = 'png';
        $contentType = 'image/png';

        if (in_array($requestedFormat, ['jpg', 'jpeg'], true)) {
            $qrJpg = $this->convertPngToJpeg($qrPng);
            if ($qrJpg !== null) {
                $binary = $qrJpg;
                $extension = 'jpg';
                $contentType = 'image/jpeg';
            }
        }

        $identifier = trim((string) ($student->nisn ?: $student->nis ?: $student->id));
        $filename = 'qr-siswa-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $identifier) . '.' . $extension;

        return response($binary, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function generateQrPngBinary(string $qrUrl): ?string
    {
        if (extension_loaded('imagick')) {
            try {
                return QrCode::format('png')
                    ->size(900)
                    ->margin(1)
                    ->generate($qrUrl);
            } catch (Throwable $e) {
                // Fall through to HTTP-based PNG generator.
            }
        }

        $response = Http::timeout(15)->get('https://quickchart.io/qr', [
            'text' => $qrUrl,
            'size' => 900,
            'margin' => 1,
            'format' => 'png',
            'ecLevel' => 'M',
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->body();
    }

    private function convertPngToJpeg(string $pngBinary): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $sourceImage = @imagecreatefromstring($pngBinary);
        if ($sourceImage === false) {
            return null;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        $targetImage = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($targetImage, 255, 255, 255);
        imagefilledrectangle($targetImage, 0, 0, $width, $height, $white);
        imagecopy($targetImage, $sourceImage, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($targetImage, null, 95);
        $jpegBinary = (string) ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return $jpegBinary !== '' ? $jpegBinary : null;
    }

    public function showPublicByQr(string $token)
    {
        $student = Student::query()
            ->with('classroom.major')
            ->where('qr_token', $token)
            ->firstOrFail();

        $schoolSetting = SchoolSetting::query()->first();

        return view('public.student-qr-profile', compact('student', 'schoolSetting'));
    }

    public function qrCard(Student $student)
    {
        $student->load('classroom.major');
        $student->ensureQrToken();

        $schoolSetting = SchoolSetting::query()->first();

        return view('admin.students.qr-card', compact('student', 'schoolSetting'));
    }

    public function qrCardsByClassroom(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'major_id' => 'nullable|exists:majors,id',
        ]);

        $classroom = Classroom::query()
            ->with('major')
            ->findOrFail((int) $validated['classroom_id']);

        if (!empty($validated['major_id']) && (int) $validated['major_id'] !== (int) $classroom->major_id) {
            return redirect()->route('students.index', $request->query())->with(
                'error',
                'Kelas tidak sesuai dengan filter jurusan yang dipilih.'
            );
        }

        $students = Student::query()
            ->with('classroom.major')
            ->where('classroom_id', $classroom->id)
            ->orderBy('nama_lengkap')
            ->get();

        if ($students->isEmpty()) {
            return redirect()->route('students.index', $request->query())->with(
                'error',
                'Tidak ada data siswa pada kelas terpilih.'
            );
        }

        $this->ensureQrTokensForCollection($students);

        $schoolSetting = SchoolSetting::query()->first();

        return view('admin.students.qr-cards-classroom', compact('students', 'classroom', 'schoolSetting'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $importer = new StudentsImport();
        $importer->importFile($request->file('file')->getRealPath());
        ReferenceCache::forgetStudentReferences();

        return redirect()->route('students.index')->with('success', $importer->getSuccessMessage());
    }

    public function export()
    {
        return Excel::download(new StudentsExport(), 'master-siswa.xlsx');
    }

    public function template()
    {
        return Excel::download(
            new TemplateExport(
                ['nis', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'classroom_kode_kelas', 'jabatan_kelas'],
                [[
                    '24001',
                    '9988776655',
                    'Budi Santoso',
                    'L',
                    'X-RPL-1',
                    'KM',
                ]]
            ),
            'format-import-siswa.xlsx'
        );
    }

    public function generateAccounts()
    {
        $role = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

        $batchSize = (int) request()->input('batch_size', 300);
        $batchSize = max(50, min(1000, $batchSize));

        $afterId = (int) request()->input('after_id', 0);
        $students = Student::query()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($batchSize)
            ->get(['id', 'nis', 'nisn', 'nama_lengkap']);

        $total = Student::query()->count();

        if ($students->isEmpty()) {
            $payload = [
                'done' => true,
                'next_after_id' => $afterId,
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total' => $total,
                'processed_until' => Student::query()->max('id') ?? 0,
                'message' => 'Generate akun siswa selesai.',
            ];

            if (request()->expectsJson()) {
                return response()->json($payload);
            }

            return redirect()->route('students.index')->with('success', $payload['message']);
        }

        $usersByEmail = [];
        $upsertRowsByEmail = [];
        $skipped = 0;

        foreach ($students as $student) {
            $username = trim((string) $student->nisn);

            if ($username === '') {
                $username = trim((string) $student->nis);
            }

            if ($username === '') {
                $skipped++;
                continue;
            }

            // Keep one account per username when duplicate NIS/NISN exists.
            if (isset($upsertRowsByEmail[$username])) {
                continue;
            }

            $usersByEmail[] = $username;
            $upsertRowsByEmail[$username] = [
                'email' => $username,
                'name' => $student->nama_lengkap,
            ];
        }

        $existingEmailLookup = [];
        if (!empty($usersByEmail)) {
            $existingEmailLookup = User::query()
                ->whereIn('email', $usersByEmail)
                ->pluck('email')
                ->flip()
                ->all();
        }

        $created = 0;
        $updated = 0;
        $now = now();
        $defaultPasswordHash = Hash::make('siswa12345');
        $upsertRows = [];

        foreach ($upsertRowsByEmail as $email => $row) {
            if (isset($existingEmailLookup[$email])) {
                $updated++;
            } else {
                $created++;
            }

            $upsertRows[] = [
                'email' => $email,
                'name' => $row['name'],
                'password' => $defaultPasswordHash,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($upsertRows)) {
            User::query()->upsert(
                $upsertRows,
                ['email'],
                ['name', 'password', 'updated_at']
            );

            $generatedUserIds = User::query()
                ->whereIn('email', array_keys($upsertRowsByEmail))
                ->pluck('id')
                ->all();

            if (!empty($generatedUserIds)) {
                $roleRows = [];
                foreach ($generatedUserIds as $userId) {
                    $roleRows[] = [
                        'role_id' => $role->id,
                        'model_type' => User::class,
                        'model_id' => $userId,
                    ];
                }

                DB::table('model_has_roles')->insertOrIgnore($roleRows);
            }
        }

        $lastIdInBatch = (int) $students->last()->id;
        $remainingExists = Student::query()->where('id', '>', $lastIdInBatch)->exists();

        $payload = [
            'done' => !$remainingExists,
            'next_after_id' => $lastIdInBatch,
            'processed' => $students->count(),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $total,
            'processed_until' => $lastIdInBatch,
            'message' => 'Batch generate akun siswa diproses.',
        ];

        if (request()->expectsJson()) {
            return response()->json($payload);
        }

        if ($payload['done']) {
            return redirect()->route('students.index')->with(
                'success',
                "Generate akun siswa selesai. Dibuat: {$created}, Diperbarui: {$updated}, Dilewati (NISN/NIS kosong): {$skipped}."
            );
        }

        return redirect()->route('students.index')->with(
            'success',
            "Batch generate akun siswa diproses. Jalankan lagi untuk batch berikutnya. after_id={$lastIdInBatch}."
        );
    }

    private function buildStudentsWithAccountStatus(?string $majorFilter = '', ?string $classroomFilter = '')
    {
        $majorFilter = (string) ($majorFilter ?? '');
        $classroomFilter = (string) ($classroomFilter ?? '');

        $query = Student::with('classroom.major')->latest();

        if ($majorFilter !== '') {
            $query->whereHas('classroom', fn($q) => $q->where('major_id', $majorFilter));
        }

        if ($classroomFilter !== '') {
            $query->where('classroom_id', $classroomFilter);
        }

        $students = $query->get();

        $accountIdentifiers = $students
            ->flatMap(fn(Student $student) => array_filter([
                trim((string) $student->nisn),
                trim((string) $student->nis),
            ]))
            ->unique()
            ->values();

        $existingAccounts = User::query()
            ->whereIn('email', $accountIdentifiers)
            ->pluck('email')
            ->all();

        $existingAccountLookup = array_fill_keys($existingAccounts, true);

        $students->each(function (Student $student) use ($existingAccountLookup) {
            $studentNisn = trim((string) $student->nisn);
            $studentNis = trim((string) $student->nis);

            $student->username_akun = $studentNisn !== '' ? $studentNisn : $studentNis;
            $student->has_account = isset($existingAccountLookup[$studentNisn])
                || isset($existingAccountLookup[$studentNis]);
        });

        return $students;
    }

    private function ensureQrTokensForCollection(Collection $students): void
    {
        $students
            ->filter(fn(Student $student) => empty($student->qr_token))
            ->each(fn(Student $student) => $student->ensureQrToken());
    }
}
