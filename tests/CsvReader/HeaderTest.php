<?php

use Jletrondo\CsvReader\CsvReader;

beforeEach(function () {
    // Default columns for most tests
    $this->columns = [
        ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'validate' => 'required'],
        ['column_name' => 'email', 'name' => 'email', 'type' => 'string', 'validate' => 'required|unique'],
    ];
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