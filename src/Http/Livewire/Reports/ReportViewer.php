<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Reports;

use Livewire\Component;
use QuickerFaster\UILibrary\Models\SavedReport;
use Illuminate\Support\Facades\Auth;

class ReportViewer extends Component
{
    public ?string $configKey = null;      // optional, can be derived from report config
    public ?string $reportKey = null;      // the config key of the report (e.g., 'hr_employee_directory')
    public ?int $savedReportId = null;     // for user-saved reports

    public array $reportConfig = [];
    public string $reportType;
    public string $reportName;
    public string $moduleName;
    public string $context;                 // for navigation layout

    public function mount(?string $configKey = null, ?string $reportKey = null, ?int $reportId = null)
    {
        $this->configKey = $configKey;
        $this->reportKey = $reportKey;
        $this->savedReportId = $reportId;

        $repository = app(\QuickerFaster\UILibrary\Services\Config\ModelConfigRepository::class);


        if ($this->savedReportId) {
            // Load user-saved report from database
            $saved = SavedReport::where('id', $this->savedReportId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $this->reportConfig = $saved->configuration;
            $this->reportType = $saved->type;
            $this->reportName = $saved->name;
            // For saved reports, we need to know the original configKey to load model/fields
            $this->configKey = $this->reportConfig['configKey'] ?? $saved->config_key;
            $this->moduleName = $this->reportConfig['module'] ?? explode('_', $this->configKey)[0] ?? 'system';
            $this->context = $this->reportConfig['context'] ?? 'reports';
        } else {
            // Load system report from config
            // Load system report from repository
            if (!$this->reportKey) {
                abort(400, 'Missing report key');
            }
            try {
                $reportConfig = $repository->get($this->reportKey);
            } catch (\InvalidArgumentException $e) {
                abort(404, 'Report configuration not found for key: ' . $this->reportKey);
            }
            $this->reportConfig = $reportConfig;
            $this->reportType = $reportConfig['type'] ?? 'tabular';
            $this->reportName = $reportConfig['label'] ?? $reportConfig['title'] ?? $this->reportKey;
            $this->configKey = $reportConfig['configKey'] ?? $this->configKey;
            $this->moduleName = $reportConfig['module'] ?? explode('_', $this->reportKey)[0] ?? 'system';
            $this->context = $reportConfig['context'] ?? 'reports';
        }

        // Fallbacks
        if (!$this->configKey) {
            abort(400, 'Report configuration missing configKey');
        }
    }

    public function render()
    {
        
        if ($this->reportType === 'tabular') {
            // Build custom columns for DataTable
            $customColumns = [];
            $fields = $this->reportConfig['fields'] ?? [];

            // If fields are defined as simple array of field names
            if (!empty($fields) && !isset($fields[0]['field'])) {
                // Try to get field definitions from the main module config (via ConfigResolver)
                try {
                    $resolver = app(\QuickerFaster\UILibrary\Services\Config\ConfigResolver::class, ['configKey' => $this->configKey]);
                    $allFields = $resolver->getFieldDefinitions();
                    foreach ($fields as $fieldName) {
                        if (isset($allFields[$fieldName])) {
                            $customColumns[$fieldName] = $allFields[$fieldName];
                        } else {
                            $customColumns[$fieldName] = [
                                'label' => ucfirst(str_replace('_', ' ', $fieldName)),
                                'field_type' => 'string',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // If resolver fails, create simple definitions
                    foreach ($fields as $fieldName) {
                        $customColumns[$fieldName] = [
                            'label' => ucfirst(str_replace('_', ' ', $fieldName)),
                            'field_type' => 'string',
                        ];
                    }
                }
            } elseif (!empty($fields) && isset($fields[0]['field'])) {
                // Detailed field definitions already provided
                foreach ($fields as $fieldDef) {
                    $fieldName = $fieldDef['field'];
                    $customColumns[$fieldName] = $fieldDef;
                }
            }

            $queryFilters = $this->reportConfig['filters'] ?? [];
            $defaultSort = $this->reportConfig['default_sort'] ?? null;
            $perPage = $this->reportConfig['per_page'] ?? 15;

            return view('qf::livewire.reports.report-viewer-tabular', [
                'configKey' => $this->configKey,
                'customColumns' => $customColumns,
                'queryFilters' => $queryFilters,
                'defaultSort' => $defaultSort,
                'perPage' => $perPage,
                'reportName' => $this->reportName,
                'moduleName' => $this->moduleName,
                'context' => $this->context,
            ]);
        } else {
            // Dashboard report
            $widgets = $this->reportConfig['widgets'] ?? [];
            $layout = $this->reportConfig['layout'] ?? ['columns' => 12, 'gutter' => 3];
            $title = ''; // $this->reportConfig['title'] ?? '$this->reportName'; Main report header is ok
            $description = $this->reportConfig['description'] ?? '';

            return view('qf::livewire.reports.report-viewer-dashboard', [
                'configKey' => $this->configKey,
                'customWidgets' => [
                    'title' => $title,
                    'description' => $description,
                    'layout' => $layout,
                    'widgets' => $widgets,
                ],
                'reportName' => $this->reportName,
                'moduleName' => $this->moduleName,
                'context' => $this->context,
            ]);
        }
    }
}