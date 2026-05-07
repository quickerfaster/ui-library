<?php

namespace QuickerFaster\UILibrary\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use QuickerFaster\UILibrary\Models\Export;

class CleanExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quicker-faster-ui:clean-exports {--days=1 : Delete exports older than this number of days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old export files and database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $exports = Export::where('created_at', '<', $cutoff)->get();

        if ($exports->isEmpty()) {
            $this->info("No exports older than {$days} day(s) found.");
            return 0;
        }

        $deletedCount = 0;
        $disk = Storage::disk('local');

        foreach ($exports as $export) {
            // Delete the physical file if it exists
            if ($export->file_path && $disk->exists($export->file_path)) {
                $disk->delete($export->file_path);
                $this->line("Deleted file: {$export->file_path}");
            }

            // Delete the database record
            $export->delete();
            $deletedCount++;
        }

        $this->info("Deleted {$deletedCount} export record(s) and their associated files.");
        return 0;
    }
}