<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

        return view('admin.app_settings.index', compact(
            'cache',
            'views',
            'session',
            'configCache',
            'routeCache',
            'refBytes'
        ));
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
}
