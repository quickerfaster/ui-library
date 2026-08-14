# QuickerFaster UI Library — Extension & Customization Guide

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`06-navigation-system.md`](./06-navigation-system.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`14-integration-map.md`](./14-integration-map.md)

---

## 7. Extension & Customization Guide

Step-by-step recipes for the most common extension tasks.

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
    'salary' => [
        'field_type' => 'currency',
        'label' => 'Salary',
        'currency_symbol' => '₦',
        'validation' => 'required|numeric|min:0',
    ],
],
```

### 7.2 Recipe: Create a New Business Module

**Step 1**: Create the module directory structure:

```bash
mkdir -p app/Modules/Billing/{Data,Models,Resources/views,Routes,Database/Migrations,Listeners,Services}
```

**Step 2**: Create a model config at `app/Modules/Billing/Data/invoice.php`:

```php
return [
    'model' => 'App\\Modules\\Billing\\Models\\Invoice',
    'fieldDefinitions' => [
        'invoice_number' => [
            'field_type' => 'string',
            'label' => 'Invoice #',
            'validation' => 'required|string|max:50',
            'autoGenerate' => true,
            'generator' => ['pattern' => 'INV-{YYYY}-{####}'],
        ],
        // ... more fields
    ],
    'fieldGroups' => [
        ['key' => 'details', 'label' => 'Invoice Details', 'fields' => ['invoice_number', 'client_id', 'amount', 'due_date']],
    ],
    'controls' => 'all',
];
```

**Step 3**: Create the Eloquent model at `app/Modules/Billing/Models/Invoice.php`.

**Step 4**: Create views at `app/Modules/Billing/Resources/views/`:

- `index.blade.php` — Uses `<livewire:qf.data-table config-key="billing.invoice" />`
- `dashboard.blade.php` — Module dashboard

**Step 5**: Add routes at `app/Modules/Billing/Routes/web.php` (optional; catch-all handles basic view rendering).

**Step 6**: Add navigation at `app/Modules/Billing/Config/navigation.php` (optional).

The module is now auto-discovered. No service provider registration needed. See [`03-module-pattern.md`](./03-module-pattern.md) for the full registration protocol.

### 7.3 Recipe: Override a Library Component

**Step 1**: Create a module-specific component extending the library version:

```php
// app/Modules/Hr/Http/Livewire/DataTables/EmployeeTable.php
namespace App\Modules\Hr\Http\Livewire\DataTables;

use QuickerFaster\UILibrary\Http\Livewire\DataTables\DataTable;

class EmployeeTable extends DataTable
{
    // Override methods as needed
    protected function getCustomActions(): array
    {
        return array_merge(parent::getCustomActions(), [
            'send_onboarding' => 'Send Onboarding Email',
        ]);
    }
}
```

**Step 2**: Register in the consuming app's `AppServiceProvider`:

```php
Livewire::component('hr.employee-table', EmployeeTable::class);
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

### 7.5 NavigationLayout Extension Guide

To extend the shared layout:

1. **Add navigation items** — Create `app/Modules/{Module}/Config/navigation.php`:

```php
return [
    'items' => [
        ['key' => 'invoices', 'label' => 'Invoices', 'route' => 'billing.invoices', 'icon' => 'fa-file-invoice'],
    ],
    'contexts' => [
        'billing' => [
            'label' => 'Billing',
            'items' => ['invoices', 'payments', 'subscriptions'],
        ],
    ],
];
```

2. **Permission-based visibility** — The [`NavigationFilter`](../../src/Traits/NavigationFilter.php) trait filters items based on Spatie permissions.

3. **Default nav items** — [`HasNavItems`](../../src/Traits/HasNavItems.php:7) provides: dashboard, profile, account, help, settings.

4. **Sidebar state** — Controlled via `sidebar.initial_state` in navigation config.

> **Cross-link**: The distilled navigation config schema (including item keys, `sidebar` grouping keys `section_label`/`collapsible`/`expanded_default`, and the Phases 4.3–4.5 navigation architecture) is in [`06-navigation-system.md`](./06-navigation-system.md). The library-level `config('ui-library.navigation')` keys are documented canonically in [`10-settings-and-config.md`](./10-settings-and-config.md).

---

**Related files**: [`00-index.md`](./00-index.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`06-navigation-system.md`](./06-navigation-system.md) · [`07-component-catalog.md`](./07-component-catalog.md) · [`08-contracts-and-interfaces.md`](./08-contracts-and-interfaces.md) · [`14-integration-map.md`](./14-integration-map.md)
