@props(['data'])
<div class="card border-0 shadow-lg rounded-4 h-100 bg-gradient-dark text-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">
            {{-- Left: Photo + Name --}}
            <div class="text-center flex-shrink-0">
                @if($data['photo_url'])
                    <img src="{{ $data['photo_url'] }}" class="rounded-circle border border-2 border-white" width="100" height="100" alt="Photo">
                @else
                    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:100px;height:100px;">
                        <i class="fas fa-user fa-3x text-white"></i>
                    </div>
                @endif
                <h4 class="mt-3 mb-1 text-white">{{ $data['full_name'] }}</h4>
                <span class="text-white opacity-8 small">{{ $data['record_number'] }}</span>
                <p class="text-white opacity-8 small mb-0">{{ $data['title'] ?? '' }}</p>
            </div>

            {{-- Divider: hidden on mobile, visible on desktop --}}
            <div class="vr d-none d-md-block bg-white opacity-3"></div>
            <hr class="d-md-none w-100 my-0 border-white opacity-3">

            {{-- Right: Detail Fields --}}
            <div class="flex-grow-1 w-100">
                @foreach($data['fields'] as $field)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white opacity-8">{{ $field['label'] }}:</span>
                        <span class="fw-medium text-white">{{ $field['value'] }}</span>
                    </div>
                @endforeach
                @if(!empty($data['actions']))
                    <div class="mt-3 d-flex justify-content-start gap-2">
                        @foreach($data['actions'] as $action)
                            <button wire:click="{{ $action['event'] }}({{ json_encode($action['params'] ?? []) }})" class="btn btn-sm btn-outline-light">
                                <i class="{{ $action['icon'] ?? 'fas fa-edit' }} me-1"></i> {{ $action['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>