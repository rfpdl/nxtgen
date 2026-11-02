<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Console\Features\Photo\SweepChildPhotosFeature;
use Illuminate\Console\Command;
use Lucid\Bus\ServesFeatures;

/**
 * The photo sweep (TODO #22): delete stored photo files no child points at. Scheduled daily (routes/console.php);
 * `--dry-run` lists them and deletes nothing.
 */
final class PhotosSweepCommand extends Command
{
    use ServesFeatures;

    protected $signature = 'nxtgen:photos-sweep {--dry-run : List the files, delete nothing}';

    protected $description = 'Delete stored photo files no child points at (a day old or more)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $files = $this->serve(SweepChildPhotosFeature::class, ['dryRun' => $dryRun]);

        foreach ($files as $file) {
            $this->line($file);
        }
        $count = count($files);
        $noun = $count === 1 ? 'photo file' : 'photo files';
        $this->info($dryRun
            ? "Would delete {$count} {$noun} no child points at (dry run: nothing deleted)."
            : "Deleted {$count} {$noun} no child points at.");

        return self::SUCCESS;
    }
}
