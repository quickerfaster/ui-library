<?php

namespace QuickerFaster\UILibrary\Attributes;

use Attribute;

/**
 * Declares the report-type key for a Reportable implementation.
 *
 * This attribute is the preferred way for a consuming application's report
 * class to declare its key, without forcing the library to instantiate the
 * class during auto-discovery:
 *
 *     #[ReportType('expense_claim')]
 *     class ExpenseClaimReport implements Reportable
 *     {
 *         public function generate(array $parameters = []): Document { ... }
 *         public function recipients(): array { ... }
 *         public function getReportType(): string { return 'expense_claim'; }
 *     }
 *
 * Discovery falls back to a public const REPORT_TYPE and finally to
 * getReportType() when the attribute is absent.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ReportType
{
    public function __construct(
        public string $type,
    ) {}
}
