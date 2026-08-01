<?php

namespace App\Repositories\Contracts\Dashboard;

use App\Models\User;

interface DashboardAnalyticsRepositoryInterface
{
    public function buildScope(User $user): array;

    public function kpi(array $filters, array $scope): array;

    public function trends(array $filters, array $scope): array;

    public function radar(array $filters, array $scope): array;

    public function rankings(array $filters, array $scope): array;

    public function alerts(array $filters, array $scope): array;

    public function recommendations(array $filters, array $scope): array;

    public function characterAnalytics(array $filters, array $scope): array;

    public function rewardAnalytics(array $filters, array $scope): array;

    public function violationAnalytics(array $filters, array $scope): array;

    public function spAnalytics(array $filters, array $scope): array;

    public function comparativeAnalytics(array $filters, array $scope): array;

    public function correlationAnalytics(array $filters, array $scope): array;

    public function predictiveAnalytics(array $filters, array $scope): array;

    public function executiveSummary(array $filters, array $scope): array;

    public function recentActivities(array $filters, array $scope, int $limit = 20): array;

    public function recentActivitiesDatatable(array $filters, array $scope, int $start, int $length, string $search = ''): array;

    public function filterOptions(array $filters, array $scope): array;

    public function exportRows(array $filters, array $scope): array;
}
