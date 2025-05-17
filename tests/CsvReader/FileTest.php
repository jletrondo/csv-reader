<?php

use Jletrondo\CsvReader\CsvReader;

beforeEach(function () {
    // Default columns for most tests
    $this->columns = [
        ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'validate' => 'required'],
        ['column_name' => 'email', 'name' => 'email', 'type' => 'string', 'validate' => 'required|unique'],
    ];
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

test('stores error rows in custom directory path', function () {
    // Create a CSV file with an invalid row (invalid birthdate)
    $csvContent = <<<CSV
            name,birthdate
            John,02/33/2000
            Dave,26/07/1992
            CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csvContent);

    $reader = new CsvReader([
        'columns' => [
            ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'validate' => 'required'],
            ['column_name' => 'birthdate', 'name' => 'birthdate', 'type' => 'date', 'validate' => 'required|date'],
        ],
    ]);
    $reader->setDirectoryPath('tests/custom_errors/');
    $reader->setFileName('errors.csv');
    $reader->setIsDownloadable(true);

    $result = $reader->read($file);

    // The error CSV should be created in the custom directory
    $customErrorDir = $reader->getDirectoryPath();
    $errorCsvPath = $reader->getErrorFileName();
    expect(is_dir($customErrorDir))->toBeTrue();
    expect(file_exists($customErrorDir . $errorCsvPath))->toBeTrue();

    // Check that the error CSV contains the invalid row
    $errorRows = array_map('str_getcsv', file($customErrorDir . $errorCsvPath));
    // The first row is the header, the second row is the invalid row
    expect($errorRows[1][0])->toBe('John');
    expect($errorRows[1][1])->toBe('02/33/2000');
    expect($errorRows[2][0])->toBe('Dave');

    // Clean up
    unlink($file);
    unlink($customErrorDir . $errorCsvPath);
});

test('handles uploaded file from $_FILES', function () {
    // Create a CSV file with test data
    $csvContent = <<<CSV
    name,email
    John Doe,john@example.com
    Jane Smith,jane@example.com
    CSV;
    
    // Create a temporary file to simulate upload
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($tempFile, $csvContent);
    
    // Simulate $_FILES array
    $files = [
        'tmp_name' => $tempFile,
        'name' => 'test.csv',
        'type' => 'text/csv',
        'size' => filesize($tempFile),
        'error' => 0
    ];
    
    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($files);
    
    // Verify the results
    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'])->toHaveCount(2);
    expect($result['rows_processed'][0]['name'])->toBe('John Doe');
    expect($result['rows_processed'][0]['email'])->toBe('john@example.com');
    expect($result['rows_processed'][1]['name'])->toBe('Jane Smith');
    expect($result['rows_processed'][1]['email'])->toBe('jane@example.com');
    
    // Clean up
    unlink($tempFile);
});

test('handles reading from a file stream', function () {
    // Create a CSV file with test data
    $csvContent = <<<CSV
    name,email
    John Doe,john@example.com
    Jane Smith,jane@example.com
    CSV;
    
    // Create a temporary file to simulate stream
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($tempFile, $csvContent);
    
    // Open the file as a stream
    $stream = fopen($tempFile, 'r');
    
    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($stream);
    
    // Verify the results
    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'])->toHaveCount(2);
    expect($result['rows_processed'][0]['name'])->toBe('John Doe');
    expect($result['rows_processed'][0]['email'])->toBe('john@example.com');
    expect($result['rows_processed'][1]['name'])->toBe('Jane Smith');
    expect($result['rows_processed'][1]['email'])->toBe('jane@example.com');
    
    // Clean up
    unlink($tempFile);
});


