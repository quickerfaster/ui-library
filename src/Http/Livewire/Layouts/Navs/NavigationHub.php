<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;
use Illuminate\Support\Collection;

/**
 * Mobile Navigation Hub — combined module and company switching sheet.
 *
 * Replaces the separate module switcher dropdown and company switcher
 * dropdown with a unified half-sheet. Both sections are role-gated:
 * the module section only appears when the user has access to multiple
 * modules; the company section only appears when multi-company is
 * enabled and the user has access to multiple companies.
 */
class NavigationHub extends Component
{
    /** @var bool Whether the sheet is open. */
    public bool $isOpen = false;

    /** @var string Current module name. */
    public string $currentModule = '';

    /** @var string Current module label. */
    public string $currentModuleLabel = '';

    /** @var array Available modules (from TopNav). */
    public array $modules = [];

    /** @var int|null Current company ID. */
    public ?int $currentCompanyId = null;

    /** @var string Current company name. */
    public string $currentCompanyName = '';

    /** @var Collection|array Available companies. */
    public $companies = [];

    /** @var bool Whether the user can access all companies. */
    public bool $canAccessAllCompanies = false;

    /** @var bool Whether multi-company is enabled. */
    public bool $multiCompanyEnabled = false;

    /** @var string Section to scroll to on open ('module' or 'company'). */
    public string $scrollTo = '';

    protected $listeners = [
        'openNavigationHub' => 'open',
        'closeNavigationHub' => 'close',
    ];

    public function mount(
        string $currentModule = '',
        string $currentModuleLabel = '',
        array $modules = [],
        ?int $currentCompanyId = null,
        string $currentCompanyName = '',
        $companies = [],
        bool $canAccessAllCompanies = false,
        bool $multiCompanyEnabled = false
    ): void {
        $this->currentModule = $currentModule;
        $this->currentModuleLabel = $currentModuleLabel;
        $this->modules = $modules;
        $this->currentCompanyId = $currentCompanyId;
        $this->currentCompanyName = $currentCompanyName;
        $this->companies = $companies instanceof Collection ? $companies : collect($companies);
        $this->canAccessAllCompanies = $canAccessAllCompanies;
        $this->multiCompanyEnabled = $multiCompanyEnabled;
    }

    /**
     * Open the hub, optionally scrolling to a section.
     */
    public function open(array $payload = []): void
    {
        $this->scrollTo = $payload['scrollTo'] ?? '';
        $this->isOpen = true;
    }

    /**
     * Close the hub.
     */
    public function close(): void
    {
        $this->isOpen = false;
    }

    /**
     * Switch to a different module.
     */
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

    /**
     * Switch to a different company.
     */
    public function switchCompany(int $companyId): void
    {
        session(['current_company_id' => $companyId]);
        $this->isOpen = false;

        // Redirect to refresh the page with new company context
        $this->redirect(request()->url(), navigate: true);
    }

    /**
     * Whether to show the module section.
     */
    public function getShowModuleSectionProperty(): bool
    {
        return count($this->modules) > 1;
    }

    /**
     * Whether to show the company section.
     */
    public function getShowCompanySectionProperty(): bool
    {
        return $this->multiCompanyEnabled && $this->companies->isNotEmpty();
    }

    public function render()
    {
        return view('qf::livewire.navs.navigation-hub');
    }
}