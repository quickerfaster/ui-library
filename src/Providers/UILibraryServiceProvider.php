<?php

namespace QuickerFaster\UILibrary\Providers;

use QuickerFaster\UILibrary\Http\Livewire\AccessControls\AccessControlManager;
use App\Modules\Admin\Http\Livewire\AccessControls\ModuleSelector;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;


use Livewire\Livewire;
use QuickerFaster\UILibrary\Http\Livewire\Custom\EmployeeDetail;
use QuickerFaster\UILibrary\Http\Livewire\Dashboards\Dashboard;
use QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTable;
use QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTableForm;
use QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTableDetail;
use QuickerFaster\UILibrary\Http\Livewire\DataTables\ImportForm;

use QuickerFaster\UILibrary\Http\Livewire\DocumentPreview;
use QuickerFaster\UILibrary\Http\Livewire\Drawer;
use QuickerFaster\UILibrary\Http\Livewire\Modals\ExportProgress;
use QuickerFaster\UILibrary\Http\Livewire\Modals\FormModal;
use QuickerFaster\UILibrary\Http\Livewire\Modals\DetailModal;
use QuickerFaster\UILibrary\Http\Livewire\Modals\AlertModal;
use QuickerFaster\UILibrary\Http\Livewire\Modals\ExportModal;
use QuickerFaster\UILibrary\Http\Livewire\Modals\ImportModal;

use QuickerFaster\UILibrary\Http\Livewire\Wizards\Wizard;
use QuickerFaster\UILibrary\Http\Livewire\Wizards\WizardForm;

use QuickerFaster\UILibrary\Http\Livewire\FilterPanel;


use QuickerFaster\UILibrary\Services\Iimpors\ImportProcessor;
use QuickerFaster\UILibrary\Commands\QuickerFasterInstallUI;
use QuickerFaster\UILibrary\Http\Livewire\Buttons\ToggleButton;
use QuickerFaster\UILibrary\Http\Livewire\Buttons\ToggleButtonGroup;
use QuickerFaster\UILibrary\Http\Livewire\Layouts\NavigationLayout;
use QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\BottomBar;
use QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\HorizontalContextMenu;
use QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\MenuRenderer;
use QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\Sidebar;
use QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs\TopNav;
use QuickerFaster\UILibrary\Http\Livewire\Modals\DocumentPreviewModal;
use QuickerFaster\UILibrary\Http\Livewire\SetupChecklist;
use QuickerFaster\UILibrary\Http\Livewire\Wizards\SetupWizard;
use QuickerFaster\UILibrary\Http\Livewire\Modals\CropImageModal;
use QuickerFaster\UILibrary\Http\Livewire\Reports\ReportBuilder;
use QuickerFaster\UILibrary\Http\Livewire\Reports\ReportIndex;
use QuickerFaster\UILibrary\Http\Livewire\Reports\ReportViewer;
use QuickerFaster\UILibrary\Http\Livewire\Settings\SettingsPanel;


use Illuminate\Support\Facades\Blade;
use QuickerFaster\UILibrary\Services\Config\ModelConfigRepository;
use QuickerFaster\UILibrary\Services\Settings\SettingsManager;


class UILibraryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind ImportProcessor if needed
        $this->app->singleton(ImportProcessor::class);
        $this->registerSettingsResolver();
        $this->registerPublicFolder();

        $this->app->singleton(ModelConfigRepository::class);

    }




    public function boot()
    {

        $this->registerCommands();
        $this->registerLivewireComponents();
        $this->registerPublishables();
        $this->registerFortifyViews();
        $this->registerSocialiteProviders();


        // Register view path
        $viewPath = __DIR__ . '/../Resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'qf');
            // Use aliasComponent to map the namespace view to a tag
            \Blade::component('qf::layouts.app', 'layout');
            \Blade::component('qf::layouts.guest', 'guest-layout');
            \Blade::component('qf::components.breadcrumb', 'breadcrumb');

        }

        \Blade::componentNamespace('QuickerFaster\\UILibrary\\Components', 'qf');

        // Register a settings blade dirctive
        // Usage: @setting('date_format', 'Y-m-d')
        Blade::directive('setting', function ($expression) {
            return "<?php echo app(\\QuickerFaster\\UILibrary\\Services\\Settings\\SettingsManager::class)->get({$expression}); ?>";
        });




        // Merge package config with application's configcomp
        $configPath = __DIR__ . '/../Config/quicker-faster-ui.php';
        $this->mergeConfigFrom($configPath, 'quicker-faster-ui');

        // Translations
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'qf');

    }


    private function registerSettingsResolver()
    {
        $this->app->singleton(SettingsManager::class, function ($app) {
            $manager = new SettingsManager();

            // Priority 1: User preferences
            $manager->addResolver('user', function ($key) {
                return auth()->user()?->getSetting($key);
            });

            // Priority 2: Context (e.g., module or organization)
            /*$manager->addResolver('context', function ($key) {
                $moduleSlug = request()->route('module') ?? session('active_module');
                if ($moduleSlug) {
                    $module = \App\Models\Module::where('slug', $moduleSlug)->first();
                    return $module?->getSetting($key);
                }
                // Or organization
                if (session('organization_id')) {
                    $org = \App\Models\Organization::find(session('organization_id'));
                    return $org?->getSetting($key);
                }
                return null;
            });

            // Priority 3: System defaults
            $manager->addResolver('system', function ($key) {
                $system = \App\Models\System::find(1);
                return $system?->getSetting($key);
            });*/

            return $manager;
        });
    }


    private function registerPublicFolder()
    {
        $this->app->bind('path.public', function () {
            // If we are on the server, the public folder is likely '../public_html' 
            // relative to the app core.
            $sharedHostingPath = base_path('../public_html');

            if (is_dir($sharedHostingPath)) {
                return $sharedHostingPath;
            }

            // Fallback for local development
            return base_path('public');
        });
    }


    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                QuickerFasterInstallUI::class,
            ]);
        }
    }


    private function registerLivewireComponents()
    {
        Livewire::component('qf.data-table', DataTable::class);
        Livewire::component('qf.data-table-form', DataTableForm::class);
        Livewire::component('qf.form-modal', FormModal::class);

        Livewire::component('qf.data-table-detail', DataTableDetail::class);
        Livewire::component('qf.detail-modal', DetailModal::class);
        Livewire::component('qf.alert-modal', AlertModal::class);

        // Files Import
        Livewire::component('qf.import-modal', ImportModal::class);
        Livewire::component('qf.import-form', ImportForm::class);
        Livewire::component('qf.filter-panel', FilterPanel::class);

        // File Export
        Livewire::component('qf.export-modal', ExportModal::class);
        Livewire::component('qf.export-progress', ExportProgress::class);

        // Wizard
        Livewire::component('qf.wizard', Wizard::class);
        Livewire::component('qf.setup-wizard', SetupWizard::class);
        Livewire::component('qf.setup-checklist', SetupChecklist::class);
        Livewire::component('qf.wizard-form', WizardForm::class);


        Livewire::component('qf.dashboard', Dashboard::class);

        // Layout
        Livewire::component('qf.top-nav', TopNav::class);
        Livewire::component('qf.sidebar', Sidebar::class);
        Livewire::component('qf.bottom-bar', BottomBar::class);

        Livewire::component('qf.navigation-layout', NavigationLayout::class);
        Livewire::component('qf.horizontal-context-menu', HorizontalContextMenu::class);

        // Access control
        Livewire::component('qf.access-control-manager', AccessControlManager::class);
        Livewire::component('qf.module-selector', ModuleSelector::class);

        // Buttons
        Livewire::component('qf.toggle-button', ToggleButton::class);
        Livewire::component('qf.toggle-button-group', ToggleButtonGroup::class);

        //Custom
        Livewire::component('qf.employee-detail', EmployeeDetail::class);

        Livewire::component('qf.employee-detail', EmployeeDetail::class);
        Livewire::component('qf.menu-renderer', MenuRenderer::class);


        // Document
        Livewire::component('qf.document-preview-modal', DocumentPreviewModal::class);
        Livewire::component('qf.document-preview', DocumentPreview::class);
        Livewire::component('qf.crop-image-modal', CropImageModal::class);


        // Reports 
        Livewire::component('qf.report-index', ReportIndex::class);
        Livewire::component('qf.report-viewer', ReportViewer::class);
        Livewire::component('qf.report-builder', ReportBuilder::class);


        // Settings 
        Livewire::component('qf.settings-panel', SettingsPanel::class);

        // Drawer
        Livewire::component('qf.drawer', Drawer::class);



    }



    private function registerPublishables()
    {
        // Publish views with a custom tag
        $this->publishes([
            __DIR__ . '/../Resources/views' => resource_path('views/vendor/quicker-faster-ui'),
        ], 'quicker-faster-ui-views');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/Config/quicker-faster-ui.php' => config_path('quicker-faster-ui.php'),
        ], 'quicker-faster-ui-config');
    }



    private function registerSocialiteProviders()
    {
        // Inject socialite package's Google config into the global 'services' array
        // List of providers you support
        $providers = ['google', 'github'];
        foreach ($providers as $provider) {
            if (config("quicker-faster-ui.socialite.providers.{$provider}.enabled")) {
                config([
                    "services.{$provider}" => [
                        'client_id' => env(strtoupper($provider) . '_CLIENT_ID'),
                        'client_secret' => env(strtoupper($provider) . '_CLIENT_SECRET'),
                        // Set redirect to env value or empty string. The empty string will be overridden in the controller.
                        'redirect' => env(strtoupper($provider) . '_REDIRECT_URI', ''),
                    ],
                ]);
            }
        }
    }

    private function registerFortifyViews()
    {

        Fortify::loginView(function () {
            return view('qf::auth.login');
        });

        Fortify::registerView(function () {
            return view('qf::auth.register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('qf::auth.forgot-password');
        });

        Fortify::resetPasswordView(function () {
            return view('qf::auth.reset-password');
        });
    }






}
