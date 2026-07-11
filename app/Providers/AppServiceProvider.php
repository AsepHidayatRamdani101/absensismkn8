<?php

namespace App\Providers;

use App\Models\Student;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('guru', function ($user) {
            return $user->hasRole('guru');
        });

        Gate::define('siswa', function ($user) {
            return $user->hasRole('siswa');
        });

        Gate::define('siswa-absen-guru', function ($user) {
            if (!method_exists($user, 'hasRole') || !$user->hasRole('siswa')) {
                return false;
            }

            $username = trim((string) $user->email);

            if ($username === '') {
                return false;
            }

            $student = Student::query()
                ->where('nisn', $username)
                ->orWhere('nis', $username)
                ->first();

            return $student?->canSubmitTeacherAttendance() ?? false;
        });
    }
}
