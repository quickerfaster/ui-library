<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;


use Illuminate\Support\Collection;

class TopNav extends Component
{
    public array $items = [];
    public string $activeContext;
    public string $moduleName;
    public int $maxDesktop = 5;
    public int $maxMobile = 3;

    public array $leftShared = [];
    public array $rightShared = [];

    public function mount(
        array $items,
        string $activeContext,
        string $moduleName,
        array $leftShared = [],
        array $rightShared = []
    ): void {
        $this->items = $items;
        $this->activeContext = $activeContext;
        $this->moduleName = $moduleName;
        $this->leftShared = $leftShared;
        $this->rightShared = $rightShared;
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