<?php

namespace App\Services\Import\Parsers;

use App\Services\Import\ParseException;

/**
 * Reads the downloadable CSV template. One row per beneficiary, grouped into
 * records by record_no:
 *
 *   record_no,coordinator,application_date,plan_holder_name,date_of_birth,address,
 *   beneficiary_name,beneficiary_birthday,beneficiary_relation
 *
 * Emits the same raw-record shape as DocxProfileParser.
 */
class CsvProfileParser implements DocumentParserInterface
{
    private const MAX_ROWS = 1000;

    public function format(): string
    {
        return 'csv';
    }

    public function parse(string $filePath): array
    {
        if (! is_file($filePath)) {
            throw new ParseException('File not found: ' . $filePath);
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new ParseException('Could not open the CSV file.');
        }

        // Read header.
        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            throw new ParseException('The CSV file is empty.');
        }
        $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);

        $required = ['plan_holder_name'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                fclose($handle);
                throw new ParseException('CSV is missing required column: ' . $col);
            }
        }

        $idx = array_flip($header);
        $groups = []; // record_no => ['fields' => [...], 'beneficiaries' => [...]]
        $order = [];  // record_no in first-seen order
        $rowCount = 0;
        $warnings = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            if ($rowCount > self::MAX_ROWS) {
                $warnings[] = 'Stopped reading at ' . self::MAX_ROWS . ' rows.';
                break;
            }

            $recordNo = trim((string) ($row[$idx['record_no']] ?? ''));
            if ($recordNo === '') {
                $recordNo = 'row-' . $rowCount;
            }
            if (! isset($groups[$recordNo])) {
                $groups[$recordNo] = [
                    'fields' => [
                        'coordinator' => '',
                        'application_date_raw' => '',
                        'plan_holder_name' => '',
                        'date_of_birth_raw' => '',
                        'address_raw' => '',
                    ],
                    'beneficiaries' => [],
                ];
                $order[] = $recordNo;
            }

            $get = static fn (string $col): string => trim((string) ($row[$idx[$col] ?? -1] ?? ''));

            // First non-empty value wins for the plan-holder fields.
            foreach (['coordinator', 'application_date_raw', 'plan_holder_name', 'date_of_birth_raw', 'address_raw'] as $field) {
                if ($groups[$recordNo]['fields'][$field] === '' && $get($field) !== '') {
                    $groups[$recordNo]['fields'][$field] = $get($field);
                }
            }

            $beneficiaryName = $get('beneficiary_name');
            $beneficiaryBirthday = $get('beneficiary_birthday');
            $beneficiaryRelation = $get('beneficiary_relation');
            if ($beneficiaryName !== '' || $beneficiaryRelation !== '') {
                $groups[$recordNo]['beneficiaries'][] = [
                    'name' => $beneficiaryName,
                    'birthday_raw' => $beneficiaryBirthday,
                    'relation' => $beneficiaryRelation,
                ];
            }
        }
        fclose($handle);

        $records = [];
        foreach ($order as $recordNo) {
            $g = $groups[$recordNo];
            $fields = $g['fields'];
            if ($fields['plan_holder_name'] === '') {
                $warnings[] = 'Skipped CSV record "' . $recordNo . '" — no plan holder name.';
                continue;
            }

            $record = $fields;
            $record['source_index'] = count($records);
            $record['beneficiaries'] = $g['beneficiaries'];
            $record['extra_fields'] = [];
            $record['extracted_text'] = self::buildExtractedText($record);
            $records[] = $record;
        }

        if ($records === []) {
            throw new ParseException('No readable plan-holder records were found in the CSV file.');
        }

        return ['records' => $records, 'warnings' => $warnings];
    }

    private static function buildExtractedText(array $record): string
    {
        $lines = [
            'Coordinator Name: ' . ($record['coordinator'] ?? ''),
            'Date of Application: ' . ($record['application_date_raw'] ?? ''),
            'Name of Plan Holder: ' . ($record['plan_holder_name'] ?? ''),
            'Date of Birth: ' . ($record['date_of_birth_raw'] ?? ''),
            'Address: ' . ($record['address_raw'] ?? ''),
            'BENEFICIARIES',
        ];
        foreach (($record['beneficiaries'] ?? []) as $i => $b) {
            $lines[] = ($i + 1) . '. ' . $b['name'] . ' | ' . $b['birthday_raw'] . ' | ' . $b['relation'];
        }

        return implode("\n", $lines);
    }
}
