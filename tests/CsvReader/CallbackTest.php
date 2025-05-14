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
    $reader->set_callback('rejectJane', $callback);
    $result = $reader->read($file);

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
            return ['status' => true, 'row' => $row];
        }
    };
    $reader->set_callback('uppercaseName', $callback);
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
    $reader->set_callback('skipRow', $callback);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    $names = array_column($result['rows_processed'], 'name');
    expect($names)->not->toContain('SkipMe');
    expect(count($result['rows_processed']))->toBe(2);
    unlink($file);
});

test('processes date type column correctly', function () {
    $csv = <<<CSV
        name,email,birthdate
        John Doe,john@example.com,1993-05-15
        Jane Smith,jane@example.com,12/26/1992
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'validate' => 'required'],
        ['column_name' => 'email', 'name' => 'email', 'type' => 'string', 'validate' => 'required|unique'],
        ['column_name' => 'birthdate', 'name' => 'birthdate', 'type' => 'date', 'validate' => 'required'],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $reader->setIsDownloadable(true);
    $result = $reader->read($file);

    print_r($result);

    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'])->toHaveCount(2);

    expect($result['rows_processed'][0]['birthdate'])->toBe('05/15/1993');
    expect($result['rows_processed'][1]['birthdate'])->toBe('12/26/1992');

    unlink($file);
});