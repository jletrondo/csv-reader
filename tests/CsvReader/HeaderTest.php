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

test('should still accept headers with parenthesis eg. birthday(M/D/Y)', function () {
    $csv = <<<CSV
        name,birthday(M/D/Y)
        John,3/16/2000
        Jane,5/15/2001
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'name',
            'name' => 'name',
            'type' => 'string',
            'validate' => 'required',
        ],
        [
            'column_name' => 'birthday',
            'name' => 'birthday',
            'type' => 'date',
            'validate' => 'required',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);
    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'])->toHaveCount(2);
    expect($result['rows_processed'][0]['birthday'])->toBe('03/16/2000');
    expect($result['rows_processed'][1]['birthday'])->toBe('05/15/2001');
    unlink($file);
});

test('should still work even without headers', function () {
    $csv = <<<CSV
        John,john@example.com
        Jane,jane@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['has_header' => false]);
    $result = $reader->read($file);
    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'])->toHaveCount(2);
    expect($result['rows_processed'][0]['row'])->toBe(1);
    expect($result['rows_processed'][0]['data'][0])->toBe('John');
    expect($result['rows_processed'][0]['data'][1])->toBe('john@example.com');
    expect($result['rows_processed'][1]['row'])->toBe(2);
    expect($result['rows_processed'][1]['data'][0])->toBe('Jane');
    expect($result['rows_processed'][1]['data'][1])->toBe('jane@example.com');
    unlink($file);
});

test('should process CSV data correctly even when there are empty rows and no headers', function () {
    $csv = <<<CSV
            John,john@example.com

            Jane,jane@example.com
            ,
            CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['has_header' => false]);
    $result = $reader->read($file);

    // Check if the status is true, indicating successful processing
    expect($result['status'])->toBeTrue();
    
    // Verify the first row of data
    expect($result['rows_processed'][0]['row'])->toBe(1);
    expect($result['rows_processed'][0]['data'][0])->toBe('John');
    expect($result['rows_processed'][0]['data'][1])->toBe('john@example.com');
    
    // Verify the second row of data
    expect($result['rows_processed'][1]['row'])->toBe(3);
    expect($result['rows_processed'][1]['data'][0])->toBe('Jane');
    expect($result['rows_processed'][1]['data'][1])->toBe('jane@example.com');
    
    // Check overall processing results
    expect($result['processed'])->toBe(2);
    expect($result['error_count'])->toBe(0);
    expect($result['skipped_rows'])->toHaveCount(2); // Expect one empty row to be recorded as an error
    
    unlink($file);
});


test('should process CSV header by removing extra spaces between words', function () {
    $csv = <<<CSV
            name,email,referred  by
            John,john@example.com,
            Jane,jane@example.com,
            CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'name',
            'name' => 'name',
            'type' => 'string',
            'validate' => 'required',
        ],
        [
            'column_name' => 'email',
            'name' => 'email',
            'type' => 'string',
            'validate' => 'required',
        ],
        [
            'column_name' => 'referred by',
            'name' => 'referred_by',
            'type' => 'string',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    // Check if the status is true, indicating successful processing
    expect($result['status'])->toBeTrue();
    // expect($result['error'])->toContain('missing the following columns');
    // expect($result['error'])->toContain('not defined in the system');

    unlink($file);
});

