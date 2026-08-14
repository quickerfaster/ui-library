<?php

namespace QuickerFaster\UILibrary\Contracts\ActivityLogs;

interface ActivityLogModelResolver
{
    /**
     * Resolve the FQCN of the ActivityLog Eloquent model.
     * Return null when activity logging is not configured by the consuming app.
     */
    public function resolveModel(): ?string;
}
