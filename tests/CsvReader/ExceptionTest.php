<?php

use Jletrondo\CsvReader\CsvReader;

beforeEach(function () {
    // Default columns for most tests
    $this->columns = [
        ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'validate' => 'required'],
        ['column_name' => 'email', 'name' => 'email', 'type' => 'string', 'validate' => 'required|unique'],
    ];
});

test('custom validation exception error is returned', function () {
    $csv = <<<CSV
        name,email
        Jane,jane@examplecom
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $callback = new class {
        public function exceptionValidation($row, $rowIndex) {
            $exceptionmMsg = "";
            $errors = [];
            try {
                if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email.');
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                $exceptionmMsg = $e->getMessage();
            }

            return [
				'status' => empty($errors),
				'column_errors' => $errors ?? [],
                'exception' => $exceptionmMsg
		    ];
        }
    };
    $reader->setCallback('exceptionValidation', $callback);
    $result = $reader->read($file);

    print_r($result);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect(!empty($result['exception']))->toBeTrue();
    expect($result['exception'][0]['message'])->toContain('Invalid email');
    unlink($file);
});