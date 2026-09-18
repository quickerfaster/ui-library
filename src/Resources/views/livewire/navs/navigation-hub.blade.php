<div>
    @if ($isOpen)
    <div class="d-md-none"
         style="position: fixed; inset: 0; z-index: 1060;"
         @click.self="$wire.close()">

    {{-- Backdrop --}}
    <div class="position-absolute bg-dark opacity-25" style="inset: 0;"
         @click="$wire.close()"></div>

    {{-- Sheet --}}
    <div class="position-absolute bottom-0 start-0 end-0 bg-white shadow-lg"
         style="max-height: 75vh; border-radius: 16px 16px 0 0; overflow-y: auto; padding-bottom: env(safe-area-inset-bottom);">

        {{-- Drag handle --}}
        <div class="d-flex justify-content-center pt-2 pb-1">
            <div class="bg-secondary rounded-pill opacity-50" style="width: 36px; height: 4px;"></div>
        </div>

        {{-- Header --}}
        <div class="d-flex align-items-center px-3 py-2 border-bottom">
            <h6 class="mb-0 fw-bold flex-grow-1">Navigation</h6>
            <button class="btn btn-sm btn-light rounded-circle p-0 d-flex align-items-center justify-content-center"
                    @click="$wire.close()" style="width: 32px; height: 32px; min-width: 32px;">
                <i class="fas fa-times opacity-50"></i>
            </button>
        </div>

        {{-- Module Section --}}
        @if ($this->showModuleSection)
        <div class="px-3 pt-3" id="hub-section-module">
            <small class="text-uppercase text-muted fw-bold d-block mb-2">Switch Module</small>
            <div class="list-group list-group-flush">
                @foreach ($modules as $module)
                    @php
                        $isActive = ($module['key'] ?? '') === $currentModule;
                    @endphp
                    <button wire:click="switchModule('{{ $module['key'] }}')"
                            class="list-group-item list-group-item-action border-0 d-flex align-items-center px-3 py-3 rounded-2
                                   {{ $isActive ? 'fw-bold' : '' }}"
                            @if ($isActive)
                            style="background: rgba(13, 110, 253, 0.15); border-left: 3px solid #0d6efd; color: #212529;"
                            @endif>
                        @if (!empty($module['icon']))
                            <i class="{{ $module['icon'] }} me-3 {{ $isActive ? 'text-primary' : 'text-muted' }}" style="width: 20px; text-align: center;"></i>
                        @endif
                        <span class="flex-grow-1">{{ $module['label'] ?? $module['key'] }}</span>
                        @if ($isActive)
                            <i class="fas fa-check text-primary"></i>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Company Section --}}
        @if ($this->showCompanySection)
        <div class="px-3 pt-3 pb-3" id="hub-section-company">
            <small class="text-uppercase text-muted fw-bold d-block mb-2">Switch Company</small>
            <div class="list-group list-group-flush">
                {{-- All Companies (admin only) --}}
                @if ($canAccessAllCompanies)
                    @php $isAllCompanies = $currentCompanyId === 0; @endphp
                    <button wire:click="switchCompany(0)"
                            class="list-group-item list-group-item-action border-0 d-flex align-items-center px-3 py-3 rounded-2
                                   {{ $isAllCompanies ? 'fw-bold' : '' }}"
                            @if ($isAllCompanies)
                            style="background: rgba(13, 202, 240, 0.15); border-left: 3px solid #0dcaf0; color: #212529;"
                            @endif>
                        <i class="fas fa-globe me-3 {{ $isAllCompanies ? 'text-info' : 'text-muted' }}" style="width: 20px; text-align: center;"></i>
                        <span class="flex-grow-1">All Companies</span>
                        @if ($isAllCompanies)
                            <i class="fas fa-check text-info"></i>
                        @endif
                    </button>
                    <hr class="my-2">
                @endif

                @foreach ($companies as $company)
                    @php $isActive = $currentCompanyId === ($company->id ?? $company['id'] ?? null); @endphp
                    <button wire:click="switchCompany({{ $company->id ?? $company['id'] }})"
                            class="list-group-item list-group-item-action border-0 d-flex align-items-center px-3 py-3 rounded-2
                                   {{ $isActive ? 'fw-bold' : '' }}"
                            @if ($isActive)
                            style="background: rgba(13, 110, 253, 0.15); border-left: 3px solid #0d6efd; color: #212529;"
                            @endif>
                        <i class="fas fa-building me-3 {{ $isActive ? 'text-primary' : 'text-muted' }}" style="width: 20px; text-align: center;"></i>
                        <span class="flex-grow-1">{{ $company->name ?? $company['name'] }}</span>
                        @if ($isActive)
                            <i class="fas fa-check text-primary"></i>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Empty state (single module, single company) --}}
        @if (!$this->showModuleSection && !$this->showCompanySection)
        <div class="text-center text-muted py-5">
            <i class="fas fa-compass d-block mb-2 fs-2 opacity-25"></i>
            <small>No other modules or companies available</small>
        </div>
        @endif

    </div>
    @endif
</div>