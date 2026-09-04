<?php

namespace QuickerFaster\UILibrary\Console\Commands;

use Illuminate\Console\Command;
use QuickerFaster\UILibrary\Services\Discovery\DiscoveryRegistrar;

class DiscoverCommand extends Command
{
    protected $signature = 'ui-library:discover';

    protected $description = 'List all auto-discovered business-module assets (modules, listeners, reports, workflows, configs)';

    public function handle(DiscoveryRegistrar $registrar): int
    {
        $discovered = $registrar->discover();

        $this->info('QuickerFaster UI Library auto-discovery');
        $this->newLine();

        $this->renderModules($discovered['modules'] ?? []);
        $this->renderListeners($discovered['listeners'] ?? []);
        $this->renderReports($discovered['reports'] ?? []);
        $this->renderWorkflows($discovered['workflows'] ?? []);
        $this->renderConfigs($discovered['configs'] ?? []);
        $this->renderPermissions($discovered['permissions'] ?? []);
        $this->renderNotifications($discovered['notifications'] ?? []);

        $totalListeners = $this->flattenCount($discovered['listeners'] ?? []);
        $totalReports = count($discovered['reports'] ?? []);
        $totalWorkflows = count($discovered['workflows'] ?? []);
        $totalPermissions = $this->countPermissions($discovered['permissions'] ?? []);
        $totalNotificationTemplates = $this->countNotificationTemplates($discovered['notifications'] ?? []);

        $this->newLine();
        $this->line(sprintf(
            'Summary: %d module(s), %d listener(s), %d report type(s), %d workflow definition(s), %d permission(s), %d notification template(s).',
            count($discovered['modules'] ?? []),
            $totalListeners,
            $totalReports,
            $totalWorkflows,
            $totalPermissions,
            $totalNotificationTemplates
        ));

        return self::SUCCESS;
    }

    /**
     * @param array<string, string> $modules
     */
    private function renderModules(array $modules): void
    {
        $this->line('<fg=cyan>Modules</>');
        if ($modules === []) {
            $this->line('  <fg=gray>(none discovered)</>');

            return;
        }

        foreach ($modules as $key => $path) {
            $this->line("  <fg=green>{$key}</> <fg=gray>({$path})</>");
        }
        $this->newLine();
    }

    /**
     * @param array<string, array<string, string[]>> $listeners
     */
    private function renderListeners(array $listeners): void
    {
        $this->line('<fg=cyan>Listeners</>');
        if ($listeners === []) {
            $this->line('  <fg=gray>(none discovered)</>');

            return;
        }

        foreach ($listeners as $module => $events) {
            $this->line("  <fg=green>{$module}</>");
            foreach ($events as $event => $classes) {
                foreach ((array) $classes as $class) {
                    $this->line("    <fg=yellow>{$event}</> => {$class}");
                }
            }
        }
        $this->newLine();
    }

    /**
     * @param array<string, array<string, string>> $reports
     */
    private function renderReports(array $reports): void
    {
        $this->line('<fg=cyan>Reports</>');
        if ($reports === []) {
            $this->line('  <fg=gray>(none discovered)</>');

            return;
        }

        foreach ($reports as $module => $types) {
            $this->line("  <fg=green>{$module}</>");
            foreach ($types as $type => $class) {
                $this->line("    <fg=yellow>{$type}</> => {$class}");
            }
        }
        $this->newLine();
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $workflows
     */
    private function renderWorkflows(array $workflows): void
    {
        $this->line('<fg=cyan>Workflows</>');
        if ($workflows === []) {
            $this->line('  <fg=gray>(none discovered)</>');

            return;
        }

        foreach ($workflows as $module => $definitions) {
            $this->line("  <fg=green>{$module}</>");
            foreach ($definitions as $key => $definition) {
                $label = is_array($definition) && isset($definition['label'])
                    ? " ({$definition['label']})"
                    : '';
                $this->line("    <fg=yellow>{$key}</>{$label}");
            }
        }
        $this->newLine();
    }

    /**
     * @param array{dashboards: array<int, array<string, string>>, reports: array<int, array<string, string>>} $configs
     */
    private function renderConfigs(array $configs): void
    {
        $this->line('<fg=cyan>Configs (Data merges)</>');

        $dashboards = $configs['dashboards'] ?? [];
        $reports = $configs['reports'] ?? [];

        if ($dashboards === [] && $reports === []) {
            $this->line('  <fg=gray>(none discovered)</>');

            return;
        }

        foreach ($dashboards as $config) {
            $this->line("  <fg=green>{$config['module']}</> dashboard <fg=yellow>{$config['config']}</>");
        }
        foreach ($reports as $config) {
            $this->line("  <fg=green>{$config['module']}</> report config <fg=yellow>{$config['config']}</>");
        }
        $this->newLine();
    }

    /**
     * @param array<string, array{models: array<string, string[]>, extra: string[]}> $permissions
     */
    private function renderPermissions(array $permissions): void
    {
        $this->line('<fg=cyan>Permissions</>');
        if ($permissions === []) {
            $this->line('  <fg=gray>(none discovered)</>');

            return;
        }

        foreach ($permissions as $module => $data) {
            $this->line("  <fg=green>{$module}</>");

            if (!empty($data['models'])) {
                foreach ($data['models'] as $model => $perms) {
                    $this->line("    <fg=yellow>{$model}</> → " . implode(', ', $perms));
                }
            }

            if (!empty($data['extra'])) {
                foreach ($data['extra'] as $extra) {
                    $this->line("    <fg=magenta>extra</> → {$extra}");
                }
            }
        }
        $this->newLine();
    }

    /**
     * @param array<string, array{templates: array<int, array<string, string>>, channels: array<string, string>}> $notifications
     */
    private function renderNotifications(array $notifications): void
    {
        $this->line('<fg=cyan>Notifications</>');
        if ($notifications === []) {
            $this->line('  <fg=gray>(none discovered)</>');

            return;
        }

        foreach ($notifications as $module => $data) {
            $this->line("  <fg=green>{$module}</>");

            if (!empty($data['templates'])) {
                foreach ($data['templates'] as $template) {
                    $code = $template['code'] ?? '?';
                    $channel = $template['channel'] ?? '?';
                    $this->line("    <fg=yellow>template</> {$code} <fg=gray>({$channel})</>");
                }
            }

            if (!empty($data['channels'])) {
                foreach ($data['channels'] as $channel) {
                    $name = $channel['name'] ?? '?';
                    $class = $channel['class'] ?? '?';
                    $this->line("    <fg=magenta>channel</> {$name} → {$class}");
                }
            }
        }
        $this->newLine();
    }

    /**
     * @param array<string, array{models: array<string, string[]>, extra: string[]}> $permissions
     */
    private function countPermissions(array $permissions): int
    {
        $count = 0;

        foreach ($permissions as $data) {
            foreach ($data['models'] ?? [] as $perms) {
                $count += count($perms);
            }
            $count += count($data['extra'] ?? []);
        }

        return $count;
    }

    /**
     * @param array<string, array{templates: array<int, array<string, string>>, channels: array<string, string>}> $notifications
     */
    private function countNotificationTemplates(array $notifications): int
    {
        $count = 0;

        foreach ($notifications as $data) {
            $count += count($data['templates'] ?? []);
        }

        return $count;
    }

    private function flattenCount(array $map): int
    {
        $count = 0;

        foreach ($map as $events) {
            foreach ((array) $events as $classes) {
                $count += count((array) $classes);
            }
        }

        return $count;
    }
}
