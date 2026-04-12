

<div class="row">
@if ($icon)
    <div class="col-auto my-2">
        <div class="icon icon-shape bg-gradient-{{$iconBg?? ''}} shadow text-center border-radius-md">
            <i class="{{ $icon }} text-{{$iconColor?? 'primary'}}" aria-hidden="true"></i>
        </div>
    </div>
@endif

<div class="col my-2">
    @if ($title)
        <h6 class="font-weight-bolder mb-0">
            {{ $title }}
        </h6>
    @endif
    @if ($subtitle)
        <p class="text-sm mb-0  font-weight-bold">
            {!! $subtitle !!}
        </p>
    @endif
    @if ($description)
            <p class="text-sm mb-0 text-capitalize font-weight-bold text-primary">
                {!! $description !!}
            </p>
    @endif
</div>
</div>
