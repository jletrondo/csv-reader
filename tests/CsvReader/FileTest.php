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