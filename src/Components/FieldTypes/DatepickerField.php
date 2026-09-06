<?php

namespace QuickerFaster\UILibrary\Components\FieldTypes;

use QuickerFaster\UILibrary\Contracts\FieldTypes\FieldType;
use QuickerFaster\UILibrary\Services\Settings\SettingsManager;
use QuickerFaster\UILibrary\Traits\FieldTypes\HasBladeRendering;
use Illuminate\Support\Facades\DB;

class DatepickerField implements FieldType
{
    use HasBladeRendering;

    protected string $name;
    protected array $definition;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    public function renderForm($value = null): string
    {
        // This can be applied in the future
        $dateFormat = app(SettingsManager::class)->get('date_format', 'Y-m-d');

        $calendarConfig = $this->buildCalendarConfig();

        return $this->renderBlade('qf::components.fields.datepicker', [
            'field' => $this,
            'value' => $value,
            'name' => $this->name,
            'label' => $this->definition['label'] ?? ucfirst($this->name),
            'customAttributes' => $this->definition['attributes'] ?? [],
            'calendarConfig' => $calendarConfig,
        ]);
    }

    /**
     * Build the calendar enhancement configuration from the field definition.
     *
     * Supports:
     *  - disableWeekends: bool — grey out Saturdays and Sundays
     *  - highlightHolidays: bool — mark company holidays with a distinct style
     *  - showTeamAbsences: bool — show approved team leave requests as marked ranges
     *  - holidays: array — config-driven fallback when no Holiday model exists
     *    e.g. ['2026-01-01' => "New Year's Day", ...]
     *
     * @return array
     */
    protected function buildCalendarConfig(): array
    {
        $config = [];

        // --- disableWeekends ---
        if (!empty($this->definition['disableWeekends'])) {
            $config['disableWeekends'] = true;
        }

        // --- highlightHolidays ---
        if (!empty($this->definition['highlightHolidays'])) {
            $holidays = $this->resolveHolidays();
            if (!empty($holidays)) {
                $config['holidays'] = $holidays;
            }
        }

        // --- showTeamAbsences ---
        if (!empty($this->definition['showTeamAbsences'])) {
            $absences = $this->resolveTeamAbsences();
            if (!empty($absences)) {
                $config['teamAbsences'] = $absences;
            }
        }

        return $config;
    }

    /**
     * Resolve holiday dates for the calendar.
     *
     * Priority:
     *  1. If a 'holidays' array is provided directly in the definition, use it.
     *  2. Otherwise, query the Holiday model (if it exists) for active holidays
     *     in the current company.
     *
     * @return array<string, string>  date => label
     */
    protected function resolveHolidays(): array
    {
        // Config-driven fallback (no provider needed)
        if (!empty($this->definition['holidays']) && is_array($this->definition['holidays'])) {
            return $this->definition['holidays'];
        }

        // Resolve via container contract (consuming app binds implementation)
        if (app()->bound(\QuickerFaster\UILibrary\Contracts\FieldTypes\CalendarEnhancementProvider::class)) {
            $provider = app(\QuickerFaster\UILibrary\Contracts\FieldTypes\CalendarEnhancementProvider::class);
            $companyId = session('current_company_id', 0);
            return $provider->getHolidays($companyId ? (string) $companyId : null);
        }

        return [];
    }

    /**
     * Resolve team absence date ranges for the calendar.
     *
     * Queries approved LeaveRequest records in the same company,
     * excluding the currently authenticated user's employee record.
     *
     * Returns an array of { from, to, label } objects for flatpickr.
     *
     * @return array
     */
    protected function resolveTeamAbsences(): array
    {
        // Resolve via container contract (consuming app binds implementation)
        if (app()->bound(\QuickerFaster\UILibrary\Contracts\FieldTypes\CalendarEnhancementProvider::class)) {
            $provider = app(\QuickerFaster\UILibrary\Contracts\FieldTypes\CalendarEnhancementProvider::class);
            $companyId = session('current_company_id', 0);
            return $provider->getTeamAbsences($companyId ? (string) $companyId : null);
        }

        return [];
    }



    public function renderInlineEditor($value, $record, array $extra = []): string
    {
        $wireModel = $extra['wire:model'] ?? 'editedData.' . $extra['rowId'] . '.' . $this->name;
        return $this->renderBlade('qf::components.fields.inline-editor.date', [
            'name' => $this->name,
            'wireModel' => $wireModel,
            'value' => $value,
        ]);
    }



    public function renderTable($value, $record): string
    {
        if ($value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d');
        }
        return e($value);
    }

    public function renderDetail($value, $record): string
    {
        return $this->renderTable($value, $record);
    }

    public function getValidationRules(): array
    {
        if (isset($this->definition['validation'])) {
            return [$this->name => $this->definition['validation']];
        }
        return [];
    }

    public function getOptions(): array
    {
        return [];
    }

    public function isRelationship(): bool
    {
        return false;
    }

    public function getRelationshipConfig(): ?array
    {
        return null;
    }

    public function getLabel(): string
    {
        return $this->definition['label'] ?? ucfirst($this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
