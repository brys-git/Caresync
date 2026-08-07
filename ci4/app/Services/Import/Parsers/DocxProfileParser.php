<?php

namespace App\Services\Import\Parsers;

use App\Services\Import\ParseException;
use DOMDocument;
use DOMNode;
use DOMXPath;
use ZipArchive;

/**
 * Parses the KAAGAPAY "RECORD PROFILING" Word documents.
 *
 * Each record is a pair of tables: a header table with labeled lines
 * (Coordinator Name / Date of Application / Name of Plan Holder / Date of Birth /
 * Address) followed by a beneficiaries table (Complete Name | Birthday | Relation).
 * A repeating letterhead block sits between records and is skipped.
 *
 * Uses only built-in ZipArchive + DOMDocument — no new dependency.
 */
class DocxProfileParser implements DocumentParserInterface
{
    private const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const LABEL_MAP = [
        'Coordinator Name' => 'coordinator',
        'Date of Application' => 'application_date_raw',
        'Name of Plan Holder' => 'plan_holder_name',
        'Date of Birth' => 'date_of_birth_raw',
        'Address' => 'address_raw',
    ];

    private const LETTERHEAD_MARKERS = [
        'KAAGAPAY', 'SEC REG', 'TIN', 'MAIN OFFICE', 'BRANCH OFFICE', 'FOUNDER',
        'CEO', 'BATASAN', 'SAN MATEO', 'RIZAL', 'RICARDO', 'RAMILO',
    ];

    private const BENEFICIARY_HEADER_TOKENS = ['complete name', 'birthday', 'relation'];

    public function format(): string
    {
        return 'docx';
    }

    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new ParseException('File not found: ' . $filePath);
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new ParseException('Not a valid Word document (.docx).');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || $xml === '') {
            throw new ParseException('The Word document has no readable content.');
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new ParseException('The Word document body could not be read.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS_W);

        $blocks = $this->extractBlocks($xpath);

        return $this->parseBlocks($blocks);
    }

    /**
     * Walk the body and flatten it into a sequence of blocks:
     *   ['type' => 'paragraph', 'text' => string]
     *   ['type' => 'table',    'rows' => array<int, array<int, string>>]
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractBlocks(DOMXPath $xpath): array
    {
        $blocks = [];
        $body = $xpath->query('//w:body/*');

        foreach ($body as $node) {
            $tag = $node->nodeName;
            if ($tag === 'w:p') {
                $text = $this->nodeText($node);
                $text = $this->cleanLine($text);
                if ($text !== '') {
                    $blocks[] = ['type' => 'paragraph', 'text' => $text];
                }
            } elseif ($tag === 'w:tbl') {
                $rows = [];
                foreach ($node->childNodes as $rowNode) {
                    if ($rowNode->nodeName !== 'w:tr') {
                        continue;
                    }
                    $cells = [];
                    foreach ($rowNode->childNodes as $cellNode) {
                        if ($cellNode->nodeName !== 'w:tc') {
                            continue;
                        }
                        $cellText = $this->cleanLine($this->nodeText($cellNode));
                        $cells[] = $cellText;
                    }
                    // Skip fully empty rows.
                    if (implode('', $cells) !== '') {
                        $rows[] = $cells;
                    }
                }
                if ($rows !== []) {
                    $blocks[] = ['type' => 'table', 'rows' => $rows];
                }
            }
        }

        return $blocks;
    }

    /**
     * Recursively gather the text of a paragraph/cell, treating w:br/w:cr as
     * line breaks and w:tab as a tab.
     */
    private function nodeText(DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->nodeValue;
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            switch ($child->nodeName) {
                case 'w:t':
                    $text .= $child->nodeValue;
                    break;
                case 'w:tab':
                    $text .= "\t";
                    break;
                case 'w:br':
                case 'w:cr':
                    $text .= "\n";
                    break;
                case 'w:p':
                    // A new paragraph inside a cell is a line boundary.
                    $text .= $this->nodeText($child) . "\n";
                    break;
                default:
                    $text .= $this->nodeText($child);
                    break;
            }
        }

        return $text;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     *
     * @return array{records: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    private function parseBlocks(array $blocks): array
    {
        $records = [];
        $warnings = [];

        foreach ($blocks as $block) {
            if ($block['type'] === 'table') {
                $grid = $block['rows'];

                if ($this->isRecordHeaderTable($grid)) {
                    $record = $this->parseRecordTable($grid);
                    if ($record === null) {
                        $warnings[] = 'Record table had no readable fields and was skipped.';
                        continue;
                    }
                    $record['source_index'] = count($records);
                    $record['extracted_text'] = self::buildExtractedText($record);
                    $records[] = $record;
                } elseif ($this->isBeneficiaryTable($grid)) {
                    // Standalone beneficiaries table following a record (alternate layout).
                    $lastIndex = count($records) - 1;
                    if ($lastIndex >= 0 && empty($records[$lastIndex]['beneficiaries'])) {
                        $records[$lastIndex]['beneficiaries'] = $this->parseBeneficiaryRows($grid);
                        $records[$lastIndex]['extracted_text'] = self::buildExtractedText($records[$lastIndex]);
                    } else {
                        $warnings[] = 'Beneficiaries table found without a preceding plan-holder record.';
                    }
                } else {
                    $warnings[] = 'Skipped an unrecognized table.';
                }
            } else {
                $text = $block['text'];
                if ($this->isLetterhead($text)) {
                    continue;
                }
                if (stripos($text, 'BENEFICIARIES') !== false) {
                    continue; // beneficiaries are inside the record table
                }
                $warnings[] = 'Unrecognized paragraph skipped: "' . mb_substr($text, 0, 60) . '"';
            }
        }

        return ['records' => $records, 'warnings' => $warnings];
    }

    /**
     * A record header table contains labeled lines such as "Name of Plan Holder:".
     */
    private function isRecordHeaderTable(array $grid): bool
    {
        $haystack = $this->gridText($grid);

        return stripos($haystack, 'Name of Plan Holder:') !== false
            || stripos($haystack, 'Coordinator Name:') !== false;
    }

    /**
     * Parse one record table. The observed documents carry BOTH the labeled
     * fields and the beneficiaries inside a single 1-column table:
     *
     *   Coordinator Name: <name>
     *   Date of Application: <date>
     *   Name of Plan Holder: <full name>
     *   Date of Birth: <date>
     *   Address: <free text>
     *   BENEFICIARIES
     *   Complete Name
     *   Birthday
     *   Relation
     *   <name> <birthday> <relation>   (repeated, one value per line)
     *
     * @return array<string, mixed>|null
     */
    private function parseRecordTable(array $grid): ?array
    {
        $full = $this->gridText($grid);
        $lines = preg_split('/\R/u', $full) ?: [];
        $lines = array_map([$this, 'cleanLine'], $lines);

        // Split at the beneficiaries header so field parsing only sees the header lines.
        // Empty lines are KEPT — the beneficiaries block relies on their positions.
        $headerLines = [];
        $beneficiaryLines = [];
        $splitIndex = null;
        foreach ($lines as $i => $line) {
            if (stripos($line, 'Complete Name') !== false) {
                $splitIndex = $i;
                break;
            }
            $headerLines[] = $line;
        }

        $fields = [
            'coordinator' => '',
            'application_date_raw' => '',
            'plan_holder_name' => '',
            'date_of_birth_raw' => '',
            'address_raw' => '',
            'extra_fields' => [],
        ];

        foreach ($headerLines as $line) {
            if ($line === '') {
                continue;
            }
            if (preg_match('/^\s*([A-Za-z][A-Za-z ]*?)\s*:\s*(.*)$/u', $line, $m)) {
                $label = trim($m[1]);
                $value = trim($m[2]);
                if (isset(self::LABEL_MAP[$label])) {
                    $fields[self::LABEL_MAP[$label]] = $value;
                } else {
                    $fields['extra_fields'][$label] = $value;
                }
            }
        }

        // Guard: require at least a plan holder name, otherwise this is not a record.
        if ($fields['plan_holder_name'] === '' && $fields['coordinator'] === '') {
            return null;
        }

        if ($splitIndex !== null) {
            $beneficiaryLines = array_slice($lines, $splitIndex);
        }
        $fields['beneficiaries'] = $this->parseBeneficiaryLines($beneficiaryLines);

        return $fields;
    }

    /**
     * Parse beneficiary rows from the lines that follow the
     * "Complete Name / Birthday / Relation" header. Each beneficiary occupies
     * three consecutive lines (name, birthday, relation). Empty middle fields
     * (e.g. a missing birthday) are preserved by keeping empty lines intact.
     *
     * @return array<int, array{name: string, birthday_raw: string, relation: string}>
     */
    private function parseBeneficiaryLines(array $lines): array
    {
        $rows = [];
        $count = count($lines);
        if ($count === 0) {
            return [];
        }

        // Locate where the data begins (after the header row(s)).
        $dataStart = null;
        for ($i = 0; $i < $count; $i++) {
            if (stripos($lines[$i], 'Complete Name') === false) {
                continue;
            }
            if (stripos($lines[$i], 'Birthday') !== false && stripos($lines[$i], 'Relation') !== false) {
                // Single-line header: "Complete Name | Birthday | Relation"
                $dataStart = $i + 1;
            } else {
                $j = $i + 1;
                if ($j < $count && stripos($lines[$j], 'Birthday') !== false) {
                    $j++;
                }
                if ($j < $count && stripos($lines[$j], 'Relation') !== false) {
                    $j++;
                }
                $dataStart = $j;
            }
            break;
        }

        if ($dataStart === null || $dataStart >= $count) {
            return [];
        }

        $tail = array_slice($lines, $dataStart);
        for ($k = 0; $k < count($tail); $k += 3) {
            $name = $this->cleanLine($tail[$k] ?? '');
            $birthday = $this->cleanLine($tail[$k + 1] ?? '');
            $relation = $this->cleanLine($tail[$k + 2] ?? '');
            if ($name === '' && $relation === '') {
                continue; // padding / blank group
            }
            $rows[] = [
                'name' => $name,
                'birthday_raw' => $birthday,
                'relation' => $relation,
            ];
        }

        return $rows;
    }

    private function isBeneficiaryTable(array $grid): bool
    {
        $rows = array_slice($grid, 0, 3);
        foreach ($rows as $row) {
            $joined = strtolower(implode('|', $row));
            $hasAll = true;
            foreach (self::BENEFICIARY_HEADER_TOKENS as $token) {
                if (stripos($joined, $token) === false) {
                    $hasAll = false;
                    break;
                }
            }
            if ($hasAll) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{name: string, birthday_raw: string, relation: string}>
     */
    private function parseBeneficiaryRows(array $grid): array
    {
        $rows = [];
        $started = false;
        foreach ($grid as $row) {
            $joined = strtolower(implode('|', $row));
            $isHeader = true;
            foreach (self::BENEFICIARY_HEADER_TOKENS as $token) {
                if (stripos($joined, $token) === false) {
                    $isHeader = false;
                    break;
                }
            }
            if ($isHeader) {
                $started = true;
                continue;
            }
            if (! $started) {
                continue; // skip rows before the header
            }

            $name = $this->cleanLine($row[0] ?? '');
            $birthday = $this->cleanLine($row[1] ?? '');
            $relation = $this->cleanLine($row[2] ?? '');

            if ($name === '' && $relation === '') {
                continue; // blank row
            }

            $rows[] = [
                'name' => $name,
                'birthday_raw' => $birthday,
                'relation' => $relation,
            ];
        }

        return $rows;
    }

    private function isLetterhead(string $text): bool
    {
        $upper = strtoupper($text);
        foreach (self::LETTERHEAD_MARKERS as $marker) {
            if (strpos($upper, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private function gridText(array $grid): string
    {
        $lines = [];
        foreach ($grid as $row) {
            $lines[] = implode("\n", $row);
        }

        return implode("\n", $lines);
    }

    private function cleanLine(string $text): string
    {
        // Normalize smart quotes / dashes to ASCII.
        $text = str_replace(["‘", "’", "‚"], "'", $text);
        $text = str_replace(["“", "”", "„"], '"', $text);
        $text = str_replace(['–', '—'], '-', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s*(\n)\s*/u', "$1", $text) ?? '';

        return trim($text, " \t\n");
    }

    private static function buildExtractedText(array $header): string
    {
        $lines = [];
        foreach (self::LABEL_MAP as $label => $key) {
            $lines[] = $label . ': ' . ($header[$key] ?? '');
        }
        foreach (($header['extra_fields'] ?? []) as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
        $lines[] = 'BENEFICIARIES';
        foreach (($header['beneficiaries'] ?? []) as $i => $beneficiary) {
            $lines[] = ($i + 1) . '. ' . $beneficiary['name'] . ' | ' . $beneficiary['birthday_raw'] . ' | ' . $beneficiary['relation'];
        }

        return implode("\n", $lines);
    }
}
