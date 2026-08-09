<?php

namespace QuickerFaster\UILibrary\Core\Common\Database\Seeders;

use Illuminate\Database\Seeder;
use QuickerFaster\UILibrary\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['type' => 'document_generated', 'channel' => 'database', 'subject' => 'Document Generated', 'body_template' => 'Your document "{name}" has been generated.', 'locale' => 'en'],
            ['type' => 'document_generated', 'channel' => 'mail', 'subject' => 'Document Generated', 'body_template' => 'Dear user, your document "{name}" has been generated and is ready for download.', 'locale' => 'en'],
            ['type' => 'report_ready', 'channel' => 'database', 'subject' => 'Report Ready', 'body_template' => 'Report "{report_name}" is ready.', 'locale' => 'en'],
            ['type' => 'report_ready', 'channel' => 'mail', 'subject' => 'Report Ready', 'body_template' => 'Your report "{report_name}" has been generated.', 'locale' => 'en'],
            ['type' => 'workflow_stage_changed', 'channel' => 'database', 'subject' => 'Workflow Update', 'body_template' => 'Workflow "{workflow_name}" moved to stage "{stage_name}".', 'locale' => 'en'],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::firstOrCreate(
                ['type' => $template['type'], 'channel' => $template['channel'], 'locale' => $template['locale']],
                $template
            );
        }
    }
}