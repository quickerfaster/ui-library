<?php

namespace QuickerFaster\UILibrary\Services\Notifications;

use Illuminate\Support\Facades\File;

/**
 * Scans business modules for notification conventions and auto-registers
 * templates and channels.
 *
 * Convention: app/Modules/{Module}/Data/notifications.php
 *
 * Each file must return an array with optional keys:
 *   - 'templates' => array of ['code' => '...', 'type' => '...', 'channel' => '...',
 *                              'subject' => '...', 'body' => '...', 'locale' => 'en']
 *   - 'channels'  => array of ['name' => 'FQCN'] additional notification channel classes
 *
 * Opt-out: config('ui-library.modules.{module}.auto_register_notifications')
 */
class NotificationDiscoveryService
{
    /**
     * Scan all business modules for notifications.php files and return
     * discovered templates and channels.
     *
     * @return array{templates: array<int, array<string, string>>, channels: array<string, string>}
     */
    public function discover(): array
    {
        $businessPath = config('ui-library.module_paths.business', app_path('Modules'));

        if (!is_dir($businessPath)) {
            return ['templates' => [], 'channels' => []];
        }

        $allTemplates = [];
        $allChannels = [];

        $moduleDirs = glob($businessPath . '/*', GLOB_ONLYDIR) ?: [];

        foreach ($moduleDirs as $moduleDir) {
            $moduleName = strtolower(basename($moduleDir));

            // Check opt-out
            if (!config("ui-library.modules.{$moduleName}.auto_register_notifications", true)) {
                continue;
            }

            $configPath = $moduleDir . '/Data/notifications.php';

            if (!File::exists($configPath)) {
                continue;
            }

            $config = require $configPath;

            if (!is_array($config)) {
                continue;
            }

            // Merge templates
            if (!empty($config['templates']) && is_array($config['templates'])) {
                foreach ($config['templates'] as $template) {
                    if (is_array($template) && isset($template['code'], $template['channel'])) {
                        $allTemplates[] = [
                            'type' => $template['code'],
                            'channel' => $template['channel'],
                            'subject' => $template['subject'] ?? $template['code'],
                            'body_template' => $template['body'] ?? '',
                            'locale' => $template['locale'] ?? 'en',
                        ];
                    }
                }
            }

            // Merge channels
            if (!empty($config['channels']) && is_array($config['channels'])) {
                foreach ($config['channels'] as $channel) {
                    if (is_array($channel) && isset($channel['name'], $channel['class'])) {
                        $allChannels[$channel['name']] = $channel['class'];
                    }
                }
            }
        }

        return [
            'templates' => $allTemplates,
            'channels' => $allChannels,
        ];
    }

    /**
     * Merge discovered channels into ui-library.notifications.channels.
     * Published config wins on conflict (deepMerge semantics).
     */
    public function registerChannels(): void
    {
        $discovered = $this->discover();

        if (empty($discovered['channels'])) {
            return;
        }

        $existing = config('ui-library.notifications.channels', []);

        // Discovered channels go under existing (published wins)
        foreach ($discovered['channels'] as $name => $class) {
            if (!isset($existing[$name])) {
                $existing[$name] = $class;
            }
        }

        config()->set('ui-library.notifications.channels', $existing);
    }

    /**
     * Get discovered templates grouped by module for the discover command.
     *
     * @return array<string, array{templates: array<int, array<string, string>>, channels: array<string, string>}>
     */
    public function discoverForCommand(): array
    {
        $businessPath = config('ui-library.module_paths.business', app_path('Modules'));

        if (!is_dir($businessPath)) {
            return [];
        }

        $result = [];

        $moduleDirs = glob($businessPath . '/*', GLOB_ONLYDIR) ?: [];

        foreach ($moduleDirs as $moduleDir) {
            $moduleName = strtolower(basename($moduleDir));

            if (!config("ui-library.modules.{$moduleName}.auto_register_notifications", true)) {
                continue;
            }

            $configPath = $moduleDir . '/Data/notifications.php';

            if (!File::exists($configPath)) {
                continue;
            }

            $config = require $configPath;

            if (!is_array($config)) {
                continue;
            }

            $moduleResult = [
                'templates' => [],
                'channels' => [],
            ];

            if (!empty($config['templates']) && is_array($config['templates'])) {
                $moduleResult['templates'] = $config['templates'];
            }

            if (!empty($config['channels']) && is_array($config['channels'])) {
                $moduleResult['channels'] = $config['channels'];
            }

            if (!empty($moduleResult['templates']) || !empty($moduleResult['channels'])) {
                $result[$moduleName] = $moduleResult;
            }
        }

        return $result;
    }
}