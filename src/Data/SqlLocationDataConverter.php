<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Data;

use RuntimeException;

class SqlLocationDataConverter
{
    /**
     * @return array<string, mixed>
     */
    public function convertDirectory(string $sourcePath, string $outputPath): array
    {
        return $this->convertRows([], [], [], $outputPath);
    }

    /**
     * @param  array<int, array<string, mixed>>  $provinceRows
     * @param  array<int, array<string, mixed>>  $cityRows
     * @param  array<int, array<string, mixed>>  $districtRows
     * @return array<string, mixed>
     */
    public function convertRows(array $provinceRows, array $cityRows, array $districtRows, string $outputPath): array
    {
        throw new RuntimeException('SQL conversion is a non-canonical utility and cannot generate public package codes without full county and official-district hierarchy. Use the Excel converter for release data.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseSql(string $sql): array
    {
        $rows = [];

        foreach ($this->insertStatements($sql) as $statement) {
            $columns = trim($statement['columns']) !== ''
                ? $this->splitColumns($statement['columns'])
                : [];

            foreach ($this->valueGroups($statement['values']) as $values) {
                $rows[] = $columns === []
                    ? $this->rowWithoutColumns($values)
                    : $this->rowWithColumns($columns, $values);
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{columns: string, values: string}>
     */
    private function insertStatements(string $sql): array
    {
        $statements = [];
        $length = strlen($sql);
        $offset = 0;

        while (preg_match('/insert\s+into\s+[`"\[]?[\w.-]+[`"\]]?\s*(?:\((?<columns>.*?)\))?\s+values\s*/is', $sql, $matches, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $valuesStart = $matches[0][1] + strlen($matches[0][0]);
            $quoted = false;

            for ($index = $valuesStart; $index < $length; $index++) {
                $char = $sql[$index];

                if ($char === "'" && ($index === 0 || $sql[$index - 1] !== '\\')) {
                    if ($quoted && ($sql[$index + 1] ?? null) === "'") {
                        $index++;

                        continue;
                    }

                    $quoted = ! $quoted;
                }

                if ($char === ';' && ! $quoted) {
                    $statements[] = [
                        'columns' => isset($matches['columns'][0]) ? (string) $matches['columns'][0] : '',
                        'values' => substr($sql, $valuesStart, $index - $valuesStart),
                    ];
                    $offset = $index + 1;

                    break;
                }
            }

            if ($index >= $length) {
                break;
            }
        }

        return $statements;
    }

    /**
     * @return array<int, string>
     */
    private function splitColumns(string $columns): array
    {
        return array_map(
            static fn (string $column): string => trim($column, " \t\n\r\0\x0B`\"[]"),
            explode(',', $columns),
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function valueGroups(string $values): array
    {
        $groups = [];
        $buffer = '';
        $depth = 0;
        $quoted = false;
        $length = strlen($values);

        for ($index = 0; $index < $length; $index++) {
            $char = $values[$index];

            if ($char === "'" && ($index === 0 || $values[$index - 1] !== '\\')) {
                if ($quoted && ($values[$index + 1] ?? null) === "'") {
                    $buffer .= $char.$values[++$index];

                    continue;
                }

                $quoted = ! $quoted;
            }

            if ($char === '(' && ! $quoted) {
                $depth++;

                if ($depth === 1) {
                    $buffer = '';

                    continue;
                }
            }

            if ($char === ')' && ! $quoted) {
                $depth--;

                if ($depth === 0) {
                    $groups[] = $this->parseValues($buffer);
                    $buffer = '';

                    continue;
                }
            }

            if ($depth > 0) {
                $buffer .= $char;
            }
        }

        return $groups;
    }

    /**
     * @return array<int, mixed>
     */
    private function parseValues(string $values): array
    {
        $parts = [];
        $buffer = '';
        $quoted = false;
        $length = strlen($values);

        for ($index = 0; $index < $length; $index++) {
            $char = $values[$index];

            if ($char === "'" && ($index === 0 || $values[$index - 1] !== '\\')) {
                if ($quoted && ($values[$index + 1] ?? null) === "'") {
                    $buffer .= $char.$values[++$index];

                    continue;
                }

                $quoted = ! $quoted;
                $buffer .= $char;

                continue;
            }

            if ($char === ',' && ! $quoted) {
                $parts[] = $this->parseValue($buffer);
                $buffer = '';

                continue;
            }

            $buffer .= $char;
        }

        $parts[] = $this->parseValue($buffer);

        return $parts;
    }

    private function parseValue(string $value): mixed
    {
        $value = trim($value);

        if (strcasecmp($value, 'null') === 0) {
            return null;
        }

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return str_replace(["\\'", "''"], ["'", "'"], substr($value, 1, -1));
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<string, mixed>
     */
    private function rowWithoutColumns(array $values): array
    {
        $row = [];

        foreach ($values as $index => $value) {
            $row['column_'.$index] = $value;
        }

        return $row;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, mixed>  $values
     * @return array<string, mixed>
     */
    private function rowWithColumns(array $columns, array $values): array
    {
        $row = [];

        foreach ($columns as $index => $column) {
            $row[$column] = $values[$index] ?? null;
        }

        return $row;
    }
}
