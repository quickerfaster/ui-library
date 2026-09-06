# QuickerFaster UI Library — Extension & Customization Guide

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-17

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`06-navigation-system.md`](./06-navigation-system.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`14-integration-map.md`](./14-integration-map.md) · [`26-module-auto-discovery.md`](./26-module-auto-discovery.md)

> **Consuming-app developers**: For recipes on creating business modules, configuring navigation, using auto-discovery, and implementing `HasWorkflow`, see:
> - [../consuming-app/module-structure.md](../consuming-app/module-structure.md) — module creation, navigation config, auto-discovery conventions & opt-outs
> - [../consuming-app/contracts.md](../consuming-app/contracts.md) — `HasWorkflow` trait and `Workflowable` contract cookbook
> - [../consuming-app/getting-started.md](../consuming-app/getting-started.md) — `ui-library:discover` command usage

---

## 7. Extension & Customization Guide

Step-by-step recipes for extending the library itself (new FieldTypes, widget types, component overrides).

### 7.1 Recipe: Add a New FieldType

**Step 1**: Create the field type class in [`src/Components/FieldTypes/`](../../src/Components/FieldTypes/):

```php
// src/Components/FieldTypes/CurrencyField.php
namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;

class CurrencyField implements FieldType
{
    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    public function renderForm($value = null): string
    {
        return view('qf::components.fields.currency', [
            'name' => $this->name,
            'definition' => $this->definition,
            'value' => $value,
        ])->render();
    }

    public function renderTable($value, $record): string
    {
        $symbol = $this->definition['currency_symbol'] ?? '$';
        return $symbol . number_format($value, 2);
    }

    public function renderDetail($value, $record): string
    {
        return $this->renderTable($value, $record);
    }

    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        return view('qf::components.fields.inline-editor.text', [
            'name' => $this->name,
            'value' => $value,
            'extra' => $extra,
        ])->render();
    }

    public function getValidationRules(): array
    {
        return [$this->name => $this->definition['validation'] ?? 'numeric'];
    }

    public function getOptions(): array { return []; }
    public function isRelationship(): bool { return false; }
    public function getRelationshipConfig(): ?array { return null; }
    public function getLabel(): string { return $this->definition['label'] ?? $this->name; }
    public function getName(): string { return $this->name; }
}
```

> **Note**: the full [`FieldType`](../../src/Contracts/FieldTypes/FieldType.php) contract (10 required methods) is documented in [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md).

**Step 2**: Register in [`FieldFactory`](../../src/Factories/FieldTypes/FieldFactory.php:25):

```php
protected array $map = [
    // ... existing mappings
    'currency' => CurrencyField::class,
];
```

**Step 3**: Create the Blade view at `src/Resources/views/components/fields/currency.blade.php`.

**Step 4**: Use in any module config:

```php
'fieldDefinitions' => [
    'amount' => [
        'field_type' => 'currency',
        'label' => 'Amount',
        'currency_symbol' => '₦',
        'validation' => 'required|numeric|min:0',
    ],
],
```

### 7.3 Recipe: Override a Library Component

**Step 1**: Create a module-specific component extending the library version:

```php
// app/Modules/Billing/Http/Livewire/DataTables/InvoiceTable.php
namespace App\Modules\Billing\Http\Livewire\DataTables;

use QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTable;

class InvoiceTable extends DataTable
{
    // Override methods as needed
    protected function getCustomActions(): array
    {
        return array_merge(parent::getCustomActions(), [
            'mark_paid' => 'Mark as Paid',
        ]);
    }
}
```

**Step 2**: Register in the consuming app's `AppServiceProvider`:

```php
Livewire::component('billing.invoice-table', InvoiceTable::class);
```

**Step 3**: Use the overridden component in views.

### 7.4 Recipe: Add a New Widget Type

**Step 1**: Create a widget processor in [`src/Widgets/`](../../src/Widgets/):

```php
// src/Widgets/GaugeWidgetProcessor.php
namespace QuickerFaster\UILibrary\Widgets;

class GaugeWidgetProcessor
{
    public function process(array $definition): array
    {
        return [
            'type' => 'gauge',
            'title' => $definition['title'] ?? 'Gauge',
            'value' => $this->calculateValue($definition),
            'min' => $definition['min'] ?? 0,
            'max' => $definition['max'] ?? 100,
            'width' => $definition['width'] ?? 3,
        ];
    }

    protected function calculateValue(array $definition): float
    {
        // Custom calculation logic
        return 75.5;
    }
}
```

**Step 2**: Register in [`WidgetProcessor`](../../src/Services/Widgets/WidgetProcessor.php:27):

```php
protected array $map = [
    // ... existing mappings
    'gauge' => GaugeWidgetProcessor::class,
];
```

**Step 3**: Create the Blade view at `src/Resources/views/widgets/gauge.blade.php`.

**Step 4**: Use in dashboard configs:

```php
'widgets' => [
    ['type' => 'gauge', 'title' => 'Revenue Target', 'min' => 0, 'max' => 100000],
],
```

---

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`06-navigation-system.md`](./06-navigation-system.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`14-integration-map.md`](./14-integration-map.md) · [`26-module-auto-discovery.md`](./26-module-auto-discovery.md)
