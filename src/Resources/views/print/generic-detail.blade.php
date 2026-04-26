<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $modelName }} Details - #{{ $record->id }}</title>
    <style>
        /* Reset & base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        /* Header */
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .header h1 {
            font-size: 24pt;
            margin-bottom: 5px;
        }
        .header p {
            color: #555;
        }
        .badge {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10pt;
        }
        /* Two-column layout */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        .col {
            flex: 1;
            padding: 0 10px;
        }
        /* Cards */
        .card {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .card-title {
            font-size: 16pt;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            width: 35%;
            font-weight: bold;
            color: #555;
        }
        .info-value {
            width: 65%;
        }
        /* Print styles */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div>
                <h1>{{ $modelName }} Details</h1>
                <p>ID: #{{ $record->id }}</p>
            </div>
            <div>
                <span class="badge">Printed: {{ now()->format('M d, Y H:i') }}</span>
            </div>
        </div>

        {{-- Field Groups (two columns) --}}
        <div class="row">
            @forelse($fieldGroups as $group)
                <div class="col">
                    <div class="card">
                        @if(!empty($group['title']))
                            <div class="card-title">{{ $group['title'] }}</div>
                        @endif
                        @foreach($group['fields'] as $field)
                            @if(!in_array($field, $hiddenFields['onDetail'] ?? []))
                                @php
                                    $definition = $fieldDefinitions[$field] ?? null;
                                    if (!$definition) continue;
                                    $fieldObj = app(QuickerFaster\UILibrary\Factories\FieldTypes\FieldFactory::class)->make($field, $definition);
                                    $value = data_get($record, $field);
                                @endphp
                                <div class="info-row">
                                    <div class="info-label">{{ $fieldObj->getLabel() }}</div>
                                    <div class="info-value">
                                        @if(($definition['field_type'] ?? '') === 'morph_to_select' && isset($definition['morph_relation']))
                                            @php
                                                $related = $record->{$definition['morph_relation']};
                                                $displayValue = $related ? $related->{$definition['display_field'] ?? 'name'} : '';
                                            @endphp
                                            {{ $displayValue ?: '—' }}
                                        @else
                                            {!! $fieldObj->renderDetail($value, $record) ?? '—' !!}
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @if($loop->iteration % 2 == 0 && !$loop->last)
                    </div><div class="row">
                @endif
            @empty
                <div class="col">
                    <div class="card">
                        <div class="card-title">No field groups defined</div>
                        <p>Please define field groups in the module configuration.</p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Print button (only visible on screen) --}}
<div class="no-print" style="text-align: center; margin-top: 30px;">
    <button onclick="window.print();" class="btn btn-primary">🖨️ Print this page</button>
    <button onclick="window.close();" class="btn btn-secondary">✖ Close</button>
</div>
    </div>
</body>
</html>