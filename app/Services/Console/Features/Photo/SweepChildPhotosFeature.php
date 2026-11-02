<?php

declare(strict_types=1);

namespace App\Services\Console\Features\Photo;

use App\Domains\Church\Jobs\GetChurchesJob;
use App\Domains\Family\Jobs\DeleteOrphanChildPhotosJob;
use App\Domains\Family\Jobs\GetChildPhotoPathsJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Console\Features\BaseConsoleFeature;
use Carbon\CarbonImmutable;

/**
 * The photo sweep (TODO #22), church by church: delete the stored photo files no child points at, once they are a
 * day old (an upload writes its file a moment before it saves the pointer). A hidden photo is pointed at, so it stays
 * (SECURITY #19). Served from `nxtgen:photos-sweep`, daily. Returns the files deleted (or, dry, the ones it would).
 *
 * @extends BaseConsoleFeature<list<string>>
 */
#[UserStory(
    title: 'Sweep away photo files nobody points at',
    persona: 'the daily schedule, or an admin at the terminal',
    happyPaths: ['story#152' => 'Day-old photo files no Child points at are deleted, in every church; the rest stay'],
    unhappyPaths: ['story#153' => 'A dry run deletes nothing and lists what it would'],
)]
final class SweepChildPhotosFeature extends BaseConsoleFeature
{
    public function __construct(private readonly bool $dryRun = false) {}

    /** @return list<string> */
    public function handle(): array
    {
        $before = CarbonImmutable::now()->subDay();
        $swept = [];

        foreach ($this->run(new GetChurchesJob) as $church) {
            $keep = $this->run(new GetChildPhotoPathsJob(church: $church));
            $swept = [...$swept, ...$this->run(new DeleteOrphanChildPhotosJob(church: $church, keep: $keep, before: $before, dryRun: $this->dryRun))];
        }

        return $swept;
    }
}
