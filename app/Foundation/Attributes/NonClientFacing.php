<?php

declare(strict_types=1);

namespace App\Foundation\Attributes;

use Attribute;

/**
 * Marks a DTO that never reaches a page — ported from aegis. Registry rows, registration payloads, list filters
 * and the like are hand-offs between Jobs and Operations, so they carry this and NOT #[TypeScript], and stay out of
 * `resources/js/types/generated.d.ts`.
 *
 * The mirror of #[TypeScript]: exactly one of the two belongs on every DTO in a DataObjects folder, so a forgotten
 * attribute on a DTO a page does read is still caught (DataObjectTest).
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class NonClientFacing {}
