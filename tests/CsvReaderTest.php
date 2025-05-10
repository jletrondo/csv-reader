<?php

use Jletrondo\CsvReader\CsvReader;

beforeEach(function () {
    // Default columns for most tests
    $this->columns = [
        ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'required' => true],
        ['column_name' => 'email', 'name' => 'email', 'type' => 'string', 'required' => true, 'unique' => true],
    ];
});

test('fails if required column is missing', function () {
    $csv = <<<CSV
        name
        John
        Jane
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse();
    expect($result['error'])->toContain('missing the following columns');
    unlink($file);
});

test('fails if extra column is present', function () {
    $csv = <<<CSV
        name,email,age
        John,john@example.com,30
        Jane,jane@example.com,25
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader();
    $reader->initialize(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse();
    expect($result['error'])->toContain('contains columns that are not defined');
    unlink($file);
});

test('fails if duplicate headers are present', function () {
    $csv = <<<CSV
        name,email,email
        John,john@example.com,john2@example.com
        Jane,jane@example.com,jane2@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse();
    expect($result['error'])->toContain('Duplicate headers found');
    unlink($file);
});

test('fails if required field is empty', function () {
    $csv = <<<CSV
        name,email
        John,
        Jane,jane@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain('Required column');
    unlink($file);
});

test('fails if unique constraint is violated', function () {
    $csv = <<<CSV
        name,email
        John,john@example.com
        Jane,john@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain('Duplicate found');
    unlink($file);
});

test('validates type: integer', function () {
    $columns = [
        [
            'column_name' => 'id', 
            'name' => 'id', 
            'type' => 'integer', 
            'required' => true
        ],
    ];
    $csv = <<<CSV
        id
        123
        abc
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain('Invalid type');
    unlink($file);
});

test('returns empty processed rows for empty file', function () {
    $csv = <<<CSV
            CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse(); // should return false cause csv is empty.
    expect($result['rows_processed'])->toBeArray();
    expect($result['rows_processed'])->toBeEmpty();
    unlink($file);
});

test('fails if file is not a valid csv', function () {
    $nonCsvContent = "This is not a CSV file.\nJust some random text.";
    $file = tempnam(sys_get_temp_dir(), 'notcsv_');
    file_put_contents($file, $nonCsvContent);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse();
    expect($result['error'])->toContain('not a CSV'); // Adjust this string to match your actual error message
    unlink($file);
});

test('fails if column definition (name) is missing ', function () {
    $columns = [
        [
            'column_name' => 'id',
            // 'name' => 'id', // this is required
            'type' => 'integer', 
            'required' => true
        ],
    ];

    $csv = <<<CSV
        name,email
        John,john@example.com
        Jane,john@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse();
    expect($result['error'])->toContain('Column definition error');
    unlink($file);
});

test('returns empty processed rows for file with only header', function () {
    $csv = "name,email\n";
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'])->toBeArray();
    expect($result['rows_processed'])->toBeEmpty();
    unlink($file);
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
            return ['status' => true];
        }
    };
    $reader->set_callback('rejectJane', $callback);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain('Jane is not allowed');
    unlink($file);
});

test('fails when CSV contains extra headers not defined in columns', function () {
    $csv = <<<CSV
        name,email,unexpected_header
        John,john@example.com,surprise
        Jane,jane@example.com,extra
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse();
    expect($result['error'])->toContain('contains columns that are not defined');
    unlink($file);
});
