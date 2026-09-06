<?php

namespace QuickerFaster\UILibrary\Services\Reports;

use QuickerFaster\UILibrary\Contracts\Reports\Reportable;
use QuickerFaster\UILibrary\Models\ReportSchedule;
use QuickerFaster\UILibrary\Services\Documents\DocumentEngine;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class ReportEngine
{
    public function __construct(
        protected DocumentEngine $documentEngine,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Process a scheduled report: generate document, notify recipients.
     */
    public function process(ReportSchedule $schedule): bool
    {
        try {
            $reportClass = $this->resolveReportClass($schedule->report_type);
            if (!$reportClass) {
                throw new \RuntimeException("Report type '{$schedule->report_type}' not found.");
            }

            /** @var Reportable $report */
            $report = app($reportClass);
            $document = $report->generate($schedule->parameters ?? []);

            $this->notifyRecipients($schedule, $document);

            $schedule->markRun();

            Log::info("Scheduled report '{$schedule->name}' generated successfully.", [
                'schedule_id' => $schedule->id,
                'document_id' => $document->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Scheduled report '{$schedule->name}' failed: " . $e->getMessage(), [
                'schedule_id' => $schedule->id,
            ]);
            $schedule->markFailed();
            return false;
        }
    }

    /**
     * Resolve the report class from config.
     */
    protected function resolveReportClass(string $type): ?string
    {
        return config("ui-library.reports.report_types.{$type}");
    }

    /**
     * Notify recipients about the generated document.
     */
    protected function notifyRecipients(ReportSchedule $schedule, $document): void
    {
        $recipients = $schedule->recipients ?? [];
        $channels = config('ui-library.reports.notification_channels', ['database']);

        foreach ($recipients as $recipientId) {
            $user = $this->resolveNotifiable($recipientId);
            if ($user instanceof Notifiable) {
                $this->notificationService->dispatch($user, 'report_ready', [
                    'report_name' => $schedule->name,
                    'document_id' => $document->id,
                ]);
            }
        }
    }

    /**
     * Resolve a notifiable entity from an ID.
     *
     * Uses the same config source as WorkflowEngine for consistency:
     * config('ui-library.user.model') is authoritative, with a fallback
     * to the default App User model.
     */
    protected function resolveNotifiable(int|string $id): ?Notifiable
    {
        $userModel = config('ui-library.user.model', \App\Models\User::class);
        $user = $userModel::find($id);

        return $user instanceof Notifiable ? $user : null;
    }
}