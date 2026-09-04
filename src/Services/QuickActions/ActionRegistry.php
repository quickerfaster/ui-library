<?php

namespace QuickerFaster\UILibrary\Services\QuickActions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/**
 * ActionRegistry discovers and merges all module quick-actions configs
 * into a unified list of available actions.
 *
 * Discovery priority chain (mirrors NavigationManager):
 *   1. Published override (config/ui-library.php quick_actions.actions)
 *   2. Business module path (app/Modules/{Module}/Config/quick-actions.php)
 *   3. Core module path (src/Core/{Module}/Config/quick-actions.php)
 */
class ActionRegistry
{
    /**
     * Cached merged actions.
     *
     * @var array<int, array>|null
     */
    protected ?array $cachedActions = null;

    /**
     * Collect all registered quick actions from all modules.
     *
     * @return array<int, array>
     */
    public function all(): array
    {
        if ($this->cachedActions !== null) {
            return $this->cachedActions;
        }

        $actions = [];

        // 1. Check for published override in ui-library config
        $publishedActions = config('ui-library.quick_actions.actions', []);
        if (!empty($publishedActions)) {
            $actions = $publishedActions;
        }

        // 2. Discover from Core modules
        $corePath = config('ui-library.module_paths.core', __DIR__ . '/../../Core');
        if (is_dir($corePath)) {
            $coreModules = File::directories($corePath);
            foreach ($coreModules as $modulePath) {
                $configPath = $modulePath . '/Config/quick-actions.php';
                if (File::exists($configPath)) {
                    $moduleActions = require $configPath;
                    if (is_array($moduleActions)) {
                        // Support both wrapped ['quick_actions' => [...]] and flat [...]
                        $moduleActions = $moduleActions['quick_actions'] ?? $moduleActions;
                        $actions = array_merge($actions, $moduleActions);
                    }
                }
            }
        }

        // 3. Discover from Business modules (consuming app)
        $businessPath = base_path('app/Modules');
        if (is_dir($businessPath)) {
            $businessModules = File::directories($businessPath);
            foreach ($businessModules as $modulePath) {
                $configPath = $modulePath . '/Config/quick-actions.php';
                if (File::exists($configPath)) {
                    $moduleActions = require $configPath;
                    if (is_array($moduleActions)) {
                        $moduleActions = $moduleActions['quick_actions'] ?? $moduleActions;
                        $actions = array_merge($actions, $moduleActions);
                    }
                }
            }
        }

        // Normalize and validate each action
        $actions = $this->normalizeActions($actions);

        $this->cachedActions = $actions;

        return $this->cachedActions;
    }

    /**
     * Filter actions by the currently authenticated user's permissions and roles.
     *
     * @return array<int, array>
     */
    public function authorizedFor(): array
    {
        $actions = $this->all();

        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();

        return array_values(array_filter($actions, function (array $action) use ($user) {
            return $this->isActionAuthorized($action, $user);
        }));
    }

    /**
     * Get a single action by its key.
     *
     * @param  string $key
     * @return array|null
     */
    public function findByKey(string $key): ?array
    {
        $actions = $this->all();

        foreach ($actions as $action) {
            if (($action['id'] ?? $action['key'] ?? '') === $key) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Check whether a given action is authorized for the specified user.
     *
     * @param  array       $action
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return bool
     */
    protected function isActionAuthorized(array $action, $user): bool
    {
        // Check permission (Spatie)
        if (!empty($action['permission'])) {
            if (method_exists($user, 'can') && !$user->can($action['permission'])) {
                return false;
            }
        }

        // Check roles
        if (!empty($action['roles'])) {
            $roles = $action['roles'];
            $isWildcard = ($roles === '*' || $roles === ['*']);

            if (!$isWildcard) {
                if (method_exists($user, 'hasAnyRole') && !$user->hasAnyRole((array) $roles)) {
                    return false;
                }
            }
        }

        // Check module dependency
        if (!empty($action['depends_on'])) {
            $moduleEnabled = config("ui-library.modules.{$action['depends_on']}.enabled", false);
            if (!$moduleEnabled) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalize action entries, ensuring required fields and defaults.
     *
     * @param  array<int, array> $actions
     * @return array<int, array>
     */
    protected function normalizeActions(array $actions): array
    {
        $normalized = [];

        foreach ($actions as $index => $action) {
            if (!is_array($action)) {
                continue;
            }

            // Support 'key' (design doc) or 'id' (task spec) as the unique identifier
            $id = $action['id'] ?? $action['key'] ?? 'action_' . $index;

            $normalized[] = [
                'id'          => $id,
                'label'       => $action['label'] ?? $id,
                'description' => $action['description'] ?? '',
                'icon'        => $action['icon'] ?? 'fas fa-bolt',
                'action'      => $action['action'] ?? 'navigate',
                'module'      => $action['module'] ?? $this->inferModule($id),
                'keywords'    => $action['keywords'] ?? [],
                'category'    => $action['category'] ?? null,
                'url'         => $action['url'] ?? null,
                'route'       => $action['route'] ?? null,
                'shortcut'    => $action['shortcut'] ?? null,
                'permission'  => $action['permission'] ?? null,
                'roles'       => $action['roles'] ?? null,
                'method'      => $action['method'] ?? 'GET',
                'confirm'     => $action['confirm'] ?? null,
                'livewire_event' => $action['livewire_event'] ?? null,
                'depends_on'  => $action['depends_on'] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * Infer the module name from the action ID (e.g., "admin.view_dashboard" → "admin").
     *
     * @param  string $id
     * @return string
     */
    protected function inferModule(string $id): string
    {
        $parts = explode('.', $id);
        return $parts[0] ?? 'unknown';
    }

    /**
     * Clear the cached actions (useful for testing or runtime config changes).
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cachedActions = null;
    }
}