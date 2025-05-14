<?php

use Jletrondo\CsvReader\CsvReader;

beforeEach(function () {
    // Default columns for most tests
    $this->columns = [
        ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'validate' => 'required'],
        ['column_name' => 'email', 'name' => 'email', 'type' => 'string', 'validate' => 'required|unique'],
    ];
});

test('returns empty processed rows for empty file', function () {
    $csv = '';
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse(); // should return false cause csv is empty.
    expect(is_array($result['rows_processed']))->toBeTrue();
    expect($result['rows_processed'])->toBeEmpty();
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
