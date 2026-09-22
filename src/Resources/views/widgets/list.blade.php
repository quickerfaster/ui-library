@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    @foreach($data['columns'] as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                    @if(!empty($data['row_actions']))
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($data['items'] as $item)
                    <tr>
                        @foreach($data['columns'] as $column)
                            <td>{!! $item[$column['label']] ?? '' !!}</td>
                        @endforeach
                        @if(!empty($data['row_actions']))
                            <td class="text-center" style="width: 1%; white-space: nowrap;">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @foreach($item['actions'] as $action)
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    @click.prevent='Livewire.dispatch("{{ $action['event'] ?? '' }}", @json($action['params'] ?? []))'>
                                                    @if(!empty($action['icon']))
                                                        <i class="{{ $action['icon'] }} me-2"></i>
                                                    @endif
                                                    {{ $action['label'] ?? '' }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($data['columns']) + (!empty($data['row_actions']) ? 1 : 0) }}" class="text-center text-muted">
                            No records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data['showViewAll'] && $data['viewAllLink'])
        <div class="mt-3 text-end">
            <a href="{{ $data['viewAllLink'] }}" target="{{ $data['viewAllLinkTarget'] ?? '_self' }}" class="btn btn-sm btn-link" @if(($data['viewAllLinkTarget'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif>View All</a>
        </div>
    @endif
</div>

@php
    $body = ob_get_clean();
@endphp

@include('qf::widgets.partials.card', [
    'data' => $data,
    'body' => $body,
    'header' => [
        'title' => $data['title'],
        'icon' => $data['icon'] ?? null,
        'color' => $data['color'],
        'description' => $data['description'] ?? null,
    ],
])
