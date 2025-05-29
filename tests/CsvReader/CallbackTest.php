<?php

use Jletrondo\CsvReader\CsvReader;

beforeEach(function () {
    // Default columns for most tests
    $this->columns = [
        ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'validate' => 'required'],
        ['column_name' => 'email', 'name' => 'email', 'type' => 'string', 'validate' => 'required|unique'],
    ];
});

test('callback can reject a row', function () {
    $csv = <<<CSV
        name,email
        John,john@example.com
        Jane,jane@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $callback = new class {
        public function rejectJane($row, $rowIndex) {
            if ($row['name'] === 'Jane') {
                return ['status' => false, 'column_errors' => ['Jane is not allowed']];
            }
        }
    };
    $reader->setCallback('rejectJane', $callback);
    $result = $reader->read($file);
    print_r($result);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain('Jane is not allowed');
    unlink($file);
});

test('callback can modify a row', function () {
    $csv = <<<CSV
        name,email
        John,john@example.com
        Jane,jane@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $callback = new class {
        public function uppercaseName($row, $rowIndex) {
            $row['name'] = strtoupper($row['name']);
            $row['email'] = strtolower($row['email']);
            return ['status' => true, 'row' => $row];
        }
    };
    $reader->setCallback('uppercaseName', $callback);
    $result = $reader->read($file);
    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'][0]['name'])->toBe('JOHN');
    expect($result['rows_processed'][1]['name'])->toBe('JANE');
    unlink($file);
});

test('callback can skip a row by returning skip', function () {
    $csv = <<<CSV
        name,email
        John,john@example.com
        SkipMe,skip@example.com
        Jane,jane@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $callback = new class {
        public function skipRow($row, $rowIndex) {
            if ($row['name'] === 'SkipMe') {
                return ['status' => false, 'skip' => true];
            }
            return ['status' => true];
        }
    };
    $reader->setCallback('skipRow', $callback);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    $names = array_column($result['rows_processed'], 'name');
    expect($names)->not->toContain('SkipMe');
    expect(count($result['rows_processed']))->toBe(2);
    expect($result['csv_file_input_type'])->toBe('file_path');
    unlink($file);
});