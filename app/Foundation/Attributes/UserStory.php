<?php

declare(strict_types=1);

namespace App\Foundation\Attributes;

use Attribute;

/**
 * What a Feature is for, in the glossary's words — aegis's `UserStory`. Required on every Feature
 * (StoryTokenTest).
 *
 * Each criterion is keyed `'story#<n>' => 'what happens'`, numbered from 1 across the whole app and never
 * reused. A test (Pest or Playwright) that proves a criterion carries the token, at-prefixed, in its TITLE
 * (`@story#12`; a comment does not count):
 *
 *     #[UserStory(
 *         title: 'Correct a family email',
 *         persona: 'church admin',
 *         happyPaths: ['story#12' => 'The family gets a new link at the corrected address'],
 *         unhappyPaths: ['story#13' => 'An email another family uses is refused'],
 *     )]
 *
 *     it('sends a fresh link to the corrected address @story#12', …)
 *
 * `grep story#12` then returns the criterion and every test that proves it. Unlike aegis there is no
 * allocator: take the next number after the highest in use.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UserStory
{
    /**
     * @param  array<string, string>  $happyPaths  `'story#<n>' => criterion` — what success looks like
     * @param  array<string, string>  $unhappyPaths  `'story#<n>' => criterion` — refusals and edge cases
     */
    public function __construct(
        public string $title,
        public string $persona,
        public array $happyPaths = [],
        public array $unhappyPaths = [],
    ) {}
}
