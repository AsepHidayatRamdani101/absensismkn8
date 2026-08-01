<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppSettingController extends Controller
{
    private const REF_BYTES = 10 * 1024 * 1024; // 10 MB reference for progress bar

    public function index()
    {
        $cache   = $this->dirStats(storage_path('framework/cache/data'));
        $views   = $this->dirStats(storage_path('framework/views'));
        $session = $this->sessionStats();

        $configCachePath = base_path('bootstrap/cache/config.php');
        $routeCachePath  = base_path('bootstrap/cache/routes-v7.php');

        $configCache = [
            'exists' => File::exists($configCachePath),
            'size'   => File::exists($configCachePath) ? $this->humanSize(File::size($configCachePath)) : '-',
            'path'   => 'bootstrap/cache/config.php',
        ];
        $routeCache = [
            'exists' => File::exists($routeCachePath),
            'size'   => File::exists($routeCachePath) ? $this->humanSize(File::size($routeCachePath)) : '-',
            'path'   => 'bootstrap/cache/routes-v7.php',
        ];

        $refBytes = self::REF_BYTES;

        $fonnte = [
            'enabled' => (bool) config('services.fonnte.enabled', false),
            'token' => (string) config('services.fonnte.token', ''),
            'api_url' => (string) config('services.fonnte.api_url', 'https://api.fonnte.com/send'),
            'default_country_code' => (string) config('services.fonnte.default_country_code', '62'),
            'reminder_enabled' => (bool) config('services.fonnte.reminder_enabled', false),
            'reminder_time' => (string) config('services.fonnte.reminder_time', '14:00'),
            'school_name' => (string) config('services.fonnte.school_name', 'SMKN 8 GARUT'),
        ];

        return view('admin.app_settings.index', compact(
            'cache',
            'views',
            'session',
            'configCache',
            'routeCache',
            'refBytes',
            'fonnte'
        ));
    }

    public function updateFonnte(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'token' => 'nullable|string|max:255',
            'api_url' => 'required|url|max:255',
            'default_country_code' => 'required|regex:/^\d{1,4}$/',
            'reminder_enabled' => 'nullable|boolean',
            'reminder_time' => 'required|date_format:H:i',
            'school_name' => 'required|string|max:120',
        ]);

        $updates = [
            'FONNTE_ENABLED' => $request->boolean('enabled') ? 'true' : 'false',
            'FONNTE_TOKEN' => (string) ($validated['token'] ?? ''),
            'FONNTE_API_URL' => (string) $validated['api_url'],
            'FONNTE_DEFAULT_COUNTRY_CODE' => (string) $validated['default_country_code'],
            'FONNTE_REMINDER_ENABLED' => $request->boolean('reminder_enabled') ? 'true' : 'false',
            'FONNTE_REMINDER_TIME' => (string) $validated['reminder_time'],
            'FONNTE_SCHOOL_NAME' => (string) $validated['school_name'],
        ];

        foreach ($updates as $key => $value) {
            $this->upsertEnvValue($key, $value);
        }

        Artisan::call('config:clear');

        return redirect()->route('app-settings.index')->with('success', 'Konfigurasi WA Fonnte berhasil disimpan.');
    }

    public function sendFonnteTest(Request $request)
    {
        $validated = $request->validate([
            'test_target' => 'required|string|max:30',
            'test_message' => 'nullable|string|max:1000',
        ]);

        $config = $this->fonnteConfig();
        $enabled = $config['enabled'];
        $token = $config['token'];
        $apiUrl = $config['api_url'];
        $countryCode = $config['country_code'];
        $schoolName = (string) config('services.fonnte.school_name', 'SMKN 8 GARUT');

        if (!$enabled) {
            return redirect()->route('app-settings.index')->with('error', 'Integrasi WA Fonnte belum diaktifkan.');
        }

        if ($token === '') {
            return redirect()->route('app-settings.index')->with('error', 'Token Fonnte kosong. Mohon isi token terlebih dahulu.');
        }

        $target = $this->normalizePhone($validated['test_target'], $countryCode);
        if ($target === null) {
            return redirect()->route('app-settings.index')->with('error', 'Nomor tujuan tidak valid.');
        }

        $message = trim((string) ($validated['test_message'] ?? ''));
        if ($message === '') {
            $message = "Tes koneksi WA Fonnte berhasil dari sistem {$schoolName}.";
        }

        if (!$this->sendFonnteMessage($token, $apiUrl, $countryCode, $target, $message)) {
            return redirect()->route('app-settings.index')
                ->with('error', 'Tes kirim gagal. Periksa token/API URL Fonnte Anda.');
        }

        return redirect()->route('app-settings.index')->with('success', 'Tes kirim WA berhasil ke nomor tujuan.');
    }

    public function sendFonnteTestToTeacherSamples(Request $request)
    {
        $validated = $request->validate([
            'sample_test_message' => 'nullable|string|max:1000',
        ]);

        $config = $this->fonnteConfig();
        $enabled = $config['enabled'];
        $token = $config['token'];
        $apiUrl = $config['api_url'];
        $countryCode = $config['country_code'];
        $schoolName = (string) config('services.fonnte.school_name', 'SMKN 8 GARUT');
        $batchDelayMs = max(0, min(5000, (int) config('services.fonnte.test_batch_delay_ms', 1200)));

        if (!$enabled) {
            return redirect()->route('app-settings.index')->with('error', 'Integrasi WA Fonnte belum diaktifkan.');
        }

        if ($token === '') {
            return redirect()->route('app-settings.index')->with('error', 'Token Fonnte kosong. Mohon isi token terlebih dahulu.');
        }

        $teachers = Teacher::query()
            ->whereNotNull('no_hp')
            ->whereRaw("TRIM(no_hp) != ''")
            ->orderBy('id')
            ->limit(3)
            ->get(['id', 'nama_lengkap', 'no_hp']);

        if ($teachers->isEmpty()) {
            return redirect()->route('app-settings.index')->with('error', 'Tidak ada data guru dengan nomor HP untuk dites.');
        }

        $templateMessage = trim((string) ($validated['sample_test_message'] ?? ''));
        $sent = 0;
        $failed = 0;

        $totalTeachers = $teachers->count();

        foreach ($teachers as $index => $teacher) {
            $target = $this->normalizePhone((string) $teacher->no_hp, $countryCode);

            if ($target === null) {
                $failed++;
                continue;
            }

            $message = $templateMessage !== ''
                ? $templateMessage
                : "Tes broadcast WA Fonnte dari sistem {$schoolName} untuk {$teacher->nama_lengkap}.";

            if ($this->sendFonnteMessage($token, $apiUrl, $countryCode, $target, $message)) {
                $sent++;
            } else {
                $failed++;
            }

            if ($batchDelayMs > 0 && $index < ($totalTeachers - 1)) {
                usleep($batchDelayMs * 1000);
            }
        }

        if ($sent === 0) {
            return redirect()->route('app-settings.index')->with('error', 'Tes kirim ke sampel guru gagal.');
        }

        return redirect()->route('app-settings.index')->with(
            'success',
            "Tes kirim sampel guru selesai. Berhasil: {$sent}, Gagal: {$failed}."
        );
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        return redirect()->route('app-settings.index')->with('success', 'Cache aplikasi berhasil dihapus.');
    }

    public function clearView()
    {
        Artisan::call('view:clear');
        return redirect()->route('app-settings.index')->with('success', 'Cache view berhasil dihapus.');
    }

    public function clearConfig()
    {
        Artisan::call('config:clear');
        return redirect()->route('app-settings.index')->with('success', 'Config cache berhasil dihapus.');
    }

    public function clearRoute()
    {
        Artisan::call('route:clear');
        return redirect()->route('app-settings.index')->with('success', 'Route cache berhasil dihapus.');
    }

    public function clearSession(Request $request)
    {
        $currentSessionId = $request->session()->getId();
        DB::table('sessions')->where('id', '!=', $currentSessionId)->delete();

        $sessionFilePath = storage_path('framework/sessions');
        if (File::isDirectory($sessionFilePath)) {
            foreach (File::files($sessionFilePath) as $file) {
                if ($file->getFilename() !== $currentSessionId) {
                    File::delete($file->getPathname());
                }
            }
        }

        return redirect()->route('app-settings.index')->with('success', 'Semua sesi pengguna lain berhasil dihapus.');
    }

    public function clearAll(Request $request)
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');

        $currentSessionId = $request->session()->getId();
        DB::table('sessions')->where('id', '!=', $currentSessionId)->delete();

        $sessionFilePath = storage_path('framework/sessions');
        if (File::isDirectory($sessionFilePath)) {
            foreach (File::files($sessionFilePath) as $file) {
                if ($file->getFilename() !== $currentSessionId) {
                    File::delete($file->getPathname());
                }
            }
        }

        return redirect()->route('app-settings.index')
            ->with('success', 'Semua cache dan sesi berhasil dibersihkan.');
    }

    // --- helpers ---

    private function dirStats(string $path): array
    {
        if (!File::isDirectory($path)) {
            return ['count' => 0, 'bytes' => 0, 'size' => '0 B', 'percent' => 0, 'path' => $path];
        }

        $files = File::allFiles($path);
        $bytes = array_sum(array_map(fn($f) => $f->getSize(), $files));

        return [
            'count'   => count($files),
            'bytes'   => $bytes,
            'size'    => $this->humanSize($bytes),
            'percent' => min(100, round($bytes / self::REF_BYTES * 100, 1)),
            'path'    => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path),
        ];
    }

    private function sessionStats(): array
    {
        $count      = DB::table('sessions')->count();
        $activeUsers = DB::table('sessions')
            ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
            ->count();
        // Approximate size: sum of payload lengths in bytes
        $bytes = (int) DB::table('sessions')->sum(DB::raw('LENGTH(payload)'));

        return [
            'count'       => $count,
            'active'      => $activeUsers,
            'bytes'       => $bytes,
            'size'        => $this->humanSize($bytes),
            'percent'     => min(100, round($bytes / self::REF_BYTES * 100, 1)),
            'path'        => 'database: sessions table',
        ];
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    private function upsertEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $content = File::exists($envPath) ? File::get($envPath) : '';

        $escapedValue = $this->escapeEnvValue($value);
        $pattern = "/^{$key}=.*$/m";

        if (preg_match($pattern, $content) === 1) {
            $content = preg_replace($pattern, "{$key}={$escapedValue}", $content) ?? $content;
        } else {
            $content .= (str_ends_with($content, PHP_EOL) || $content === '' ? '' : PHP_EOL) . "{$key}={$escapedValue}" . PHP_EOL;
        }

        File::put($envPath, $content);
    }

    private function escapeEnvValue(string $value): string
    {
        $needsQuotes = $value === ''
            || str_contains($value, ' ')
            || str_contains($value, '#')
            || str_contains($value, '"')
            || str_contains($value, "'");

        if ($needsQuotes) {
            return '"' . addcslashes($value, '"') . '"';
        }

        return $value;
    }

    private function normalizePhone(string $rawPhone, string $defaultCountryCode): ?string
    {
        $digits = preg_replace('/\D+/', '', $rawPhone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return $defaultCountryCode . substr($digits, 1);
        }

        if (str_starts_with($digits, $defaultCountryCode) || str_starts_with($digits, '62')) {
            return $digits;
        }

        return $defaultCountryCode . $digits;
    }

    private function sendFonnteMessage(string $token, string $apiUrl, string $countryCode, string $target, string $message): bool
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders(['Authorization' => $token])
                ->asForm()
                ->post($apiUrl, [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => $countryCode,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::channel('security')->warning('Gagal kirim pesan WA Fonnte', [
                'target' => $target,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::channel('security')->error('Error kirim pesan WA Fonnte', [
                'target' => $target,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function fonnteConfig(): array
    {
        return [
            'enabled' => (bool) config('services.fonnte.enabled', false),
            'token' => (string) config('services.fonnte.token', ''),
            'api_url' => (string) config('services.fonnte.api_url', 'https://api.fonnte.com/send'),
            'country_code' => (string) config('services.fonnte.default_country_code', '62'),
        ];
    }
}
