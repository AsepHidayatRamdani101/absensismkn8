<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Repositories\Contracts\Dashboard\DashboardAnalyticsRepositoryInterface;

class DashboardDssService
{
    public function __construct(
        private readonly DashboardAnalyticsRepositoryInterface $repository,
        private readonly DashboardCacheService $cache
    ) {}

    public function build(User $user, array $filters, string $mode = 'detail'): array
    {
        $scope = $this->repository->buildScope($user);
        $roleKey = (string) ($scope['role'] ?? 'unknown');
        $scopeKey = implode(':', [
            $scope['teacher_id'] ?? '-',
            $scope['classroom_id'] ?? '-',
            $scope['student_id'] ?? '-',
            $scope['user_id'] ?? '-',
        ]);

        $normalizedMode = $mode === 'detail' ? 'detail' : 'ringkas';

        return $this->cache->remember($roleKey, $scopeKey . ':mode:' . $normalizedMode, $filters, function () use ($filters, $scope, $roleKey, $normalizedMode): array {
            $kpi = $this->repository->kpi($filters, $scope);
            $alerts = $this->repository->alerts($filters, $scope);
            $recommendations = $this->repository->recommendations($filters, $scope);
            $characterAnalytics = [];
            $rewardAnalytics = [];
            $violationAnalytics = [];
            $spAnalytics = [];
            $comparativeAnalytics = [];
            $correlationAnalytics = [];
            $predictiveAnalytics = [];
            $executiveSummary = [];
            $trends = [
                'labels' => [],
                'reward_trend' => [],
                'violation_trend' => [],
                'character_growth' => [],
                'sp_distribution' => ['SP1' => 0, 'SP2' => 0, 'SP3' => 0],
            ];
            $radar = [
                'labels' => [],
                'current' => [],
                'previous' => [],
                'school_average' => [],
                'class_average' => [],
                'department_average' => [],
                'comparisons' => [],
            ];
            $rankings = [
                'best_students' => [],
                'highest_violations' => [],
                'highest_rewards' => [],
                'most_active_teachers' => [],
            ];
            $recentActivities = [];

            if ($normalizedMode === 'detail') {
                $trends = $this->repository->trends($filters, $scope);
                $radar = $this->repository->radar($filters, $scope);
                $rankings = $this->repository->rankings($filters, $scope);
                $characterAnalytics = $this->repository->characterAnalytics($filters, $scope);
                $rewardAnalytics = $this->repository->rewardAnalytics($filters, $scope);
                $violationAnalytics = $this->repository->violationAnalytics($filters, $scope);
                $spAnalytics = $this->repository->spAnalytics($filters, $scope);
                $comparativeAnalytics = $this->repository->comparativeAnalytics($filters, $scope);
                $correlationAnalytics = $this->repository->correlationAnalytics($filters, $scope);
                $predictiveAnalytics = $this->repository->predictiveAnalytics($filters, $scope);
                $executiveSummary = $this->repository->executiveSummary($filters, $scope);
                $recentActivities = $this->repository->recentActivities($filters, $scope, 15);
            }

            return [
                'meta' => [
                    'role' => $roleKey,
                    'mode' => $normalizedMode,
                    'scope' => $scope,
                    'cache_ttl_seconds' => 120,
                    'generated_at' => now()->toIso8601String(),
                ],
                'kpi' => $kpi,
                'trends' => $trends,
                'radar' => $radar,
                'rankings' => $rankings,
                'alerts' => $alerts,
                'recommendations' => $recommendations,
                'recent_activities' => $recentActivities,
                'character_analytics' => $characterAnalytics,
                'reward_analytics' => $rewardAnalytics,
                'violation_analytics' => $violationAnalytics,
                'sp_analytics' => $spAnalytics,
                'comparative_analytics' => $comparativeAnalytics,
                'correlation_analytics' => $correlationAnalytics,
                'predictive_analytics' => $predictiveAnalytics,
                'executive_summary' => $executiveSummary,
                'analytics' => [
                    'trend_analysis' => $this->trendAnalysis($trends),
                    'growth_analysis' => $this->growthAnalysis($trends),
                    'behavior_analysis' => $this->behaviorAnalysis($kpi),
                    'character_analysis' => $this->characterAnalysis($kpi, $radar),
                ],
            ];
        });
    }

    public function options(User $user, array $filters): array
    {
        return $this->repository->filterOptions($filters, $this->repository->buildScope($user));
    }

    private function trendAnalysis(array $trends): array
    {
        $reward = collect($trends['reward_trend'] ?? []);
        $violation = collect($trends['violation_trend'] ?? []);

        $rewardDelta = $reward->count() > 1 ? ((int) $reward->last() - (int) $reward->first()) : 0;
        $violationDelta = $violation->count() > 1 ? ((int) $violation->last() - (int) $violation->first()) : 0;

        return [
            'reward_delta' => $rewardDelta,
            'violation_delta' => $violationDelta,
            'reward_direction' => $rewardDelta >= 0 ? 'up' : 'down',
            'violation_direction' => $violationDelta >= 0 ? 'up' : 'down',
        ];
    }

    private function growthAnalysis(array $trends): array
    {
        $series = collect($trends['character_growth'] ?? []);

        $first = (float) ($series->first() ?? 0);
        $last = (float) ($series->last() ?? 0);
        $delta = round($last - $first, 2);

        return [
            'character_growth_delta' => $delta,
            'growth_direction' => $delta >= 0 ? 'positive' : 'negative',
        ];
    }

    private function behaviorAnalysis(array $kpi): array
    {
        $rewards = (int) ($kpi['total_rewards'] ?? 0);
        $violations = (int) ($kpi['total_violations'] ?? 0);

        return [
            'reward_violation_ratio' => $violations === 0 ? $rewards : round($rewards / max(1, $violations), 2),
            'risk_level' => $violations > $rewards ? 'high' : 'normal',
        ];
    }

    private function characterAnalysis(array $kpi, array $radar): array
    {
        $avg = (float) ($kpi['character_average'] ?? 0);
        $currentRadar = collect($radar['current'] ?? []);
        $lowestDimension = $currentRadar->isEmpty() ? null : $currentRadar->search($currentRadar->min());

        return [
            'average' => $avg,
            'status' => $avg >= 80 ? 'excellent' : ($avg >= 65 ? 'good' : 'needs_attention'),
            'weakest_dimension_index' => $lowestDimension,
        ];
    }
}
