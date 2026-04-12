@php
    $onboarding = auth()?->user()?->onboarding();
@endphp

@if($onboarding?->inProgress())


    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>🚀 Get Started with Your Workspace</span>
            <button class="btn btn-sm btn-light" onclick="this.closest('.card').remove()">Hide</button>
            {{-- Simple hide; for persistence you'd use Livewire or a session flag --}}
        </div>
        <div class="card-body">
            <div class="progress mb-3">
                <div class="progress-bar" style="width: {{ $onboarding->percentageCompleted() }}%">
                    {{ $onboarding->percentageCompleted() }}%
                </div>
            </div>
            <div class="list-group">
                @foreach($onboarding->steps as $step)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            @if($step->complete())
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <s>{{ $step->title }}</s>
                            @else
                                <i class="fas fa-circle-notch text-muted me-2"></i>
                                {{ $step->title }}
                            @endif
                        </span>
                        <a href="{{ $step->link }}" class="btn btn-sm btn-primary">
                            {{ $step->cta }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif