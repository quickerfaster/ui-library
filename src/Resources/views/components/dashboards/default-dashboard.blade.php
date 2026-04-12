

<div class="bg-gray-100 rounded rounded-3 px-4 pb-4 pt-3">
    <div class="d-flex justify-content-between p-3">
        <div>
            <h6 class="mb-0">{{ $mainTitle ?? '' }}</h6>
            <span class="text-primary text-xs fst-italic mt-0">
                {{ $subtitle ?? '' }}
            </span>
        </div>
        <div>
            {{ $controls ?? '' }}
        </div>
    </div>

    <hr class=" mb-4" style="height: 0.01em" />

    {{$slot}}


    {{-- -- @include('core.views::widgets.spinner') --}}
    

</div>
