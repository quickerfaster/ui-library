<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;
use Illuminate\Support\Collection;

/**
 * Mobile Navigation Hub — combined module and company switching sheet.
 *
 * Self-sufficient: loads modules from config, companies from session/provider,
 * and current context from session. No data dependencies on parent components.
 */
class NavigationHub extends Component
{
    public bool $isOpen = false;
    public string $currentModule = '';
    public string $currentModuleLabel = '';
    public array $modules = [];
    public ?int $currentCompanyId = null;
    public string $currentCompanyName = '';
    public $companies = [];
    public bool $canAccessAllCompanies = false;
    public bool $multiCompanyEnabled = false;
    public string $scrollTo = '';

    protected $listeners = [
        'openNavigationHub' => 'open',
        'closeNavigationHub' => 'close',
    ];

    public function mount(): void
    {
        $this->loadModules();
        $this->loadCompanyContext();
    }

    /**
     * Load accessible modules from config, matching TopNav's logic.
     */
    protected function loadModules(): void
    {
        $allModules = config('ui-library.modules', []);
        $user = auth()->user();

        $this->modules = collect($allModules)
            ->filter(fn($cfg) => ($cfg['enabled'] ?? false) && ($cfg['user_facing'] ?? false))
            ->filter(function ($cfg) use ($user) {
                $roles = $cfg['roles'] ?? '*';
                if ($roles === '*' || $roles === ['*']) {
                    return true;
                }
                return $user && $user->hasAnyRole((array) $roles);
            })
            ->map(fn($cfg, $key) => [
                'key'   => $key,
                'label' => $cfg['label'] ?? $key,
                'icon'  => $cfg['icon'] ?? 'fa-cube',
                'route' => $cfg['route'] ?? null,
                'url'   => $cfg['url'] ?? null,
            ])
            ->sortBy('label')
            ->values()
            ->toArray();

        $this->currentModule = session('active_module', 'admin');
        $activeCfg = $allModules[$this->currentModule] ?? null;
        $this->currentModuleLabel = $activeCfg['label'] ?? $this->currentModule;
    }

    /**
     * Load company context from session and config.
     */
    protected function loadCompanyContext(): void
    {
        $this->multiCompanyEnabled = (bool) config('ui-library.features.multi_company', false);

        if (!$this->multiCompanyEnabled) {
            return;
        }

        $sessionKey = config('ui-library.tenancy.session_key', 'current_company_id');
        $this->currentCompanyId = session($sessionKey);

        // Resolve companies via CompanyProvider contract
        try {
            $provider = app(\QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider::class);
            $this->companies = $provider->getCompanies();
            $this->currentCompanyName = $this->companies
                ->firstWhere('id', $this->currentCompanyId)?->name ?? '';
        } catch (\Exception $e) {
            $this->companies = collect();
        }

        // Check all-companies access
        $allCompaniesRoles = config('ui-library.all_companies_roles', ['super_admin', 'admin', 'company_admin']);
        $user = auth()->user();
        $this->canAccessAllCompanies = $user && $user->hasAnyRole($allCompaniesRoles);
    }

    public function open(array $payload = []): void
    {
        // Refresh data on open (module/company may have changed)
        $this->loadModules();
        $this->loadCompanyContext();
        $this->scrollTo = $payload['scrollTo'] ?? '';
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function switchModule(string $moduleKey): void
    {
        $module = collect($this->modules)->firstWhere('key', $moduleKey);
        if (!$module) {
            return;
        }

        session(['active_module' => $moduleKey]);
        $this->isOpen = false;

        $url = $module['route'] ?? $module['url'] ?? '/';
        $this->redirect($url, navigate: true);
    }

    public function switchCompany(int $companyId): void
    {
        $sessionKey = config('ui-library.tenancy.session_key', 'current_company_id');
        session([$sessionKey => $companyId]);
        $this->isOpen = false;

        $this->redirect(request()->url(), navigate: true);
    }

    public function getShowModuleSectionProperty(): bool
    {
        return count($this->modules) > 1;
    }

    public function getShowCompanySectionProperty(): bool
    {
        return $this->multiCompanyEnabled && $this->companies->isNotEmpty();
    }

    public function render()
    {
        return view('qf::livewire.navs.navigation-hub');
    }
}