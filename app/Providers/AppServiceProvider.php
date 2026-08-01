<?php

namespace App\Providers;

use App\Models\RewardCategory;
use App\Models\RewardItem;
use App\Models\RewardTransaction;
use App\Models\StudentCharacterScore;
use App\Models\StudentWarningLetter;
use App\Models\ViolationCategory;
use App\Models\ViolationItem;
use App\Models\ViolationTransaction;
use App\Observers\Pancawaluya\RewardTransactionObserver;
use App\Observers\Pancawaluya\StudentCharacterScoreObserver;
use App\Observers\Pancawaluya\StudentWarningLetterObserver;
use App\Observers\Pancawaluya\ViolationTransactionObserver;
use App\Policies\Pancawaluya\RewardCategoryPolicy;
use App\Policies\Pancawaluya\RewardPolicy;
use App\Policies\Pancawaluya\RewardTransactionPolicy;
use App\Policies\Pancawaluya\TransactionHistoryPolicy;
use App\Policies\Pancawaluya\ViolationCategoryPolicy;
use App\Policies\Pancawaluya\ViolationPolicy;
use App\Policies\Pancawaluya\ViolationTransactionPolicy;
use App\Repositories\Contracts\Pancawaluya\AuditLogCharacterRepositoryInterface;
use App\Repositories\Contracts\Dashboard\DashboardAnalyticsRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\CharacterMappingRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\RewardCategoryRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\RewardItemRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\RewardTransactionRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\TransactionHistoryRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\ViolationCategoryRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\ViolationItemRepositoryInterface;
use App\Repositories\Contracts\Pancawaluya\ViolationTransactionRepositoryInterface;
use App\Repositories\Pancawaluya\AuditLogCharacterRepository;
use App\Repositories\Dashboard\DashboardAnalyticsRepository;
use App\Repositories\Pancawaluya\CharacterMappingRepository;
use App\Repositories\Pancawaluya\RewardCategoryRepository;
use App\Repositories\Pancawaluya\RewardItemRepository;
use App\Repositories\Pancawaluya\RewardTransactionRepository;
use App\Repositories\Pancawaluya\TransactionHistoryRepository;
use App\Repositories\Pancawaluya\ViolationCategoryRepository;
use App\Repositories\Pancawaluya\ViolationItemRepository;
use App\Repositories\Pancawaluya\ViolationTransactionRepository;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\SchoolSetting;
use App\Models\StudentLeaveRequest;
use App\Models\Teacher;
use App\Models\Student;
use App\Support\ReferenceCache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RewardCategoryRepositoryInterface::class, RewardCategoryRepository::class);
        $this->app->bind(RewardItemRepositoryInterface::class, RewardItemRepository::class);
        $this->app->bind(ViolationCategoryRepositoryInterface::class, ViolationCategoryRepository::class);
        $this->app->bind(ViolationItemRepositoryInterface::class, ViolationItemRepository::class);
        $this->app->bind(CharacterMappingRepositoryInterface::class, CharacterMappingRepository::class);
        $this->app->bind(AuditLogCharacterRepositoryInterface::class, AuditLogCharacterRepository::class);
        $this->app->bind(DashboardAnalyticsRepositoryInterface::class, DashboardAnalyticsRepository::class);
        $this->app->bind(RewardTransactionRepositoryInterface::class, RewardTransactionRepository::class);
        $this->app->bind(ViolationTransactionRepositoryInterface::class, ViolationTransactionRepository::class);
        $this->app->bind(TransactionHistoryRepositoryInterface::class, TransactionHistoryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();

        $this->registerReferenceCacheInvalidationHooks();

        View::composer('*', function () {
            if (app()->runningInConsole() || !Auth::check()) {
                return;
            }

            $user = Auth::user();

            if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('guru')) {
                return;
            }

            if (!Schema::hasTable('student_leave_requests') || !Schema::hasColumns('teachers', ['is_wali_kelas', 'wali_classroom_id'])) {
                return;
            }

            $waliTeacher = Teacher::query()
                ->where(function ($query) use ($user) {
                    $query->where('nip', $user->email)
                        ->orWhere('nama_lengkap', $user->name);
                })
                ->where('is_wali_kelas', true)
                ->whereNotNull('wali_classroom_id')
                ->first();

            if (!$waliTeacher) {
                return;
            }

            $pendingCount = (int) StudentLeaveRequest::query()
                ->where('status_pengajuan', 'Menunggu')
                ->whereHas('student', function ($query) use ($waliTeacher) {
                    $query->where('classroom_id', $waliTeacher->wali_classroom_id);
                })
                ->count();

            $menu = config('adminlte.menu', []);
            $menu = $this->applyPendingStudentLeaveRequestBadge($menu, $pendingCount);

            Config::set('adminlte.menu', $menu);
        });

        Gate::define('admin', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('guru', function ($user) {
            return $user->hasRole('guru');
        });

        Gate::define('kurikulum', function ($user) {
            return $user->hasRole('kurikulum');
        });

        Gate::define('siswa', function ($user) {
            return $user->hasRole('siswa');
        });

        Gate::define('wali_kelas', function ($user) {
            return $user->hasRole('wali_kelas');
        });

        Gate::define('bk', function ($user) {
            return $user->hasRole('bk');
        });

        Gate::define('kesiswaan', function ($user) {
            return $user->hasRole('kesiswaan');
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

        Gate::define('guru-wali-kelas', function ($user) {
            if (!method_exists($user, 'hasRole') || !$user->hasRole('guru')) {
                return false;
            }

            if (!Schema::hasColumns('teachers', ['is_wali_kelas', 'wali_classroom_id'])) {
                return false;
            }

            return Teacher::query()
                ->where(function ($query) use ($user) {
                    $query->where('nip', $user->email)
                        ->orWhere('nama_lengkap', $user->name);
                })
                ->where('is_wali_kelas', true)
                ->whereNotNull('wali_classroom_id')
                ->exists();
        });

        Gate::define('pancawaluya.menu.reward-transaction', function ($user) {
            if (!method_exists($user, 'hasRole')) {
                return false;
            }

            if ($user->hasRole('admin')) {
                return true;
            }

            if (!method_exists($user, 'hasAnyRole')) {
                return false;
            }

            return $user->hasAnyRole(['guru', 'wali_kelas', 'kesiswaan'])
                && method_exists($user, 'can')
                && $user->can('pancawaluya.transaction.reward.view');
        });

        Gate::define('pancawaluya.menu.violation-transaction', function ($user) {
            if (!method_exists($user, 'hasRole')) {
                return false;
            }

            if ($user->hasRole('admin')) {
                return true;
            }

            if (!method_exists($user, 'hasAnyRole')) {
                return false;
            }

            return $user->hasAnyRole(['wali_kelas', 'bk', 'kesiswaan'])
                && method_exists($user, 'can')
                && $user->can('pancawaluya.transaction.violation.view');
        });

        Gate::define('pancawaluya.menu.transaction-history', function ($user) {
            if (!method_exists($user, 'hasRole')) {
                return false;
            }

            if ($user->hasRole('admin')) {
                return true;
            }

            if (!method_exists($user, 'hasAnyRole')) {
                return false;
            }

            return $user->hasAnyRole(['wali_kelas', 'kesiswaan', 'bk', 'siswa'])
                && method_exists($user, 'can')
                && $user->can('pancawaluya.transaction.history.view');
        });

        Gate::policy(RewardCategory::class, RewardCategoryPolicy::class);
        Gate::policy(RewardItem::class, RewardPolicy::class);
        Gate::policy(ViolationCategory::class, ViolationCategoryPolicy::class);
        Gate::policy(ViolationItem::class, ViolationPolicy::class);
        Gate::policy(RewardTransaction::class, RewardTransactionPolicy::class);
        Gate::policy(ViolationTransaction::class, ViolationTransactionPolicy::class);

        Gate::define('pancawaluya.transaction-history.view', fn($user) => (new TransactionHistoryPolicy())->viewAny($user));
        Gate::define('pancawaluya.transaction-history.export', fn($user) => (new TransactionHistoryPolicy())->export($user));

        Gate::define('ApproveViolation', fn($user) => $user->can('pancawaluya.violation.approve'));
        Gate::define('ApproveReward', fn($user) => $user->can('pancawaluya.reward.approve'));
        Gate::define('GenerateSP', fn($user) => $user->can('pancawaluya.sp.generate'));
        Gate::define('ManageMapping', fn($user) => $user->can('pancawaluya.mapping.manage'));

        RewardTransaction::observe(RewardTransactionObserver::class);
        ViolationTransaction::observe(ViolationTransactionObserver::class);
        StudentWarningLetter::observe(StudentWarningLetterObserver::class);
        StudentCharacterScore::observe(StudentCharacterScoreObserver::class);
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('web-login', function (Request $request) {
            $email = (string) $request->input('email', '');
            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('analytics-read', function (Request $request) {
            $userId = (string) optional($request->user())->id;
            return Limit::perMinute(90)->by($userId . '|' . $request->ip());
        });

        RateLimiter::for('analytics-export', function (Request $request) {
            $userId = (string) optional($request->user())->id;
            return Limit::perMinute(10)->by($userId . '|' . $request->ip());
        });

        RateLimiter::for('api-auth', function (Request $request) {
            $email = (string) $request->input('email', '');
            return Limit::perMinute(10)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('api-default', function (Request $request) {
            $userId = (string) optional($request->user())->id;
            return Limit::perMinute(120)->by($userId . '|' . $request->ip());
        });

        RateLimiter::for('fonnte-test', function (Request $request) {
            $userId = (string) optional($request->user())->id;
            return Limit::perMinute(3)->by($userId . '|' . $request->ip());
        });
    }

    private function applyPendingStudentLeaveRequestBadge(array $menu, int $pendingCount): array
    {
        foreach ($menu as &$item) {
            if (($item['route'] ?? null) === 'guru.wali-kelas.leave-requests.index') {
                if ($pendingCount > 0) {
                    $item['label'] = $pendingCount;
                    $item['label_color'] = 'danger';
                } else {
                    unset($item['label'], $item['label_color']);
                }

                continue;
            }

            if (isset($item['submenu']) && is_array($item['submenu'])) {
                $item['submenu'] = $this->applyPendingStudentLeaveRequestBadge($item['submenu'], $pendingCount);
            }
        }

        return $menu;
    }

    private function registerReferenceCacheInvalidationHooks(): void
    {
        Major::saved(fn() => ReferenceCache::forgetAcademicReferences());
        Major::deleted(fn() => ReferenceCache::forgetAcademicReferences());

        Classroom::saved(fn() => ReferenceCache::forgetAcademicReferences());
        Classroom::deleted(fn() => ReferenceCache::forgetAcademicReferences());

        Student::saved(fn() => ReferenceCache::forgetStudentReferences());
        Student::deleted(fn() => ReferenceCache::forgetStudentReferences());

        Teacher::saved(fn() => ReferenceCache::forgetTeacherReferences());
        Teacher::deleted(fn() => ReferenceCache::forgetTeacherReferences());

        SchoolSetting::saved(fn() => ReferenceCache::forgetSchoolSettings());
        SchoolSetting::deleted(fn() => ReferenceCache::forgetSchoolSettings());
    }
}
