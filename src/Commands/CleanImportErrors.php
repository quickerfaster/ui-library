<?php

namespace QuickerFaster\UILibrary\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use QuickerFaster\UILibrary\Models\Import;

class CleanImportErrors extends Command
{
    protected $signature = 'quicker-faster-ui:clean-import-errors {--days=7}';
    protected $description = 'Delete old import error files and clear error_file columns';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $imports = Import::whereNotNull('error_file')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($imports as $import) {
            if (Storage::disk('local')->exists($import->error_file)) {
                Storage::disk('local')->delete($import->error_file);
            }
            $import->update(['error_file' => null]);
        }

        $this->info("Cleaned " . $imports->count() . " old error files.");
    }
}