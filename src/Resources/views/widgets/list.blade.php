@props(['data'])

@php
    ob_start();
@endphp

<div class="card-body p-3 p-lg-4">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    @foreach($data['columns'] as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($data['items'] as $item)
                    <tr>
                        @foreach($data['columns'] as $column)
                            <td>{{ $item[$column['label']] ?? '' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($data['columns']) }}" class="text-center text-muted">
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
