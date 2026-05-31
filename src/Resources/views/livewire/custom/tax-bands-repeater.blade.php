<div>
    <table class="table table-sm table-borderless">
        <thead>
            <tr>
                <th>{{ $limitLabel }}</th>
                <th>{{ $rateLabel }}</th>
                <th style="width: 50px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $row)
                <tr wire:key="row-{{ $index }}">
                    <td>
                        <input type="number" step="any" class="form-control form-control-sm"
                               wire:model="rows.{{ $index }}.limit" placeholder="e.g., 80000">
                    </td>
                    <td>
                        <input type="number" step="any" class="form-control form-control-sm"
                               wire:model="rows.{{ $index }}.rate" placeholder="e.g., 15">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeRow({{ $index }})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <button type="button" class="btn btn-sm btn-secondary" wire:click="addRow">
        <i class="fas fa-plus"></i> Add Bracket
    </button>
</div>