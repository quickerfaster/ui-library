<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f2f2f2; text-align: left; padding: 8px; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .header p { color: #666; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generated on {{ now()->format('F j, Y, g:i a') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $field => $def)
                    <th>{{ $def['label'] ?? ucfirst($field) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    @foreach($columns as $field => $def)
                        <td>
                            @php
                                $fieldFactory = new \QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory();

                                $fieldObj = $fieldFactory->make($field, $def);
                                echo $fieldObj->renderDetail($record->$field);
                            @endphp
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align: center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>