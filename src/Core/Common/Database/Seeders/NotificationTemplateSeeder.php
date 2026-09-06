<?php

namespace QuickerFaster\UILibrary\Core\Common\Database\Seeders;

use Illuminate\Database\Seeder;
use QuickerFaster\UILibrary\Models\NotificationTemplate;
use QuickerFaster\UILibrary\Services\Notifications\NotificationDiscoveryService;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Built-in library templates
        $templates = [
            // Document templates
            ['type' => 'document_generated', 'channel' => 'database', 'subject' => 'Document Generated', 'body_template' => 'Your document "{name}" has been generated.', 'locale' => 'en'],
            ['type' => 'document_generated', 'channel' => 'mail', 'subject' => 'Document Generated', 'body_template' => 'Dear user, your document "{name}" has been generated and is ready for download.', 'locale' => 'en'],

            // Report templates
            ['type' => 'report_ready', 'channel' => 'database', 'subject' => 'Report Ready', 'body_template' => 'Report "{report_name}" is ready.', 'locale' => 'en'],
            ['type' => 'report_ready', 'channel' => 'mail', 'subject' => 'Report Ready', 'body_template' => 'Your report "{report_name}" has been generated.', 'locale' => 'en'],

            // Workflow templates (used by WorkflowEngine)
            ['type' => 'workflow_submitted', 'channel' => 'database', 'subject' => 'Workflow Submitted', 'body_template' => 'A new workflow has been submitted for your review.', 'locale' => 'en'],
            ['type' => 'workflow_submitted', 'channel' => 'mail', 'subject' => 'Workflow Submitted', 'body_template' => 'A new workflow has been submitted for your review.', 'locale' => 'en'],
            ['type' => 'workflow_approved', 'channel' => 'database', 'subject' => 'Workflow Approved', 'body_template' => 'A workflow step has been approved.', 'locale' => 'en'],
            ['type' => 'workflow_approved', 'channel' => 'mail', 'subject' => 'Workflow Approved', 'body_template' => 'A workflow step has been approved.', 'locale' => 'en'],
            ['type' => 'workflow_rejected', 'channel' => 'database', 'subject' => 'Workflow Rejected', 'body_template' => 'A workflow step has been rejected.', 'locale' => 'en'],
            ['type' => 'workflow_rejected', 'channel' => 'mail', 'subject' => 'Workflow Rejected', 'body_template' => 'A workflow step has been rejected.', 'locale' => 'en'],
            ['type' => 'workflow_recalled', 'channel' => 'database', 'subject' => 'Workflow Recalled', 'body_template' => 'A workflow has been recalled.', 'locale' => 'en'],
            ['type' => 'workflow_recalled', 'channel' => 'mail', 'subject' => 'Workflow Recalled', 'body_template' => 'A workflow has been recalled.', 'locale' => 'en'],

            // Legacy — kept for backward compatibility with existing data
            ['type' => 'workflow_stage_changed', 'channel' => 'database', 'subject' => 'Workflow Update', 'body_template' => 'Workflow "{workflow_name}" moved to stage "{stage_name}".', 'locale' => 'en'],
        ];

        // Auto-discover business-module notification templates
        $discovery = app(NotificationDiscoveryService::class);
        $discovered = $discovery->discover();
        $templates = array_merge($templates, $discovered['templates']);

        foreach ($templates as $template) {
            NotificationTemplate::firstOrCreate(
                ['type' => $template['type'], 'channel' => $template['channel'], 'locale' => $template['locale']],
                $template
            );
        }
    }
}