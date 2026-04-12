<?php

namespace QuickerFaster\UILibrary\Services\Config\Dashboards;

use QuickerFaster\UILibrary\Services\Config\ModelConfigRepository;

class DashboardResolver
{
    protected array $config;
    protected array $parameters;

    /**
     * @param string $configKey  e.g., 'hr.dashboard_time_overview' (module.config_name)
     * @param array $parameters  Placeholder replacements
     * @param ModelConfigRepository|null $repository
     */
    public function __construct(string $configKey, array $parameters = [], ?ModelConfigRepository $repository = null)
    {
        $repository = $repository ?? app(ModelConfigRepository::class);
        $this->config = $repository->get($configKey);
        $this->parameters = $parameters;

        if (!$this->config) {
            throw new \InvalidArgumentException("Dashboard configuration not found for key: {$configKey}");
        }

        $this->replacePlaceholders();
    }

    protected function replacePlaceholders(): void
    {
        array_walk_recursive($this->config, function (&$value) {
            if (is_string($value)) {
                foreach ($this->parameters as $key => $replacement) {
                    $value = str_replace('{{ ' . $key . ' }}', $replacement, $value);
                }
            }
        });
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getLayout(): array
    {
        return $this->config['layout'] ?? ['columns' => 12, 'gutter' => 3];
    }

    public function getWidgets(): array
    {
        return $this->config['widgets'] ?? [];
    }
}