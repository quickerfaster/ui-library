<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Reports;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Models\SavedReport;
use Illuminate\Support\Facades\Auth;
use QuickerFaster\UILibrary\Services\Config\ModelConfigRepository;

class ReportBuilder extends Component
{
    public string $mainConfigKey;      // e.g., 'hr.employee'
    public string $reportConfigKey;    // e.g., 'hr.employee_directory' (system report template)
    public ?int $reportId = null;      // for editing existing user reports

    public array $allFields = [];
    public array $selectedFields = [];
    public array $activeFilters = [];
    public string $reportName = '';
    public bool $isGlobal = false;

    protected $listeners = [
        'filtersUpdated' => 'updateFilters',
    ];



    public function mount(string $reportConfigKey, ?int $reportId = null)
{
    $this->reportConfigKey = $reportConfigKey;
    $this->reportId = $reportId;

    if ($this->reportId) {
        // Load the saved user report
        $saved = SavedReport::where('id', $this->reportId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->mainConfigKey = $saved->configuration['configKey'] ?? $saved->config_key;
        $this->reportName = $saved->name;
        $this->isGlobal = $saved->is_global;
        $this->selectedFields = $saved->configuration['fields'] ?? [];
        $this->activeFilters = $saved->configuration['filters'] ?? [];
    } else {
        // Creating a new report – use the system report template
        $repository = app(ModelConfigRepository::class);
        $systemConfig = $repository->get($reportConfigKey);

        $this->mainConfigKey = $systemConfig['configKey'] ?? '';
        if (!$this->mainConfigKey) {
            throw new \InvalidArgumentException("System report config missing 'configKey' for: {$reportConfigKey}");
        }

        $this->reportName = '';
        $this->isGlobal = false;
        $this->selectedFields = $systemConfig['fields'] ?? [];
        $this->activeFilters = [];
    }

    // Load all fields from the main model config
    $resolver = new ConfigResolver($this->mainConfigKey);
    $this->allFields = $resolver->getFieldDefinitions();
}

    public function updateFilters($filters)
    {
        $this->activeFilters = $filters;
    }

    public function getPreviewColumnsProperty(): array
    {
        $columns = [];
        foreach ($this->selectedFields as $field) {
            if (isset($this->allFields[$field])) {
                $columns[$field] = $this->allFields[$field];
            } else {
                $columns[$field] = [
                    'label' => ucfirst(str_replace('_', ' ', $field)),
                    'field_type' => 'string',
                ];
            }
        }
        return $columns;
    }

    public function saveReport()
    {
        $this->validate([
            'reportName' => 'required|string|max:255',
        ]);

        // Load the system report config to get context and module info
        $repository = app(ModelConfigRepository::class);
        $systemConfig = $repository->get($this->reportConfigKey); 

        $configuration = [
            'fields'   => $this->selectedFields,
            'filters'  => $this->activeFilters,
            'configKey'=> $this->mainConfigKey,
            'reportConfigKey' => $this->reportConfigKey,   // <-- add this
            'module'   => $systemConfig['module'] ?? explode('_', $this->mainConfigKey)[0] ?? 'system',
            'context'  => $systemConfig['context'] ?? 'reports',
            'type'     => $systemConfig['type'] ?? 'tabular', // preserve original type
        ];

        $data = [
            'user_id'       => Auth::id(),
            'config_key'    => $this->mainConfigKey,
            'name'          => $this->reportName,
            'type'          => 'tabular', // user reports are always tabular (builder only creates tabular)
            'configuration' => $configuration,
            'is_global'     => $this->isGlobal,
        ];

        if ($this->reportId) {
            SavedReport::where('id', $this->reportId)
                ->where('user_id', Auth::id())
                ->update($data);
            $message = 'Report updated successfully.';
        } else {
            SavedReport::create($data);
            $message = 'Report saved successfully.';
        }

        $this->dispatch('showAlert', ['type' => 'success', 'message' => $message]);
        return redirect()->route('reports.index');
    }

    public function render()
    {
        // Unique key for preview DataTable to force refresh when selected fields or filters change
        $tableKey = 'preview-' . $this->mainConfigKey . '-' . md5(json_encode($this->selectedFields) . json_encode($this->activeFilters));

        return view('qf::livewire.reports.report-builder', [
            'previewColumns' => $this->previewColumns,
            'tableKey'       => $tableKey,
        ]);
    }
}