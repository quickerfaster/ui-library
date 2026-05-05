<div class="p-3 bg-light">
    @if($items->count())
        <table class="table table-sm table-borderless">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-end">{{ $this->currencySymbol }}{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted mb-0">No items found for this payslip.</p>
    @endif
</div>