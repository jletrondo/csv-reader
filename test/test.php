<?php

require 'vendor/autoload.php';

use Jletrondo\CsvReader\CsvReader;

$columns = [
    [
        'name'        => 'centercode', 
        'column_name' => 'company', 
        'type'        => 'string', 
        'max_length'  => 7, 
        'validate'    => 'required|to_upper'
    ],
    [
        'name'        => 'classification_code', 
        'column_name' => 'payroll classification', 
        'type'        => 'string',
        'max_length'  => 20, 
        'validate'    => 'required|to_upper'
    ],
    [
        'name'        => 'companycode',
        'column_name' => 'costcenter',
        'type'        => 'string',
        'max_length'  => 20,
        'validate'    => 'required|to_upper'
    ],
	[
        'name'        => 'clntcode',
        'column_name' => 'division',
        'type'        => 'string',
        'max_length'  => 20,
        'validate'    => 'required|to_upper'
    ],
];

$reader = new CsvReader([
    'columns' => $columns
]);

try {
    
    $result = $reader->read(__DIR__ . '/file.csv');
    print_r($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}