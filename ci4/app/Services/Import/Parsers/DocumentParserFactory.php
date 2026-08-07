<?php

namespace App\Services\Import\Parsers;

use App\Services\Import\ParseException;

/**
 * Returns the parser for a given source format. Register new parsers here.
 */
class DocumentParserFactory
{
    /** @var array<string, class-string<DocumentParserInterface>> */
    private const PARSERS = [
        'docx' => DocxProfileParser::class,
        'csv' => CsvProfileParser::class,
    ];

    public static function create(string $format): DocumentParserInterface
    {
        $class = self::PARSERS[$format] ?? null;
        if ($class === null) {
            throw new ParseException('Unsupported document format: ' . $format);
        }

        return new $class();
    }
}
