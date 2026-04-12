<div>
    @if ($isCard)
    <!-- Card Layout -->
    <div class="{{$hasCorners? 'card': ''}}  w-100">
        <div class="card-body ">
            <div class="row align-items-center">

                <div class="col">
                    @include('qf::livewire.buttons.title-icon')
                </div>

                <div class="col-auto">
                    <div class="form-check form-switch mt-3" wire:key="button-toggle-{{ $isOn }}">
                        <input
                            type="checkbox"
                            class="form-check-input  bg-{{ $isOn ? $onStateColor : $offStateColor }} border border-bg-{{ $isOn ? $onStateColor : $offStateColor }} "
                            wire:click="toggle"
                            wire:loading.attr="disabled"

                            @checked($isOn)
                            id="toggle-{{ $recordId }}"
                        >
                    </div>
                </div>

            </div>
        </div>
    </div>
@else
    <!-- Standalone Layout -->
    <div class="d-flex flex-column align-items-center">
        @if ($labelPosition == 'top')
            @if ($showLabel)
                <span class="text-{{ $isOn ? $onStateColor : $offStateColor }} ">
                    {{ $isOn ? 'On' : 'Off' }}
                </span>
            @endif
        @endif

        <div class="d-flex align-items-center {{ $labelPosition == 'left' || $labelPosition == 'right' ? 'justify-content-between w-100' : '' }}">
            @if ($labelPosition == 'left')
                @if ($showLabel)
                    <span class="text-{{ $isOn ? $onStateColor : $offStateColor }}  me-2">
                        {{ $isOn ? 'On' : 'Off' }}
                    </span>
                @endif
            @endif

            <div class="form-check form-switch">
                <input
                    type="checkbox"
                    class="mt-3 mt-sm-0 form-check-input bg-{{ $isOn ? $onStateColor : $offStateColor }} border border-bg-{{ $isOn ? $onStateColor : $offStateColor }}"
                    wire:click="toggle"
                    wire:loading.attr="disabled"

                    @checked($isOn)
                    id="toggle-{{ $recordId }}"
                >
            </div>

            @if ($labelPosition == 'right')
                @if ($showLabel)
                    <span class="text-{{ $isOn ? $onStateColor : $offStateColor }}  ms-2">
                        {{ $isOn ? 'On' : 'Off' }}
                    </span>
                @endif
            @endif
        </div>

        @if ($labelPosition == 'bottom')
            @if ($showLabel)
                <span class="text-{{ $isOn ? $onStateColor : $offStateColor }}  mt-2">
                    {{ $isOn ? 'On' : 'Off' }}
                </span>
            @endif
        @endif
    </div>
@endif


</div>
