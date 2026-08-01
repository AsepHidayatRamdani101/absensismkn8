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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

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

        $students = $hasFilter
            ? $this->buildStudentsWithAccountStatus($majorFilter, $classroomFilter)
            : collect();

        if ($hasFilter && $students->isNotEmpty()) {
            $this->ensureQrTokensForCollection($students);
        }

        $majors = Major::orderBy('nama_jurusan')->get();

        $classrooms = Classroom::with('major')
            ->orderBy('tingkat')
            ->orderBy('rombel')
            ->get();

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

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil diperbarui'
        ]);
    }

    public function destroy(Student $student)
    {
        $student->delete();

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
