<?php

namespace App\Services\Import;

use RuntimeException;

/**
 * Thrown when a document cannot be parsed at all (corrupt file, wrong type, ...).
 * Record-level data quirks are NOT exceptions — they become warnings/errors on
 * the review screen.
 */
class ParseException extends RuntimeException
{
}
