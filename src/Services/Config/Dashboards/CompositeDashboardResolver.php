<?php

namespace QuickerFaster\UILibrary\Services\Config\Dashboards;

use QuickerFaster\UILibrary\Services\Config\ModelConfigRepository;

/**
 * CompositeDashboardResolver — aggregates widgets from multiple dashboard
 * config keys into a single unified dashboard.
 *
 * This enables cross-module dashboards like the My Portal ESS dashboard,
 * which pulls widgets from HR, Leave, Attendance, Payroll, and Holiday
 * modules without requiring a single monolithic config file.
 *
 * Usage:
 *   $resolver = new CompositeDashboardResolver([
 *       'hr.dashboards.dashboard_my_portal',
 *       'leave.dashboards.dashboard_my_leave',
 *   ], $parameters);
 *
 *   $widgets = $resolver->getWidgets();  // Merged from all configs
 */
class CompositeDashboardResolver
{
    /** @var array<string> Dashboard config keys to aggregate */
    protected array $configKeys;

    /** @var array Placeholder replacements applied to all configs */
    protected array $parameters;

    /** @var ModelConfigRepository */
    protected ModelConfigRepository $repository;

    /** @var array Merged config */
    protected array $merged = [];

    /**
     * @param array<string> $configKeys  Dashboard config keys to aggregate
     * @param array $parameters          Placeholder replacements
     * @param ModelConfigRepository|null $repository
     */
    public function __construct(
        array $configKeys,
        array $parameters = [],
        ?ModelConfigRepository $repository = null
    ) {
        $this->configKeys = $configKeys;
        $this->parameters = $parameters;
        $this->repository = $repository ?? app(ModelConfigRepository::class);

        $this->resolve();
    }

    /**
     * Resolve and merge all dashboard configs.
     */
    protected function resolve(): void
    {
        $allWidgets = [];
        $title = '';
        $description = '';
        $layout = ['columns' => 12, 'gutter' => 3];
        $hero = [];
        $stats = [];
        $roles = [];

        foreach ($this->configKeys as $key) {
            $config = $this->repository->get($key);

            if (!$config) {
                continue;
            }

            // Apply placeholder replacements
            $this->replacePlaceholders($config);

            // Use the first config's title/description as the composite title
            if (empty($title) && !empty($config['title'])) {
                $title = $config['title'];
            }
            if (empty($description) && !empty($config['description'])) {
                $description = $config['description'];
            }

            // Merge widgets
            if (!empty($config['widgets'])) {
                $allWidgets = array_merge($allWidgets, $config['widgets']);
            }

            // Merge hero
            if (!empty($config['hero'])) {
                $hero = array_merge($hero, $config['hero']);
            }

            // Merge stats
            if (!empty($config['stats'])) {
                $stats = array_merge($stats, $config['stats']);
            }

            // Merge roles (union)
            if (!empty($config['roles'])) {
                $roles = array_unique(array_merge($roles, (array) $config['roles']));
            }

            // Use the last layout (or first non-default)
            if (!empty($config['layout'])) {
                $layout = $config['layout'];
            }
        }

        $this->merged = [
            'title'       => $title,
            'description' => $description,
            'widgets'     => $allWidgets,
            'hero'        => $hero,
            'stats'       => $stats,
            'roles'       => $roles,
            'layout'      => $layout,
        ];
    }

    /**
     * Apply placeholder replacements to a config array.
     */
    protected function replacePlaceholders(array &$config): void
    {
        array_walk_recursive($config, function (&$value) {
            if (is_string($value)) {
                foreach ($this->parameters as $key => $replacement) {
                    $value = str_replace('{{ ' . $key . ' }}', $replacement, $value);
                }
            }
        });
    }

    /**
     * Get the merged dashboard config.
     */
    public function getConfig(): array
    {
        return $this->merged;
    }

    /**
     * Get all merged widgets.
     */
    public function getWidgets(): array
    {
        return $this->merged['widgets'] ?? [];
    }

    /**
     * Get the merged layout.
     */
    public function getLayout(): array
    {
        return $this->merged['layout'] ?? ['columns' => 12, 'gutter' => 3];
    }

    /**
     * Get the merged hero section.
     */
    public function getHero(): array
    {
        return $this->merged['hero'] ?? [];
    }

    /**
     * Get the merged stats.
     */
    public function getStats(): array
    {
        return $this->merged['stats'] ?? [];
    }

    /**
     * Get the merged roles.
     */
    public function getRoles(): array
    {
        return $this->merged['roles'] ?? [];
    }
}