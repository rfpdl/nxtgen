<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Testing\TestResponse;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The rows of a spreadsheet download's first sheet, each as its cells joined by `|`.
 *
 * @param  TestResponse<Response>  $response
 * @return list<string>
 */
function exportedRows(TestResponse $response): array
{
    $download = $response->baseResponse;
    if (! $download instanceof BinaryFileResponse) {
        throw new RuntimeException('Expected a file download.');
    }

    return spreadsheetRows($download->getFile()->getPathname());
}

/**
 * The rows of a spreadsheet file's first sheet, each as its cells joined by `|`.
 *
 * @return list<string>
 */
function spreadsheetRows(string $path): array
{
    $rows = [];
    $reader = new Reader;
    $reader->open($path);
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = implode('|', array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $row->toArray()));
        }
        break;
    }
    $reader->close();

    return $rows;
}
