<?php

namespace QuickerFaster\UILibrary\Contracts\Reports;

use QuickerFaster\UILibrary\Models\Document;

interface Reportable
{
    /**
     * Generate the report and return a Document.
     */
    public function generate(array $parameters = []): Document;

    /**
     * Get the recipients for this report.
     * Returns array of notifiable identifiers (e.g., user IDs).
     */
    public function recipients(): array;

    /**
     * Get the report type key.
     */
    public function getReportType(): string;
}