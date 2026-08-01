<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\DashboardDssAnalyticsExport;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardDssService;
use App\Services\Dashboard\DashboardFilterService;
use App\Repositories\Contracts\Dashboard\DashboardAnalyticsRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class DashboardDssController extends Controller
{
    public function __construct(
        private readonly DashboardDssService $service,
        private readonly DashboardFilterService $filterService,
        private readonly DashboardAnalyticsRepositoryInterface $repository
    ) {}

    public function landing(Request $request)
    {
        $user = Auth::user();
        $mode = $this->resolveDisplayMode($request);

        if (!$user || !method_exists($user, 'hasRole')) {
            abort(403);
        }

        if ($this->hasRole($user, 'admin')) {
            return redirect()->route('admin.dashboard.dss', ['mode' => $mode]);
        }

        if ($this->hasRole($user, 'kesiswaan')) {
            return redirect()->route('kesiswaan.dashboard.dss', ['mode' => $mode]);
        }

        if ($this->hasRole($user, 'bk')) {
            return redirect()->route('bk.dashboard.dss', ['mode' => $mode]);
        }

        if ($this->hasRole($user, 'wali_kelas')) {
            return redirect()->route('wali-kelas.dashboard.dss', ['mode' => $mode]);
        }

        if ($this->hasRole($user, 'guru')) {
            return redirect()->route('guru.dashboard.dss', ['mode' => $mode]);
        }

        if ($this->hasRole($user, 'siswa')) {
            return redirect()->route('siswa.dashboard.dss', ['mode' => $mode]);
        }

        if ($this->hasRole($user, 'kurikulum')) {
            return redirect()->route('kurikulum.dashboard.dss', ['mode' => $mode]);
        }

        abort(403);
    }

    public function admin(Request $request)
    {
        return $this->render('admin', $request);
    }

    public function guru(Request $request)
    {
        return $this->render('guru', $request);
    }

    public function waliKelas(Request $request)
    {
        return $this->render('wali_kelas', $request);
    }

    public function bk(Request $request)
    {
        return $this->render('bk', $request);
    }

    public function kesiswaan(Request $request)
    {
        return $this->render('kesiswaan', $request);
    }

    public function siswa(Request $request)
    {
        return $this->render('siswa', $request);
    }

    public function kurikulum(Request $request)
    {
        return $this->render('kurikulum', $request);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $filters = $this->filterService->normalize($request);
        $mode = $this->resolveDisplayMode($request);

        return response()->json($this->service->build($user, $filters, $mode));
    }

    public function options(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $filters = $this->filterService->normalize($request);

        return response()->json($this->service->options($user, $filters));
    }

    public function activities(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $filters = $this->filterService->normalize($request);
        $scope = $this->repository->buildScope($user);
        $mode = $this->resolveDisplayMode($request);

        if ($mode !== 'detail') {
            $draw = (int) $request->input('draw', 1);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = max(min((int) $request->input('length', 10), 100), 10);
        $search = (string) data_get($request->input('search', []), 'value', '');

        $result = $this->repository->recentActivitiesDatatable($filters, $scope, $start, $length, $search);

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $result['data'],
        ]);
    }

    public function export(Request $request, string $format)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $filters = $this->filterService->normalize($request);
        $scope = $this->repository->buildScope($user);
        $rows = $this->repository->exportRows($filters, $scope);
        $timestamp = now()->format('Ymd_His');

        if ($format === 'xlsx') {
            return Excel::download(new DashboardDssAnalyticsExport($rows), 'analytics_pancawaluya_' . $timestamp . '.xlsx');
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('dashboard.dss.export-pdf', [
                'rows' => $rows,
                'generatedAt' => now(),
                'filters' => $filters,
            ])->setPaper('a4', 'portrait');

            return $pdf->download('analytics_pancawaluya_' . $timestamp . '.pdf');
        }

        $filename = 'analytics_pancawaluya_' . $timestamp . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = static function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['section', 'metric', 'value']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    (string) ($row['section'] ?? ''),
                    (string) ($row['metric'] ?? ''),
                    (string) ($row['value'] ?? ''),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function render(string $role, Request $request)
    {
        $mode = $this->resolveDisplayMode($request);

        return view('dashboard.dss.index', [
            'role' => $role,
            'title' => $this->title($role),
            'mode' => $mode,
            'roleRouteName' => $this->routeNameByRole($role),
            'absensiRouteName' => $this->absensiRouteNameByRole($role),
        ]);
    }

    private function routeNameByRole(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard.dss',
            'guru' => 'guru.dashboard.dss',
            'wali_kelas' => 'wali-kelas.dashboard.dss',
            'bk' => 'bk.dashboard.dss',
            'kesiswaan' => 'kesiswaan.dashboard.dss',
            'siswa' => 'siswa.dashboard.dss',
            default => 'kurikulum.dashboard.dss',
        };
    }

    private function absensiRouteNameByRole(string $role): ?string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'guru' => 'guru.dashboard',
            'siswa' => 'siswa.dashboard',
            'kurikulum' => 'kurikulum.dashboard',
            default => null,
        };
    }

    private function title(string $role): string
    {
        return match ($role) {
            'admin' => 'Dashboard Admin',
            'guru' => 'Dashboard Guru',
            'wali_kelas' => 'Dashboard Wali Kelas',
            'bk' => 'Dashboard BK',
            'kesiswaan' => 'Dashboard Kesiswaan',
            'siswa' => 'Dashboard Siswa',
            default => 'Dashboard Kurikulum',
        };
    }

    private function hasRole(object $user, string $role): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole($role);
    }

    private function resolveDisplayMode(Request $request): string
    {
        return $request->query('mode') === 'detail' ? 'detail' : 'ringkas';
    }
}
