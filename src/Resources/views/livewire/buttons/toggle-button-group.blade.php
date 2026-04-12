
<div class="card" style="width:100%" wire:key="component-{{$componentId}}">

    <div class="card-header pb-0 px-3">
        <div class="row d-flex justify-content-between ps-3 pe-5 px-md-4" id="">
            <a class="col-11" data-bs-toggle="collapse" href="#component-{{$componentId}}" role="button" aria-expanded="false"
                aria-controls="collapseExample">


                @include('qf::livewire.buttons.title-icon', [
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'description' => $description,
                    'icon' => $icon,
                ])
            </a>
            <div class="col-1 pe-3">
                <div class="form-check form-switch">
                    <div wire:key="parent-toggle-{{$componentId}}-{{ $parentState }}">
                        <input
                        type="checkbox"
                        class="my-3 form-check-input checkbox-animated
                        bg-{{ $parentState === 'on' ? $onStateColor : ($parentState === 'off' ? $offStateColor : $mixedStateColor) }}
                        border-{{ $parentState === 'on' ? $onStateColor : ($parentState === 'off' ? $offStateColor : $mixedStateColor) }}"
                        wire:click="toggleAll"
                        @checked($parentState === 'on' || $parentState === 'mixed')
                        id="parent-toggle"
                    />
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body pt-4 p-2 p-md-4 pt-md-4 mb-2 ">
        <div class="collapse" id="component-{{$componentId}}" wire:ignore>

            <!-- Child Toggle Buttons -->
            <ul class="list-group pt-1" id="Resource">
            @foreach ($buttons as $key => $button)
                <li
                  class="list-group-item border-0 rounded rounded-3  bg-gray-100  m-2 p-0"
                >
                    <livewire:qf.toggle-button
                        :is-card="true"
                        :title="$button['title']?? ''"
                        :subtitle="$button['subtitle']?? ''"
                        :icon="$button['icon']?? ''"
                        :iconBg="$button['iconBg']?? ''"
                        :iconColor="$button['iconColor']?? ''"
                        :hasCorners="false"
                        wire:loading.attr="disabled"

                        :isOn="$button['state']?? ''"

                        :componentId="$button['componentId']?? ''"
                        :model="$button['model']?? ''"
                        :column="$button['column']?? ''"
                        :record-id="$button['recordId']?? ''"

                        :onStateValue="$button['onStateValue']?? ''"
                        :offStateValue="$button['offStateValue']?? ''"

                        :stateSyncMethod="$stateSyncMethod?? $button['stateSyncMethod']?? '' "
                        :method="$method?? $button['method']?? '' "
                        :data="$data"
                    />
                </li>
            @endforeach
            </ul>
        </div>
    </div>


    {{--@include('system.views::widgets.spinner')--}}

</div>
