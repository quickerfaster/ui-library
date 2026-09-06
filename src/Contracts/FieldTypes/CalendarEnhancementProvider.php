<?php

namespace QuickerFaster\UILibrary\Contracts\FieldTypes;

interface CalendarEnhancementProvider
{
    /**
     * Get holiday dates for the calendar.
     *
     * @param string|null $companyId
     * @return array<string, string> ['2026-01-01' => "New Year's Day", ...]
     */
    public function getHolidays(?string $companyId = null): array;

    /**
     * Get team absence dates for the calendar.
     *
     * @param string|null $companyId
     * @param int|null $excludeUserId
     * @return array<int, array{from: string, to: string, label: string}>
     */
    public function getTeamAbsences(?string $companyId = null, ?int $excludeUserId = null): array;
}