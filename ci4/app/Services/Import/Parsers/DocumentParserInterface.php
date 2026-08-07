<?php

namespace App\Services\Import\Parsers;

use App\Services\Import\ParseException;

/**
 * A document parser turns a stored file into RAW records — untouched strings.
 * Normalization happens later so every stage stays auditable.
 */
interface DocumentParserInterface
{
    public function format(): string;

    /**
     * @param string $filePath absolute path to the stored file
     *
     * @return array{records: array<int, array<string, mixed>>, warnings: array<int, string>}
     *
     * @throws ParseException when the file cannot be parsed at all
     */
    public function parse(string $filePath): array;
}
