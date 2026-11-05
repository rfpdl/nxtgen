<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Foundation\Attributes\UserStory;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * What the test map (`make test-map`, TODO #10) shows: every staff and parent route with its access rule, who gets in
 * (AccessMatrix), its Feature's story and each criterion's tests (StoryClaims); then every other Feature (sign-in,
 * public pages, commands) with its stories and tests. Read from the same lists the tests use, so the page cannot claim
 * what the suite does not prove.
 *
 * @phpstan-type MapTest array{file: string, title: string, kind: string}
 * @phpstan-type MapCriterion array{token: string, kind: string, text: string, tests: list<MapTest>}
 * @phpstan-type MapRoute array{name: string, method: string, uri: string, rule: string, feature: string, title: string|null, cells: array<string, string>, stories: list<MapCriterion>}
 * @phpstan-type MapFeature array{service: string, feature: string, title: string|null, persona: string|null, stories: list<MapCriterion>}
 */
final class TestMap
{
    /** @return array{actors: list<string>, routes: list<MapRoute>, features: list<MapFeature>, untested: list<string>} */
    public static function export(string $root): array
    {
        $claims = StoryClaims::all($root);
        $stories = fn (?UserStory $story): array => self::criteria($story, $claims);
        $served = [];

        $routes = [];
        foreach (AccessMatrix::rows() as $name => [$row]) {
            $route = Router::getRoutes()->getByName($name);
            $feature = $route instanceof Route ? AccessMatrix::featureOf($route) : '';
            $served[$feature] = true;
            $story = self::storyOf($feature);
            $routes[] = [
                'name' => $name,
                'method' => $route instanceof Route ? implode('|', array_diff($route->methods(), ['HEAD'])) : '',
                'uri' => $route instanceof Route ? '/'.$route->uri() : '',
                'rule' => $row->label(),
                'feature' => class_basename($feature),
                'title' => $story?->title,
                'cells' => array_combine(
                    array_map(fn (Actor $a): string => $a->value, Actor::cases()),
                    array_map(fn (Actor $a): string => AccessMatrix::expected($name, $a), Actor::cases()),
                ),
                'stories' => $stories($story),
            ];
        }

        $features = [];
        foreach (self::featureClasses($root) as $feature) {
            if (! isset($served[$feature])) {
                $story = self::storyOf($feature);
                $features[] = [
                    'service' => explode('\\', $feature)[2] ?? '',
                    'feature' => class_basename($feature),
                    'title' => $story?->title,
                    'persona' => $story?->persona,
                    'stories' => $stories($story),
                ];
            }
        }

        $untested = [];
        foreach ([...$routes, ...$features] as $item) {
            foreach ($item['stories'] as $criterion) {
                if ($criterion['tests'] === []) {
                    $untested[] = $criterion['token'];
                }
            }
        }

        return [
            'actors' => array_map(fn (Actor $a): string => $a->value, Actor::cases()),
            'routes' => $routes,
            'features' => $features,
            'untested' => array_values(array_unique($untested)),
        ];
    }

    /**
     * @param  list<array{file: string, title: string, kind: string, stories: list<int>}>  $claims
     * @return list<MapCriterion>
     */
    private static function criteria(?UserStory $story, array $claims): array
    {
        $criteria = [];
        foreach (['happy' => $story->happyPaths ?? [], 'unhappy' => $story->unhappyPaths ?? []] as $kind => $paths) {
            foreach ($paths as $token => $text) {
                $number = (int) substr((string) $token, strlen('story#'));
                $criteria[] = [
                    'token' => (string) $token,
                    'kind' => $kind,
                    'text' => $text,
                    'tests' => array_values(array_map(
                        fn (array $c): array => ['file' => $c['file'], 'title' => $c['title'], 'kind' => $c['kind']],
                        array_filter($claims, fn (array $c): bool => in_array($number, $c['stories'], true)),
                    )),
                ];
            }
        }

        return $criteria;
    }

    private static function storyOf(string $feature): ?UserStory
    {
        if (! class_exists($feature)) {
            return null;
        }
        $attributes = (new ReflectionClass($feature))->getAttributes(UserStory::class);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    /** @return list<string> every concrete Feature, by class name */
    private static function featureClasses(string $root): array
    {
        $classes = [];
        foreach (Finder::create()->files()->in("{$root}/app/Services")->path('/Features\//')->name('*Feature.php')->sortByName() as $file) {
            $class = 'App\\'.str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen("{$root}/app/")));
            if (class_exists($class) && ! (new ReflectionClass($class))->isAbstract()) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
