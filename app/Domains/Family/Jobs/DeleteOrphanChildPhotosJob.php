<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

/**
 * The photo sweep (TODO #22): delete this church's stored photo files that no child points at — never shown, so
 * only taking up room: a replaced photo whose old file could not be deleted, an upload whose save failed. Only
 * files older than `before`, because an upload writes its file a moment before it saves the pointer
 * (CreateChildPhotoJob). Returns the files deleted (or, on a dry run, the ones it would delete).
 *
 * @extends BaseJob<list<string>>
 */
final class DeleteOrphanChildPhotosJob extends BaseJob
{
    /** @param list<string> $keep */
    public function __construct(
        private readonly Church $church,
        private readonly array $keep,
        private readonly CarbonImmutable $before,
        private readonly bool $dryRun,
    ) {}

    /** @return list<string> */
    public function handle(): array
    {
        $disk = Storage::disk('photos');
        $keep = array_flip($this->keep);

        $orphans = [];
        foreach ($disk->allFiles("children/{$this->church->id}") as $file) {
            if (is_string($file) && ! isset($keep[$file]) && $disk->lastModified($file) < $this->before->getTimestamp()) {
                $orphans[] = $file;
            }
        }
        sort($orphans);

        if (! $this->dryRun && $orphans !== []) {
            $disk->delete($orphans);
        }

        return $orphans;
    }
}
