<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use QuickerFaster\UILibrary\Models\SavedReport;
use Illuminate\Support\Facades\Auth;

class ReportIndex extends Component
{
    use WithPagination;

    public string $module = '';          // Optional module filter (e.g., 'hr')
    public string $reportTypeFilter = 'all'; // all, system, user
    public string $search = '';

    protected $queryString = [
        'module' => ['except' => ''],
        'reportTypeFilter' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function mount(string $module = '')
    {
        $this->module = $module;
    }


protected function getSystemReports(): array
{
    $cacheKey = 'system_reports_list';

    return \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function () {
        $reports = [];
        $repository = app(\QuickerFaster\UILibrary\Services\Config\ModelConfigRepository::class);
        $modulesPath = app_path('Modules');

        if (!is_dir($modulesPath)) {
            return [];
        }

        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) as $moduleDir) {
            $moduleName = basename($moduleDir);
            $reportsDir = $moduleDir . '/Data/reports';

            if (!is_dir($reportsDir)) {
                continue;
            }

            foreach (glob($reportsDir . '/*.php') as $reportFile) {
                $reportBaseName = basename($reportFile, '.php');
                $configKey = strtolower($moduleName) . '.reports.' . $reportBaseName;

                try {
                    $reportConfig = $repository->get($configKey);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                // Apply module filter at runtime (cannot be cached because it varies per request)
                // We'll store the raw list and filter later.
                $reports[] = [
                    'id' => 'system_' . $configKey,
                    'name' => $reportConfig['label'] ?? $reportConfig['title'] ?? $reportBaseName,
                    'description' => $reportConfig['description'] ?? '',
                    'type' => 'system',
                    'module' => $reportConfig['module'] ?? strtolower($moduleName),
                    'context' => $reportConfig['context'] ?? 'reports',
                    'config_key' => $reportConfig['configKey'] ?? '',
                    'report_key' => $configKey,
                    'definition' => $reportConfig,
                    'last_run' => null,
                ];
            }
        }

        return $reports;
    });
}

    /**
     * Get user‑saved reports from database.
     */
    protected function getUserReports(): array
    {
        $query = SavedReport::where('user_id', Auth::id());

        if ($this->module) {
            $query->where('config_key', 'like', $this->module . '_%'); // or use a module column
        }

        return $query->get()->map(function ($report) {
            return [
                'id' => 'user_' . $report->id,
                'name' => $report->name,
                'description' => 'Custom report',
                'type' => 'user',
                'module' => $report->configuration['module'] ?? explode('_', $report->config_key)[0] ?? '',
                'context' => $report->configuration['context'] ?? '',
                'config_key' => $report->config_key,
                'report_key' => $report->configuration['reportConfigKey'] ?? '',
                'report_id' => $report->id,
                'definition' => $report->configuration,
                'last_run' => $report->updated_at?->diffForHumans(),
            ];
        })->toArray();
    }

public function getReportsProperty()
{
    $reports = [];

    if ($this->reportTypeFilter === 'all' || $this->reportTypeFilter === 'system') {
        $systemReports = $this->getSystemReports();
        // Filter by module (cannot be cached)
        if ($this->module) {
            $systemReports = array_filter($systemReports, fn($r) => ($r['module'] ?? '') === $this->module);
        }
        $reports = array_merge($reports, $systemReports);
    }
    if ($this->reportTypeFilter === 'all' || $this->reportTypeFilter === 'user') {
        $reports = array_merge($reports, $this->getUserReports());
    }

    // Apply search filter (dynamic)
    if ($this->search) {
        $reports = array_filter($reports, function ($report) {
            return stripos($report['name'], $this->search) !== false ||
                   stripos($report['description'], $this->search) !== false;
        });
    }

    usort($reports, fn($a, $b) => strcmp($a['name'], $b['name']));
    return $reports;
}


public function getAvailableSources(): array
{
    // Reuse the same system reports (cached list) but we may filter only those usable as templates
    $systemReports = $this->getSystemReports();
    $sources = [];
    foreach ($systemReports as $report) {
        $sources[] = [
            'key' => $report['report_key'],   // e.g., 'hr.reports.employee_directory'
            'label' => $report['name'],
            'module' => $report['module'],
            'context' => $report['context'],
        ];
    }
    usort($sources, fn($a, $b) => [$a['module'], $a['context']] <=> [$b['module'], $b['context']]);
    return $sources;
}




    public function runReport($reportId)
    {

        $report = collect($this->reports)->firstWhere('id', $reportId);
        if (!$report) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => 'Report not found.']);
            return;
        }

        if ($report['type'] === 'system') {
            // Use only reportKey (the full config key)
            $this->redirectRoute('report.viewer', ['reportKey' => $report['report_key']]);
        } else {
            $this->redirectRoute('report.viewer.user', ['reportId' => $report['report_id']]);
        }
    }

    public function deleteSavedReport($reportId)
    {
        $id = str_replace('user_', '', $reportId);
        SavedReport::where('id', $id)->where('user_id', Auth::id())->delete();
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Report deleted.']);
        $this->resetPage();
    }




    public function getDefaultConfigKey()
    {
        if ($this->module) {
            // Assuming config key is module name + '_employee'
            return $this->module . '_employee';
        }
        return null; // or a default like 'hr_employee'
    }









public function render()
{
    return view('qf::livewire.reports.report-index', [
        'reports' => $this->reports,
        'availableSources' => $this->getAvailableSources(),
    ]);
}




}




