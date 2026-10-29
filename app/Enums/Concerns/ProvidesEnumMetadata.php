<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Serialises a backed enum's cases for a page as `{ value, label, color }` rows — ported from aegis. A Feature
 * shares them under its `enums` prop (`'enums' => ['gender' => GenderEnum::toFrontend()]`) and the Vue side reads
 * them with `useEnum('gender')`. Use together with {@see HasEnumMetadata}.
 */
trait ProvidesEnumMetadata
{
    abstract public function label(): string;

    abstract public function color(): string;

    /**
     * Every case — or only `$cases`, in their order, when a page offers a few of them (e.g. the reasons staff may pick).
     *
     * @param  list<self>|null  $cases
     * @return list<array{value: string, label: string, color: string}>
     */
    public static function toFrontend(?array $cases = null): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ],
            $cases ?? self::cases(),
        );
    }
}
