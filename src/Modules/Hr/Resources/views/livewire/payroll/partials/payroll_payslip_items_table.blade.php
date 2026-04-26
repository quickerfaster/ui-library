<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="ps-3 text-uppercase fs-xs fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Item Details</th>
                <th class="text-center text-uppercase fs-xs fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Type</th>
                <th class="text-end pe-3 text-uppercase fs-xs fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">Amount</th>
            </tr>
        </thead>
        <tbody class="border-top-0">
            @foreach ($items as $item)
                @php
                    $type = $item['type'] ?? $item->type;
                    $amount = $item['amount'] ?? $item->amount;
                    $label = $item['label'] ?? $item->label;
                @endphp
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold text-dark">{{ $label }}</div>
                        @if($type === 'tax')
                            <small class="text-muted">Statutory Government Levy</small>
                        @endif
                    </td>

                    <td class="text-center">
                        @if ($type === 'earning')
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2">Earning</span>
                        @elseif($type === 'deduction')
                            <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2">Deduction</span>
                        @elseif($type === 'tax')
                            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-2">Tax</span>
                        @else
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle px-2">{{ ucfirst($type) }}</span>
                        @endif
                    </td>

                    <td class="text-end pe-3 fw-bold">
                        @if ($type === 'earning')
                            <span class="text-success">
                                + {{ $currencySymbol }}{{ number_format($amount, 2) }}
                            </span>
                        @else
                            <span class="text-danger">
                                ({{ $currencySymbol }}{{ number_format($amount, 2) }})
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
