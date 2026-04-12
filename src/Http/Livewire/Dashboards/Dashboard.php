<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Dashboards;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\Dashboards\DashboardResolver;
use QuickerFaster\UILibrary\Services\Widgets\WidgetProcessor;

class Dashboard extends Component
{
    public string $configKey;
    public array $widgetsData = [];
    public array $layout = [];
    public array $parameters = [];
    public string $title = '';
    public string $description = '';

    /** @var array Custom widget definitions (overrides config file) */
    public array $customWidgets = [];

    public function mount(string $configKey, array $parameters = [], array $customWidgets = [])
    {
        $this->configKey = $configKey;
        $this->parameters = $parameters;
        $this->customWidgets = $customWidgets;
        $this->loadDashboard();
    }

    protected function getResolver(): DashboardResolver
    {
        return app(DashboardResolver::class, ['configKey' => $this->configKey]);
    }

    protected function loadDashboard(): void
    {
        // NEW: If custom widgets are provided, use them directly
        if (!empty($this->customWidgets)) {
            $this->title = $this->customWidgets['title'] ?? '';
            $this->description = $this->customWidgets['description'] ?? '';
            $this->layout = $this->customWidgets['layout'] ?? ['columns' => 12, 'gutter' => 3];
            $widgetDefinitions = $this->customWidgets['widgets'] ?? [];
        } else {
            $resolver = new DashboardResolver($this->configKey, $this->parameters);
            $config = $resolver->getConfig();
            $this->title = $config['title'] ?? '';
            $this->description = $config['description'] ?? '';
            $this->layout = $config['layout'] ?? ['columns' => 12, 'gutter' => 3];
            $widgetDefinitions = $config['widgets'] ?? [];
        }

        $processor = new WidgetProcessor();
        $this->widgetsData = $processor->processAll($widgetDefinitions);
    }

    public function render()
    {
        return view('qf::livewire.dashboards.dashboard', [
            'widgetsData' => $this->widgetsData,
            'layout' => $this->layout,
            'title' => $this->title,
            'description' => $this->description,
        ]);
    }
}