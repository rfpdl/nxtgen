<?php

declare(strict_types=1);

namespace Tests\Support;

use Symfony\Component\Finder\Finder;

/**
 * Which tests claim which stories: a test claims a story by carrying its token, at-prefixed, in its TITLE — Pest's
 * `it(`/`test(`/`describe(`, Playwright's `test(` — with comments stripped first, so a note that mentions a story
 * claims nothing. Read by StoryTokenTest (every story has a test) and by the test map (`make test-map`), which lists
 * each story's tests and runs them. (Tokens are described here, never written out: a literal would be a claim.)
 */
final class StoryClaims
{
    /**
     * Every titled test or group in one file, and the story numbers its title claims.
     *
     * @return list<array{title: string, stories: list<int>}>
     */
    public static function titlesIn(string $source, bool $php): array
    {
        $code = $php
            ? self::stripPhpComments($source)
            : (string) preg_replace(['#/\*.*?\*/#s', '#(?<![:\\\\])//[^\n]*#'], '', $source);

        preg_match_all('/\b(?:it|test|describe)(?:\.\w+)?\(\s*([\'"`])((?:\\\\.|(?!\1).)*)\1/s', $code, $titles);

        return array_map(function (string $title): array {
            preg_match_all('/@story#(\d+)\b/', $title, $m);

            return ['title' => stripslashes($title), 'stories' => array_values(array_unique(array_map(intval(...), $m[1])))];
        }, $titles[2]);
    }

    /** @return list<int> the story numbers a test file claims */
    public static function numbersIn(string $source, bool $php): array
    {
        return array_values(array_unique(array_merge(...array_map(
            fn (array $t): array => $t['stories'],
            [['title' => '', 'stories' => []], ...self::titlesIn($source, $php)],
        ))));
    }

    /**
     * Every test title that claims a story, across the Pest and Playwright suites: its file (from the project root),
     * kind (`php` or `e2e`) and the stories it claims.
     *
     * @return list<array{file: string, title: string, kind: string, stories: list<int>}>
     */
    public static function all(string $root): array
    {
        $finder = Finder::create()->files()->ignoreDotFiles(true)->exclude('node_modules')
            ->in(["{$root}/tests", "{$root}/app/Domains"])
            ->name(['*Test.php', '*.spec.ts'])
            ->sortByName();

        $claims = [];
        foreach ($finder as $file) {
            $php = $file->getExtension() === 'php';
            foreach (self::titlesIn($file->getContents(), $php) as $test) {
                if ($test['stories'] !== []) {
                    $claims[] = ['file' => ltrim(substr($file->getPathname(), strlen($root)), '/'), 'title' => $test['title'], 'kind' => $php ? 'php' : 'e2e', 'stories' => $test['stories']];
                }
            }
        }

        return $claims;
    }

    private static function stripPhpComments(string $source): string
    {
        $result = '';
        foreach (token_get_all($source) as $token) {
            $result .= is_array($token) ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : $token[1]) : $token;
        }

        return $result;
    }
}
