<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Dashboards;

use Illuminate\Support\Facades\Log;
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
    public array $hero = [];
    public array $stats = [];

    /** @var array Custom widget definitions (overrides config file) */
    public array $customWidgets = [];

    /** @var string|null Error message when dashboard config cannot be loaded. */
    public ?string $errorMessage = null;

    public function mount(string $configKey, array $parameters = [], array $customWidgets = [])
    {
        $this->configKey = $configKey;
        $this->parameters = $parameters;
        $this->customWidgets = $customWidgets;

        try {
            $this->loadDashboard();
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = 'Dashboard configuration not found: ' . $this->configKey;
            Log::error($e->getMessage(), [
                'configKey' => $this->configKey,
                'exception' => $e,
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'An unexpected error occurred while loading the dashboard: ' . $this->configKey;
            Log::error($e->getMessage(), [
                'configKey' => $this->configKey,
                'exception' => $e,
            ]);
        }
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
            $this->hero = $this->customWidgets['hero'] ?? [];
            $this->stats = $this->customWidgets['stats'] ?? [];
            $this->layout = $this->customWidgets['layout'] ?? ['columns' => 12, 'gutter' => 3];
            $widgetDefinitions = $this->customWidgets['widgets'] ?? [];
        } else {
            $resolver = new DashboardResolver($this->configKey, $this->parameters);
            $config = $resolver->getConfig();
            $this->title = $config['title'] ?? '';
            $this->description = $config['description'] ?? '';
            $this->hero = $resolver->getHero();
            $this->stats = $resolver->getStats();
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
            'hero' => $this->hero,
            'stats' => $this->stats,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}