<?php

namespace QuickerFaster\UILibrary\Services\Config;
use QuickerFaster\UILibrary\Services\Settings\SettingsManager;

class ConfigResolver
{
    protected array $config;
    protected $settingsManager;

    /**
     * @param string $configKey  Dot notation key, e.g., 'hr.employee'
     */
    public function __construct(string $configKey, ?ModelConfigRepository $repository = null)
    {

        $repository = $repository ?? app(ModelConfigRepository::class);
        $this->config = $repository->get($configKey);




        $this->settingsManager = app(SettingsManager::class);
        
        if (!$this->config) {
            throw new \InvalidArgumentException("Configuration not found for key: {$configKey}");
        }
    }

    public function getModel(): string
    {
        return $this->config['model']?? '';
    }

    public function getModelName(): string
    {
        $modelParts = explode("\\", $this->getModel());
        return $modelParts[count($modelParts) -1];
    }


    public function getModuleName(): string
    {
        $modelParts = explode("\\", $this->getModel());
        return $modelParts[2];
    }



    public function getFieldDefinitions(): array
    {
        return $this->config['fieldDefinitions'];
    }


public function getSettingsOverrideFieldDefinition(string $field): array
{
    $def = $this->getFieldDefinitions()[$field] ?? [];

    // Override date format from settings
    if (isset($def['field_type']) && $def['field_type'] === 'datepicker') {
        $dateFormat = $this->settingsManager->get('date_format', 'Y-m-d');
        $def['date_format'] = $dateFormat;
    }

    // Override currency from settings
    if (isset($def['field_type']) && $def['field_type'] === 'currency') {
        $currency = $this->settingsManager->get('currency', 'USD');
        $def['currency'] = $currency;
    }

    return $def;
}





    public function getFieldGroups(): array
    {
        return $this->config['fieldGroups'] ?? [];
    }

    public function getControls(): array
    {
        $controls = $this->config['controls'] ?? [];
        if ($controls && !is_array($controls) && strtolower($controls) == "all")
            return $this->getAllControls();

        foreach($this->getAllControls() as $controlName => $controlDef) {
            $controls[$controlName] = $controls[$controlName] ?? null;
        }
        
        return $controls;
    }

    public function getRelations(): array
    {
        return $this->config['relations'] ?? [];
    }

    public function getHiddenFields(): array
    {
        return $this->config['hiddenFields'] ?? [
            'onTable' => [],
            'onNewForm' => [],
            'onEditForm' => [],
            'onQuery' => [],
            'onDetail'  => [], 

        ];
    }

    public function getSwitchViews(): array
    {
        return $this->config['switchViews'] ?? [];
    }



    public function getConfig()
    {
        return $this->config;
    }



    // Add more getters as needed (simpleActions, moreActions, isTransaction, dispatchEvents, etc.)

    public function getMoreActions(): array
    {
        return $this->config['moreActions'] ?? [];
    }


    protected function getAllControls()
    {
        return [
            'files' => [
                'export' => ['xls', 'csv', 'pdf'],
                'import' => ['xls', 'csv'],
                'print' => true,
            ],
            'bulkActions' => [
                'export' => ['xls', 'csv', 'pdf'],
                'delete' => true
            ],
            'perPage' => [10, 25, 50, 100],
            'search' => true,
            'showHideColumns' => true,
            'filterColumns' => true,
            'addButton' => true,

        ];
    }






/**
 * Get all reports defined in the configuration.
 * Returns an array keyed by report key, each containing label, type, etc.
 */
public function getReports(): array
{
    return $this->config['reports'] ?? [];
}

/**
 * Get a specific report by its key.
 */
public function getReport(string $reportKey): ?array
{
    $reports = $this->getReports();
    return $reports[$reportKey] ?? null;
}





}
