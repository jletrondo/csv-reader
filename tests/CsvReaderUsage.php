<?php

require 'vendor/autoload.php';

use Jletrondo\CsvReader\CsvReader;

class CsvProcessor
{
    private $columns;
    private $reader;

    public function __construct()
    {
        $this->columns = [
            [
                'name'        => 'company', 
                'column_name' => 'company', 
                'type'        => 'string', 
                'min_length'  => 5,
                'max_length'  => 7,
                'validate'    => 'uppercase'
            ],
            [
                'name'        => 'name', 
                'column_name' => 'fullname',
                'type'        => 'string',
                'max_length'  => 15, 
                'validate'    => 'required|uppercase'
            ],
            [
                'name'        => 'bdate',
                'column_name' => 'birth date',
                'type'        => 'date',
                'max_length'  => 11,
                'validate'    => 'required|uppercase'
            ],
            [
                'name'        => 'status',
                'column_name' => 'active',
                'type'        => 'string',
                'max_length'  => 3,
                'allowed_values' => ['YES', 'NO'],
                'validate'    => 'required|uppercase'
            ],
        ];

        $this->reader = new CsvReader([
            'columns' => $this->columns
        ]);

        $this->reader->setIsDownloadable(true);

        // $this->reader->initialize([
        //     'columns' => $this->columns
        // ]);
    }

    public function process()
    {
        try {
            $this->reader->set_callback('custom_validation', $this);
            $result = $this->reader->read(__DIR__ . '/file.csv');
            print_r($result);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function custom_validation($row, $index) 
    {
        $errors = [];

        // check if company exists
        if ($row['company'] !== 'NWJS') {
            $errors[] = "Company doesn't exists in the system. ('" . $row['company'] . "')";
        }



        return [
            'status' => empty($errors),
            'column_errors' => $errors ?? []
        ];
    }
}

// Create an instance of CsvProcessor and process the CSV file
$csvProcessor = new CsvProcessor();
$csvProcessor->process();
