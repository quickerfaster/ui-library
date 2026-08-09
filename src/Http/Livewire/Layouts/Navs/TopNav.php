<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;

class TopNav extends Component
{
    public array $items = [];
    public string $activeContext;
    public string $moduleName;
    public int $maxDesktop = 5;
    public int $maxMobile = 3;

    public array $leftShared = [];
    public array $rightShared = [];

    /** @var \Illuminate\Support\Collection|null */
    public $companies = null;

    public ?int $currentCompanyId = null;
    public ?string $currentCompanyName = null;

    protected CompanyProvider $companyProvider;

    public function mount(
        array $items,
        string $activeContext,
        string $moduleName,
        array $leftShared = [],
        array $rightShared = [],
        CompanyProvider $companyProvider = null
    ): void {
        $this->items = $items;
        $this->activeContext = $activeContext;
        $this->moduleName = $moduleName;
        $this->leftShared = $leftShared;
        $this->rightShared = $rightShared;

        $this->companyProvider = $companyProvider ?? app(CompanyProvider::class);

        $this->loadCompanies();
    }

    /**
     * Load all companies and set the current selection from session.
     */
    protected function loadCompanies(): void
    {
        if (!auth()->check()) {
            $this->companies = collect();
            return;
        }

        $user = auth()->user();

        // Check if company switcher is enabled in config
        if (!config('ui-library.navigation.show_company_switcher', false)) {
            $this->companies = collect();
            return;
        }

        $config = config('quicker-faster-ui.multitenancy', []);
        $switcherRoles = $config['switcher_roles'] ?? ['super_admin'];
        $isWildcard = ($switcherRoles === '*' || $switcherRoles === ['*']);

        if (!$isWildcard && !$user->hasAnyRole((array) $switcherRoles)) {
            $this->companies = collect();
            return;
        }

        $this->companies = $this->companyProvider->getCompanies($user);

        // Determine current company from session
        $sessionCompanyId = Session::get('current_company_id');
        $providerCompanyId = $this->companyProvider->getCurrentCompanyId($user);

        if ($sessionCompanyId === 0) {
            $this->currentCompanyId = 0;
        } elseif ($sessionCompanyId && $this->companies->pluck('id')->contains($sessionCompanyId)) {
            $this->currentCompanyId = $sessionCompanyId;
        } elseif ($providerCompanyId) {
            $this->currentCompanyId = $providerCompanyId;
            Session::put('current_company_id', $providerCompanyId);
        } elseif ($this->companies->isNotEmpty()) {
            $this->currentCompanyId = $this->companies->first()->id;
            Session::put('current_company_id', $this->currentCompanyId);
        }

        $this->updateCurrentCompanyName();
    }

    /**
     * Switch the active company and persist to session.
     */
    public function switchCompany(int $companyId): void
    {
        // Allow 0 for "All Companies"; otherwise validate company exists
        if ($companyId !== 0 && (!$this->companies || !$this->companies->pluck('id')->contains($companyId))) {
            return;
        }

        $this->currentCompanyId = $companyId;
        Session::put('current_company_id', $companyId);
        $this->updateCurrentCompanyName();

        // Dispatch event so other components can react to company change
        $this->dispatch('companySwitched', companyId: $companyId);

        // Redirect to dashboard to refresh the page context
        $this->redirect(url('/' . strtolower($this->moduleName) . '/dashboard'));
    }

    /**
     * Update the display name for the currently selected company.
     */
    protected function updateCurrentCompanyName(): void
    {
        if ($this->currentCompanyId === 0) {
            $this->currentCompanyName = 'All Companies';
        } elseif ($this->currentCompanyId && $this->companies) {
            $company = $this->companies->firstWhere('id', $this->currentCompanyId);
            $this->currentCompanyName = $company ? $company->name : 'Select Company';
        } else {
            $this->currentCompanyName = 'Select Company';
        }
    }

    public function getOverflowDesktopProperty(): Collection
    {
        return collect($this->items)->slice($this->maxDesktop);
    }


    public function handleOverflowSelect($value)
    {
        // $value is the selected item's key (the array key from $items)
        $this->dispatch('contextSelected', $value);
        // Optionally navigate to the item's default route
        $item = $this->items[$value] ?? null;
        if ($item && isset($item['route'])) {
            $isNamedRoute = !str_contains($item['route'], '/');
            $url = $isNamedRoute ? route($item['route']) : url($item['route']);
            $this->redirect($url);
        } else {
            // Fallback to a constructed URL
            $this->redirect(url("/{$this->moduleName}/" . Str::kebab($value)));
        }
    }

    public function getOverflowMobileProperty(): Collection
    {
        return collect($this->items)->slice($this->maxMobile);
    }

    public function selectContext(string $context): void
    {
        $this->dispatch('contextSelected', $context);
    }

    /**
     * Open the background jobs drawer.
     */

    public function openBackgroundJobsDrawer(): void
    {
        $this->dispatch('openDrawer', 'qf.background-jobs-panel', [], 'Background Jobs');
    }

    public function logout()
    {
        // 1. Log the user out using the Auth facade
        auth()->logout();

        // 2. Invalidate the user's session
        session()->invalidate();

        // 3. Regenerate the CSRF token for security
        session()->regenerateToken();

        // 4. Redirect to the login page or homepage (this is a GET)
        return redirect('/login');
    }


    public function render()
    {
        return view('qf::livewire.navs.top-nav');
    }
}