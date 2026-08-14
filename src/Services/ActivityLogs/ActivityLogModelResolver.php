<?php

namespace QuickerFaster\UILibrary\Services\ActivityLogs;

use QuickerFaster\UILibrary\Contracts\ActivityLogs\ActivityLogModelResolver as ResolverContract;

class ActivityLogModelResolver implements ResolverContract
{
    public function resolveModel(): ?string
    {
        return config('ui-library.activity_logs.model');
    }
}
