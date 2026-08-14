<?php

namespace QuickerFaster\UILibrary\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Laravel\Fortify\Fortify;
use QuickerFaster\UILibrary\Components\Breadcrumbs;
use QuickerFaster\UILibrary\Services\Settings\SettingsManager;
use QuickerFaster\UILibrary\Services\Config\ModelConfigRepository;
use QuickerFaster\UILibrary\Events\ModuleRegistered;
use QuickerFaster\UILibrary\Events\ModuleBooted;

class UILibraryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/../Config/ui-library.php', 'ui-library');

        // Bind core services
        $this->app->singleton(SettingsManager::class, function ($app) {
            $manager = new SettingsManager();

            // Register resolvers from config
            $resolvers = config('ui-library.settings.resolvers', []);
            foreach ($resolvers as $name => $resolver) {
                if ($resolver && is_callable($resolver)) {
                    $manager->addResolver($name, $resolver);
                }
            }

            return $manager;
        });

        $this->app->singleton(ModelConfigRepository::class);

        $this->app->singleton(\QuickerFaster\UILibrary\Contracts\Approvals\ApprovalModelResolver::class,
            \QuickerFaster\UILibrary\Services\Approvals\ApprovalModelResolver::class);

        $this->app->bind(
            \QuickerFaster\UILibrary\Contracts\ActivityLogs\ActivityLogModelResolver::class,
            \QuickerFaster\UILibrary\Services\ActivityLogs\ActivityLogModelResolver::class
        );

        $this->app->singleton(\QuickerFaster\UILibrary\Services\Workflow\WorkflowEngine::class);

        $this->app->singleton(\QuickerFaster\UILibrary\Services\Documents\DocumentEngine::class);

        $this->app->singleton(\QuickerFaster\UILibrary\Services\Notifications\NotificationService::class, function ($app) {
            $service = new \QuickerFaster\UILibrary\Services\Notifications\NotificationService();
            $service->registerChannel('database', new \QuickerFaster\UILibrary\Services\Notifications\Channels\DatabaseChannel());
            $service->registerChannel('mail', new \QuickerFaster\UILibrary\Services\Notifications\Channels\MailChannel());
            return $service;
        });

        $this->app->singleton(\QuickerFaster\UILibrary\Services\Reports\ReportEngine::class);

        $this->app->singleton(
            \QuickerFaster\UILibrary\Contracts\ReferenceData\ReferenceDataProvider::class,
            \QuickerFaster\UILibrary\Services\ReferenceData\ReferenceDataService::class
        );

        // Phase 4.5: NavigationManager singleton for config-driven sidebar
        $this->app->singleton(\QuickerFaster\UILibrary\Services\Navigation\NavigationManager::class);

        // Workspace context resolver (multi-tenant / role-based navigation filtering)
        $this->app->singleton(
            \QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver::class,
            \QuickerFaster\UILibrary\Services\Navigation\NullWorkspaceResolver::class
        );

        $this->app->bind(
            \QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider::class,
            config('ui-library.navigation.company_provider', \QuickerFaster\UILibrary\Services\Navigation\NullCompanyProvider::class)
        );

        // Bind DataTable authorization provider (configurable, defaults to Spatie Permission-based)
        $this->app->bind(
            \QuickerFaster\UILibrary\Contracts\DataTables\DataTableAuthorizationProvider::class,
            config('ui-library.datatables.authorization_provider', \QuickerFaster\UILibrary\Services\DataTables\DefaultAuthorizationProvider::class)
        );

        // Bind ModelDiscovery service for access control model scanning
        $this->app->singleton(\QuickerFaster\UILibrary\Services\AccessControl\ModelDiscovery::class);

        // Bind public path for shared hosting compatibility
        $this->app->bind('path.public', function () {
            $sharedHostingPath = base_path('../public_html');
            if (is_dir($sharedHostingPath)) {
                return $sharedHostingPath;
            }
            return base_path('public');
        });
    }

    public function boot(): void
    {
        // Set Core module path in config
        config()->set('ui-library.module_paths.core', __DIR__ . '/../Core');

        // Load shared library routes (export, import, print, etc.)
        $sharedRoutesPath = __DIR__ . '/../Routes/web.php';
        if (file_exists($sharedRoutesPath)) {
            $this->loadRoutesFrom($sharedRoutesPath);
        }

        // Load shared library migrations
        $sharedMigrationsPath = __DIR__ . '/../../Database/Migrations';
        if (is_dir($sharedMigrationsPath)) {
            $this->loadMigrationsFrom($sharedMigrationsPath);
        }

        // Phase 4.4: Register view composers
        $this->registerViewComposers();

        // 1. Boot Core modules (Admin, System)
        $this->bootCoreModules();

        // 2. Fire ModuleRegistered for each Core module
        event(new ModuleRegistered('admin', __DIR__ . '/../Core/Admin',
            userFacing: config('ui-library.modules.admin.user_facing', true),
            dependsOn: config('ui-library.modules.admin.depends_on', []),
        ));
        event(new ModuleRegistered('system', __DIR__ . '/../Core/System',
            userFacing: config('ui-library.modules.system.user_facing', true),
            dependsOn: config('ui-library.modules.system.depends_on', []),
        ));
        event(new ModuleRegistered('organization', __DIR__ . '/../Core/Organization',
            userFacing: config('ui-library.modules.organization.user_facing', true),
            dependsOn: config('ui-library.modules.organization.depends_on', []),
        ));

        // 3. Fire ModuleBooted
        event(new ModuleBooted());

        // 4. Register views, components, Livewire, commands
        $this->registerViews();
        $this->registerBladeComponents();
        $this->registerLivewireComponents();
        $this->registerCommands();
        $this->registerPublishables();
        $this->registerFortifyViews();
        $this->registerSocialiteProviders();
        $this->registerBladeDirectives();
        $this->registerEventListeners();
        $this->registerTranslations();
    }

    private function bootCoreModules(): void
    {
        $corePath = __DIR__ . '/../Core';

        $first = true;
        foreach (['Admin', 'System', 'Organization'] as $module) {
            $moduleLower = strtolower($module);
            $modulePath = "{$corePath}/{$module}";

            // Register views under single 'qf-core' namespace
            // Routes use view('qf-core::admin.dashboard') → namespace qf-core, view admin/dashboard
            $viewPath = "{$modulePath}/Resources/views";
            if (is_dir($viewPath)) {
                if ($first) {
                    $this->loadViewsFrom($viewPath, 'qf-core');
                    $first = false;
                } else {
                    $this->app['view']->addNamespace('qf-core', $viewPath);
                }
                $this->publishes([
                    $viewPath => resource_path("views/vendor/ui-library/core/{$moduleLower}"),
                ], 'ui-library-core-views');
            }

            // Register routes
            $routePath = "{$modulePath}/Routes/web.php";
            if (file_exists($routePath)) {
                $this->loadRoutesFrom($routePath);
            }

            // Register migrations
            $migrationPath = "{$modulePath}/Database/Migrations";
            if (is_dir($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
                $this->publishes([
                    $migrationPath => database_path('migrations'),
                ], 'ui-library-migrations');
            }
        }
    }

    private function registerViews(): void
    {
        $viewPath = __DIR__ . '/../Resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'qf');
        }
    }

    private function registerBladeComponents(): void
    {
        Blade::component('qf::layouts.app', 'layout');
        Blade::component('qf::layouts.guest', 'guest-layout');
        Blade::component('qf::components.breadcrumb', 'breadcrumb');
        Blade::component(Breadcrumbs::class, 'breadcrumbs');
        Blade::componentNamespace('QuickerFaster\\UILibrary\\Components', 'qf');
    }

    private function registerLivewireComponents(): void
    {
        // Layout
        Livewire::component('qf.top-nav', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\TopNav::class);
        Livewire::component('qf.sidebar', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\Sidebar::class);
        Livewire::component('qf.bottom-bar', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\BottomBar::class);
        Livewire::component('qf.navigation-layout', \QuickerFaster\UILibrary\Http\Livewire\Layouts\NavigationLayout::class);
        Livewire::component('qf.horizontal-context-menu', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\HorizontalContextMenu::class);
        Livewire::component('qf.menu-renderer', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\MenuRenderer::class);
        Livewire::component('qf.module-switcher', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\ModuleSwitcher::class);
        Livewire::component('qf.workspace-tabs', \QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\WorkspaceTabs::class);

        // DataTables
        Livewire::component('qf.data-table', \QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTable::class);
        Livewire::component('qf.data-table-form', \QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTableForm::class);
        Livewire::component('qf.data-table-detail', \QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTableDetail::class);

        // Modals
        Livewire::component('qf.form-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\FormModal::class);
        Livewire::component('qf.detail-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\DetailModal::class);
        Livewire::component('qf.alert-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\AlertModal::class);
        Livewire::component('qf.import-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\ImportModal::class);
        Livewire::component('qf.export-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\ExportModal::class);
        Livewire::component('qf.export-progress', \QuickerFaster\UILibrary\Http\Livewire\Modals\ExportProgress::class);
        Livewire::component('qf.document-preview-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\DocumentPreviewModal::class);
        Livewire::component('qf.crop-image-modal', \QuickerFaster\UILibrary\Http\Livewire\Modals\CropImageModal::class);

        // Wizards
        Livewire::component('qf.wizard', \QuickerFaster\UILibrary\Http\Livewire\Wizards\Wizard::class);
        Livewire::component('qf.setup-wizard', \QuickerFaster\UILibrary\Http\Livewire\Wizards\SetupWizard::class);
        Livewire::component('qf.setup-checklist', \QuickerFaster\UILibrary\Http\Livewire\SetupChecklist::class);
        Livewire::component('qf.wizard-form', \QuickerFaster\UILibrary\Http\Livewire\Wizards\WizardForm::class);

        // Dashboard
        Livewire::component('qf.dashboard', \QuickerFaster\UILibrary\Http\Livewire\Dashboards\Dashboard::class);

        // Access Control
        Livewire::component('qf.access-control-manager', \QuickerFaster\UILibrary\Http\Livewire\AccessControls\AccessControlManager::class);
        Livewire::component('qf.module-selector', \QuickerFaster\UILibrary\Http\Livewire\AccessControls\ModuleSelector::class);
        Livewire::component('qf.role-assignment-manager', \QuickerFaster\UILibrary\Http\Livewire\AccessControls\RoleAssignmentManager::class);
        Livewire::component('qf.permission-manager', \QuickerFaster\UILibrary\Http\Livewire\AccessControls\PermissionManager::class);

        // Buttons
        Livewire::component('qf.toggle-button', \QuickerFaster\UILibrary\Http\Livewire\Buttons\ToggleButton::class);
        Livewire::component('qf.toggle-button-group', \QuickerFaster\UILibrary\Http\Livewire\Buttons\ToggleButtonGroup::class);

        // Documents
        Livewire::component('qf.document-preview', \QuickerFaster\UILibrary\Http\Livewire\DocumentPreview::class);

        // Reports
        Livewire::component('qf.report-index', \QuickerFaster\UILibrary\Http\Livewire\Reports\ReportIndex::class);
        Livewire::component('qf.report-viewer', \QuickerFaster\UILibrary\Http\Livewire\Reports\ReportViewer::class);
        Livewire::component('qf.report-builder', \QuickerFaster\UILibrary\Http\Livewire\Reports\ReportBuilder::class);

        // Settings
        Livewire::component('qf.settings-panel', \QuickerFaster\UILibrary\Http\Livewire\Settings\SettingsPanel::class);

        // Misc
        Livewire::component('qf.drawer', \QuickerFaster\UILibrary\Http\Livewire\Drawer::class);
        Livewire::component('qf.filter-panel', \QuickerFaster\UILibrary\Http\Livewire\FilterPanel::class);
        Livewire::component('qf.search-panel', \QuickerFaster\UILibrary\Http\Livewire\SearchPanel::class);
        Livewire::component('qf.collapsible', \QuickerFaster\UILibrary\Http\Livewire\Collapsible::class);
        Livewire::component('qf.background-jobs-panel', \QuickerFaster\UILibrary\Http\Livewire\BackgroundJobsPanel::class);
        Livewire::component('qf.column-manager', \QuickerFaster\UILibrary\Http\Livewire\ColumnManager::class);
        Livewire::component('qf.import-form', \QuickerFaster\UILibrary\Http\Livewire\DataTables\ImportForm::class);
        Livewire::component('qf.recent-exports', \QuickerFaster\UILibrary\Http\Livewire\Exports\RecentExports::class);
        Livewire::component('qf.recent-imports', \QuickerFaster\UILibrary\Http\Livewire\Imports\RecentImports::class);

        // Approvals
        Livewire::component('qf.approval-actions', \QuickerFaster\UILibrary\Http\Livewire\Approvals\ApprovalActions::class);
        Livewire::component('qf.approval-history-timeline', \QuickerFaster\UILibrary\Http\Livewire\Approvals\ApprovalHistoryTimeline::class);
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \QuickerFaster\UILibrary\Commands\QuickerFasterInstallUI::class,
                \QuickerFaster\UILibrary\Commands\CleanExports::class,
                \QuickerFaster\UILibrary\Commands\CleanImportErrors::class,
                \QuickerFaster\UILibrary\Console\Commands\GenerateScheduledReports::class,
                \QuickerFaster\UILibrary\Console\Commands\InstallCommand::class,
            ]);
        }
    }

    private function registerPublishables(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../Config/ui-library.php' => config_path('ui-library.php'),
        ], 'ui-library-config');

        // Assets
        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/ui-library'),
        ], 'ui-library-assets');

        // Views
        $this->publishes([
            __DIR__ . '/../Resources/views' => resource_path('views/vendor/ui-library'),
        ], 'ui-library-views');
    }

    private function registerFortifyViews(): void
    {
        Fortify::loginView(fn() => view('qf::auth.login'));
        Fortify::registerView(fn() => view('qf::auth.register'));
        Fortify::requestPasswordResetLinkView(fn() => view('qf::auth.forgot-password'));
        Fortify::resetPasswordView(fn() => view('qf::auth.reset-password'));
    }

    private function registerSocialiteProviders(): void
    {
        $providers = ['google', 'github'];
        foreach ($providers as $provider) {
            if (config("ui-library.socialite.providers.{$provider}.enabled")) {
                config([
                    "services.{$provider}" => [
                        'client_id' => env(strtoupper($provider) . '_CLIENT_ID'),
                        'client_secret' => env(strtoupper($provider) . '_CLIENT_SECRET'),
                        'redirect' => env(strtoupper($provider) . '_REDIRECT_URI', ''),
                    ],
                ]);
            }
        }
    }

    private function registerBladeDirectives(): void
    {
        Blade::directive('setting', function ($expression) {
            return "<?php echo app(\\QuickerFaster\\UILibrary\\Services\\Settings\\SettingsManager::class)->get({$expression}); ?>";
        });
    }

    /**
     * Register library-level event listeners that are not auto-discovered
     * from module Listeners directories by ModuleServiceProvider.
     */
    private function registerEventListeners(): void
    {
        Event::listen(
            \QuickerFaster\UILibrary\Events\ToggleButtonEvent::class,
            \QuickerFaster\UILibrary\Listeners\ToggleButtonListener::class
        );
    }

    /**
     * Phase 4.4: Register view composers for injecting data into views.
     */
    private function registerViewComposers(): void
    {
        // Attach SidebarComposer to the sidebar Livewire view
        View::composer(
            'qf::livewire.navs.sidebar',
            \QuickerFaster\UILibrary\Http\ViewComposers\SidebarComposer::class
        );
    }

    private function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'qf');
    }
}
