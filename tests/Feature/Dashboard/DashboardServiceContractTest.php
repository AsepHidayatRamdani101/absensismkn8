<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Repositories\Contracts\Dashboard\DashboardAnalyticsRepositoryInterface;
use App\Services\Dashboard\DashboardCacheService;
use App\Services\Dashboard\DashboardDssService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardServiceContractTest extends TestCase
{
    #[Test]
    public function dashboard_service_returns_required_sections(): void
    {
        $service = new DashboardDssService(new FakeDashboardRepository(), new DashboardCacheService());

        $user = new User();
        $user->id = 99;
        $user->email = 'admin@smkn8.sch.id';
        $user->name = 'Admin';

        $payload = $service->build($user, [
            'academic_year_id' => '',
            'semester' => '',
            'major_id' => '',
            'classroom_id' => '',
            'student_id' => '',
            'teacher_id' => '',
            'source' => '',
            'date_from' => '',
            'date_to' => '',
            'gender' => '',
            'grade_level' => '',
            'top_limit' => '10',
            'compare_mode' => '',
        ]);

        $this->assertArrayHasKey('meta', $payload);
        $this->assertArrayHasKey('kpi', $payload);
        $this->assertArrayHasKey('trends', $payload);
        $this->assertArrayHasKey('radar', $payload);
        $this->assertArrayHasKey('rankings', $payload);
        $this->assertArrayHasKey('alerts', $payload);
        $this->assertArrayHasKey('recommendations', $payload);
        $this->assertArrayHasKey('recent_activities', $payload);
        $this->assertArrayHasKey('analytics', $payload);
        $this->assertArrayHasKey('character_analytics', $payload);
        $this->assertArrayHasKey('reward_analytics', $payload);
        $this->assertArrayHasKey('violation_analytics', $payload);
        $this->assertArrayHasKey('sp_analytics', $payload);
        $this->assertArrayHasKey('comparative_analytics', $payload);
        $this->assertArrayHasKey('correlation_analytics', $payload);
        $this->assertArrayHasKey('predictive_analytics', $payload);
        $this->assertArrayHasKey('executive_summary', $payload);
    }

    #[Test]
    public function cache_service_reuses_cached_result_for_identical_filters(): void
    {
        $cacheService = new DashboardCacheService();
        $calls = 0;

        $resolver = function () use (&$calls): array {
            $calls++;
            return ['ok' => true];
        };

        $first = $cacheService->remember('admin', 'all', ['a' => 1], $resolver);
        $second = $cacheService->remember('admin', 'all', ['a' => 1], $resolver);

        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }
}

class FakeDashboardRepository implements DashboardAnalyticsRepositoryInterface
{
    public function buildScope(User $user): array
    {
        return ['role' => 'admin', 'teacher_id' => null, 'classroom_id' => null, 'student_id' => null, 'user_id' => 1];
    }

    public function kpi(array $filters, array $scope): array
    {
        return [
            'total_students' => 10,
            'total_teachers' => 2,
            'total_rewards' => 3,
            'total_violations' => 1,
            'total_sp1' => 1,
            'total_sp2' => 0,
            'total_sp3' => 0,
            'character_average' => 80,
            'pending_validation' => 0,
        ];
    }

    public function trends(array $filters, array $scope): array
    {
        return [
            'labels' => ['Jan'],
            'reward_trend' => [1],
            'violation_trend' => [0],
            'character_growth' => [80],
            'sp_distribution' => ['SP1' => 0, 'SP2' => 0, 'SP3' => 0],
        ];
    }

    public function radar(array $filters, array $scope): array
    {
        return [
            'labels' => ['Cageur', 'Bageur', 'Bener', 'Pinter', 'Singer'],
            'current' => [80, 82, 81, 79, 83],
            'previous' => [78, 80, 79, 77, 81],
            'school_average' => [76, 78, 77, 75, 79],
            'class_average' => [79, 81, 80, 78, 82],
        ];
    }

    public function rankings(array $filters, array $scope): array
    {
        return ['best_students' => [], 'highest_violations' => [], 'highest_rewards' => [], 'most_active_teachers' => []];
    }

    public function alerts(array $filters, array $scope): array
    {
        return ['near_sp' => [], 'low_character' => [], 'without_reward' => []];
    }

    public function recommendations(array $filters, array $scope): array
    {
        return [];
    }

    public function characterAnalytics(array $filters, array $scope): array
    {
        return [];
    }

    public function rewardAnalytics(array $filters, array $scope): array
    {
        return [];
    }

    public function violationAnalytics(array $filters, array $scope): array
    {
        return [];
    }

    public function spAnalytics(array $filters, array $scope): array
    {
        return [];
    }

    public function comparativeAnalytics(array $filters, array $scope): array
    {
        return [];
    }

    public function correlationAnalytics(array $filters, array $scope): array
    {
        return [];
    }

    public function predictiveAnalytics(array $filters, array $scope): array
    {
        return [];
    }

    public function executiveSummary(array $filters, array $scope): array
    {
        return ['narrative' => []];
    }

    public function recentActivities(array $filters, array $scope, int $limit = 20): array
    {
        return [];
    }

    public function recentActivitiesDatatable(array $filters, array $scope, int $start, int $length, string $search = ''): array
    {
        return ['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
    }

    public function filterOptions(array $filters, array $scope): array
    {
        return [
            'academic_years' => [],
            'semesters' => ['Ganjil', 'Genap'],
            'majors' => [],
            'classrooms' => [],
            'students' => [],
            'teachers' => [],
            'sources' => [],
            'genders' => [],
            'grade_levels' => [],
            'top_limits' => [10, 20, 50],
            'compare_modes' => [],
        ];
    }

    public function exportRows(array $filters, array $scope): array
    {
        return [];
    }
}
