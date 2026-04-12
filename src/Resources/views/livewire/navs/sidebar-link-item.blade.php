
<li class="nav-item">
    <a href="#"
        class="nav-link {{ $anchorClasses?? '' }} {{ $item['key'] === $activeContext ? 'active fw-semibold' : '' }}"
        wire:click.prevent="selectContext('{{ $item['key'] }}')" target="{{$target??''}}">
        <i class="fa {{ $item['icon'] }} me-1 {{ $iconClasses }}" aria-hidden="true"></i>
        <span>{{ $item['label'] }}</span>
    </a>
</li>




