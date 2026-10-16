<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * The guards on the shared guard patterns (Helpers.php). Each pattern is used by several boundary tests, and a
 * pattern that silently stopped matching one form would pass that form in every layer at once — which is how
 * `\App\Data\Models\Family::query()` and `Child::with('family')->get()` slipped through the first version.
 */
it('sees every way into a model\'s rows', function (string $code, bool $query): void {
    expect(preg_match(modelEntryPattern(), $code) === 1)->toBe($query);
})->with([
    'query()' => ['Family::query()->get()', true],
    'a static where' => ["Child::where('id', 1)", true],
    'with()' => ["Child::with('family')->get()", true],
    'all()' => ['Branch::all()', true],
    'find()' => ['Family::find($id)', true],
    'latest()' => ['Note::latest()->first()', true],
    'a scope' => ["Family::matching('x')", true],
    'a model helper that writes' => ['Note::system(about: $c, body: $b, by: null)', true],
    'fully qualified' => ['\App\Data\Models\Family::query()', true],
    'qualified, no leading slash' => ['App\Data\Models\Guardian::where(1)', true],
    'partly qualified' => ['Models\Family::where(1)', true],
    'leading slash only' => ['\Family::query()', true],
    'a factory' => ['Family::factory()->create()', false],
    'a pure helper' => ['DuplicateCandidate::familyOf($pair->subject)', false],
    'a class constant' => ['Family::class', false],
    'another class, same prefix' => ['FamilyClaimStatusEnum::from($x)', false],
    'not a model' => ['Carbon::create(2026)', false],
    'an instance call' => ['$family->query()', false],
]);

it('sees every way a Job is run by hand', function (string $code, bool $byHand): void {
    expect(runsJobByHand($code))->toBe($byHand);
})->with([
    'new, then handle' => ['(new GetAccessScopeJob(user: $u, organization: $o))->handle()', true],
    'nested arguments' => ['count((new GetLandingOptionsJob(user: f($a, g($b))))->handle())', true],
    'app()' => ['app(GetAccessScopeJob::class, [\'user\' => $u])->handle()', true],
    'resolve()' => ['resolve(\App\Domains\X\Jobs\GetXJob::class)->handle()', true],
    'make()' => ['app()->make(GetXJob::class)->handle()', true],
    'a variable' => ["\$job = new GetXJob(id: 1);\n\$scope = \$job->handle();", true],
    'a variable from app()' => ["\$j = app(GetXJob::class);\n\$j->handle()", true],
    'run()' => ['$this->run(new GetXJob(id: 1))', false],
    'the next closure' => ['return $next($request)', false],
    'a variable never handled' => ['$job = new GetXJob(id: 1); $this->run($job)', false],
]);
