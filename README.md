# CsvReader Library

## Overview
The CsvReader library is a PHP class designed to facilitate the reading and processing of CSV files. It provides various features for handling CSV data, including validation, error handling, and customizable processing through callback functions.

## Motivation

I created the CsvReader library to simplify and standardize the process of reading and validating CSV files in my PHP projects. After encountering repetitive issues with inconsistent CSV formats and error handling, I wanted a reusable solution that could be easily customized and extended for different use cases.

## Namespace
```php
namespace Jletrondo\CsvReader;
```

## Installation
To use the CsvReader library, include the file in your project and ensure that you have the necessary dependencies installed. You can install it via Composer:

```bash
composer require jletrondo/csv-reader
```

## Usage
### Creating an Instance
To use the CsvReader library, include the following use statement at the top of your PHP file:
```php
use Jletrondo\CsvReader\CsvReader;
```
You can create an instance of the CsvReader class by passing optional parameters to the constructor.

```php
$csvReader = new CsvReader($params);
```

### Parameters
- **$params**: An associative array of optional parameters to initialize the CsvReader. Common parameters include:
  - `delimiter`: Character used to separate values in the CSV file (default: `,`).
  - `enclosure`: Character used to enclose values in the CSV file (default: `"`).
  - `escape`: Character used to escape special characters (default: `\`).
  - `has_header`: Indicates if the CSV file has a header row (default: `true`).
  - `columns`: Array of required column names for validation.
  - `error_threshold`: If the error count exceeds this threshold, the CSV reader stops reading the data (default: `1000`).

### Reading a CSV File
To read a CSV file, use the `read` method. You can also provide a callback function to process each row.

```php
$results = $csvReader->read('path/to/your/file.csv', $callback);
```

### Callback Function

To perform custom validation or processing on each row of your CSV, you can define a callback function. This callback should accept two parameters:

- `$row`: An associative array representing the current row's data.
- `$index`: The row index (starting from 1).

The callback must return either:
- `true` if the row is valid and should be processed, or
- an associative array with a `'status'` key set to `false` and a `'column_errors'` key containing an array of error messages if the row is invalid.

**Important:** You must set the callback before calling `CsvReader::read()`. Use the following method to register your callback:

#### Using the Callback for custom validations
```php
$this->reader->set_callback(string $method, object $context);
```

- Here, `$method` refers to the name of the function that will be invoked on the `$context` object for each row during CSV processing.

```php
public function custom_validation($row, $index) 
{
    // Custom validations must return an associative array 
    return [
        'status' => false, // true means no errow in the row
        'column_errors' => [] // if row has error, this is the array where error message is stored.
    ];
}
```

> **Note:** Custom validations via callback are optional. If you do not set a callback, only the built-in column validations (as defined in your `columns` array) will be applied.


### Column Validations

Each column definition in the `columns` parameter supports the following options:

- **name** (required): The key name for the value in the resulting associative array.
- **column_name** (required): The header name as it appears in the CSV file.
- **type** (optional): The expected data type. Supported types: `string`, `integer`, `float`, `boolean`, `date`.
- **min_length** (optional): The minimum allowed length for the value.
- **max_length** (optional): The maximum allowed length for the value.
- **validate** (optional): Pipe-separated validation rules. Supported rules: `required`, `lowercase`, `uppercase`, `strip_tags`. (More validations will be added soon.)
- **unique** (optional): `true` or `false`. If `true`, the column will be checked for duplicate values.
- **required** (optional): `true` or `false`. If `true`, the column must have a value.

Example column definition:

### Example
```php
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
                'max_length'  => 7, 
                'validate'    => 'uppercase'
            ],
            [
                'name'        => 'name', 
                'column_name' => 'fullname',
                'type'        => 'string',
                'max_length'  => 20, 
                'validate'    => 'required|uppercase'
            ],
            [
                'name'        => 'bdate',
                'column_name' => 'birth date',
                'type'        => 'date',
                'max_length'  => 20,
                'validate'    => 'required|uppercase'
            ],
            [
                'name'        => 'status',
                'column_name' => 'active',
                'type'        => 'string',
                'max_length'  => 20,
                'validate'    => 'required|uppercase'
            ],
        ];

        $this->reader = new CsvReader([
            'columns' => $this->columns
        ]);

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

        if (empty($row['birth date'])) {
            $errors[] = "Birth Date is required. : (" . $row['birth date'] . ")";
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
```

## Error Handling
The library tracks errors encountered during the reading process. If errors occur, they are stored in an array, and an error file can be generated for review.

## Methods
### `initialize(array $params)`
Initializes the CsvReader with given parameters.

### `setDelimiter(string $delimiter)`
Sets the delimiter for CSV.

### `setEnclosure(string $enclosure)`
Sets the enclosure character for CSV.

### `setEscape(string $escape)`
Sets the escape character for CSV.

### `setHasHeader(bool $has_header)`
Sets whether the CSV has a header row.

### `setColumns(array $columns)`
Sets the required columns for validation.

### `setDirectoryPath(string $directory_path)`
Sets the directory path for error files.

### `setFileName(string $file_name)`
Sets the name of the error file.

### `setErrorCountThreshold(int $error_threshold)`
Sets the error count threshold.

### `storeErrorRows(array $error_rows, array $header)`
Stores rows with errors into a CSV file.

## Conclusion
The CsvReader library provides a robust solution for reading and processing CSV files in PHP. With its validation features and customizable processing, it is suitable for various applications that require CSV data handling.

## License
This library is licensed under the MIT License.
