<?php

namespace Jletrondo\CsvReader;

use DateTime;
use Exception;

class CsvReader
{
    /**
     * @var callable|null $callback
     * Callback function to process each row after validation.
     * The function should accept two parameters: the associative row array and the row index.
     */
    private $callback;

    /**
     * @var object|null $callback_context
     * The context (object instance) in which the callback function is executed.
     * This allows the callback to access properties and methods of the given object.
     */
    private $callback_context;

    /**
     * @var string $delimiter
     * The character used to separate values in the CSV file.
     * Default is a comma (',').
     */
    private $delimiter = ',';

    /**
     * @var string $enclosure
     * The character used to enclose values in the CSV file.
     * Default is a double quote ('"').
     */
    private $enclosure = '"';

    /**
     * @var string $escape
     * The character used to escape special characters in the CSV file.
     * Default is a backslash ('\\').
     */
    private $escape = '\\';

    /**
     * @var bool $has_header
     * Indicates whether the CSV file contains a header row.
     * If true, the first row is treated as the header.
     */
    private $has_header = true;

    /**
     * @var int $header_count
     * The number of headers detected in the CSV file.
     * This is set after reading the header row.
     */
    private $header_count = 0;

    /**
     * @var string $mojibake_pattern
     * Regular expression pattern to detect mojibake (garbled text due to encoding issues)
     * in CSV values. Used for validation in validateColumns().
     */
    private $mojibake_pattern = '/Ã|Â|â€“|â€œ|â€|Ã©|ÿþ|þÿ|â€™|â€”|â€¦|â€˜|â€¢|â„¢|âˆ’|âˆž|â‚¬|â„—|â€º|â€¹/';

    /**
     * @var array $columns
     * Array of required column names for validation.
     * Ensure that the column names defined here match exactly with the headers in the CSV file.
     */
    private $columns = [];

    /**
     * @var array $unique_values
     * Array to track unique values for validation purposes (e.g., to enforce uniqueness constraints).
     * The structure and usage depend on the validation logic implemented.
     */
    private $unique_values = [];

    /**
     * @var int $error_threshold
     * The maximum number of errors allowed before the CSV reader stops processing further rows.
     * Default is 1000.
     */
    private $error_threshold = 1000;

    /**
     * @var array $results
     * Associative array holding the results of the CSV reading and validation process.
     * - status: (bool) Overall status of the operation.
     * - error: (string) Error message, if any.
     * - processed: (int) Number of successfully processed rows.
     * - errors: (array) List of error messages and their corresponding row indices.
     * - rows_processed: (array) List of successfully processed rows.
     * - rows_with_errors: (array) List of rows that failed validation or processing.
     * - total_error_rows: (int) Number of rows with errors.
     * - error_count: (int) Total number of errors across all rows and columns.
     */
    private $results = [
        'status' => false,
        'error' => "",
        'processed' => 0,
        'errors' => [],
        'rows_processed' => [],
        'rows_with_errors' => [],
        'total_error_rows' => 0,
        'error_count' => 0
    ];

    /**
     * Indicates whether the library should generate a downloadable CSV file
     * containing rows that failed validation or processing.
     *
     * @var bool
     */
    private $is_downloadable = false;

    /**
     * The directory path where the error CSV file (containing invalid rows)
     * will be stored if $is_downloadable is set to true.
     *
     * @var string
     */
    private $directory_path = 'test/errors/';

    /**
     * The filename to use for the generated CSV file containing rows with errors.
     *
     * @var string
     */
    private $file_name = "rows_with_errors.csv";
    

    /**
     * Constructor to initialize the CsvReader with optional parameters.
     *
     * @param array $params Optional parameters to initialize the CsvReader.
     */
    public function __construct($params = [])
    {
        // $this->CI =& get_instance();
        // $this->CI->load->helper('custom_helper'); // Load your custom helper
        if (!empty($params)) {
            $this->initialize($params);
        }
    }

    /**
     * Initialize the Csv_reader with given parameters.
     *
     * @param array $params Parameters to set properties of the Csv_reader.
     */
    public function initialize(array $params = []): void
    {
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function test()
    {
        echo validate_date('05/15/1993');
    }

    // Setters for the properties
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter; // Set the delimiter for CSV
    }

    public function setEnclosure($enclosure): void
    {
        $this->enclosure = $enclosure; // Set the enclosure character for CSV
    }

    public function setEscape($escape): void
    {
        $this->escape = $escape; // Set the escape character for CSV
    }

    public function setHasHeader(bool $has_header): void
    {
        $this->has_header = $has_header; // Set whether the CSV has a header row
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns; // Set columns
    }

    public function setDirectoryPath(string $directory_path): void
    {
        $this->directory_path = rtrim($directory_path, '/') . '/'; // Ensure the path ends with a slash
    }

    public function getDirectoryPath(): string
    {
        return $this->directory_path; // Get the directory path for error files
    }

    public function setFileName(string $file_name): void
    {
        $this->file_name = $file_name; // Set the name of the error file
    }

    public function getErrorFileName(): string
    {
        return $this->file_name; // Get the name of the error file
    }

    public function setErrorCountThreshold(int $error_threshold): void 
    {
        $this->error_threshold = $error_threshold;
    }

    public function setIsDownloadable(bool $is_downloadable): void
    {
        $this->is_downloadable = $is_downloadable;
    }

    public function getIsDownloadable(): bool
    {
        return $this->is_downloadable;
    }
    
    /**
     * Set a callback method for processing each row.
     *
     * This method allows the user to define a custom callback function that will be called for each row
     * read from the CSV file. The callback function should accept two parameters: the row data as an associative
     * array and the row index (starting from 1). The callback should return true if the row is valid and should
     * be processed, or an associative array with a 'status' key set to false and a 'message' key for error
     * reporting if the row is invalid.
     *
     * @param string $method The name of the callback method.
     * @param object $context The context (object) in which the callback method exists.
     * @throws Exception If the callback method does not exist in the provided context.
     */
    public function set_callback(string $method, object $context): void
    {
        if (method_exists($context, $method)) {
            $this->callback = $method; // Assign the callback method
            $this->callback_context = $context; // Assign the context for the callback
        } else {
            throw new Exception("Callback method {$method} does not exist."); // Throw exception if method does not exist
        }
    }

    /**
     * Read a CSV file directly from its path.
     *
     * @param string $file_path Path to the CSV file.
     * @param callable|null $callback Optional callback function to process each row.
     *                                The function should accept two parameters: the row data and the row index.
     *                                It should return a boolean indicating whether to process the row.
     * @return array An associative array containing the status of the read operation, 
     *               number of processed rows, and any errors encountered.
     */
    public function read(string $file_path, ?callable $callback = null): array
    {
        $rowIndex = 0; // Initialize row index
        $header = []; // Initialize header array
        $errorCount = 0; 
        $totalErrorRows = 0; // Total count of error rows

        if (!file_exists($file_path)) {
            $this->results['error'] = 'File does not exist: ' . $file_path;
            return $this->results; // Return error if file does not exist
        }

        if(!$this->isValidCsvFile($file_path)) {
            $this->results['error'] = 'Uploaded file is not a CSV. Please upload a valid CSV file.';
            return $this->results;
        }

        $handle = fopen($file_path, 'r'); // Open the CSV file for reading
        if ($handle === false) {
            $this->results['error'] = 'Failed to open file.'; // Return error if file cannot be opened
            return $this->results;
        }

        /* 
            Removes BOM if present.
            This is for the library to be compatible with CSV UTF-8.
            The difference between CSV UTF-8 and plain CSV (especially when saved from Excel) often lies in invisible characters at the beginning of the file — 
            specifically, the UTF-8 BOM (Byte Order Mark).
        */
        // Detect encoding and convert if necessary
        $firstBytes = fread($handle, 3);
        if ($firstBytes === "\xEF\xBB\xBF") {
            // UTF-8 BOM detected
            fseek($handle, 0); // Reset file pointer
        } else {
            // Assume it's a different encoding (e.g., ISO-8859-1) and convert to UTF-8
            $content = file_get_contents($file_path);
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
            $handle = fopen('php://temp', 'r+'); // Open a temporary file
            fwrite($handle, $content); // Write converted content to temporary file
            rewind($handle); // Reset file pointer
        }
        
        // Step 1: Precompile column mappings (Done once, not for every row)
        $columnNameToKey = [];
        foreach ($this->columns as $column) {
            $headerName = $column['column_name'] ?? null;
            $key = $column['name'] ?? null;
            if (empty($headerName) || empty($key)) {
                fclose($handle);
                $this->results['status'] = false;
                $this->results['error'] = "Column definition error: Each column must have both a 'column_name' (the CSV header) and a 'name' (the key for the resulting array). Please review your column definitions and ensure these fields are present for every column.";
                return $this->results;
            }
            $columnNameToKey[$headerName] = $key;
        }

        // Main loop to read rows from CSV
        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $rowIndex++; // Increment row index

            // Exit if too may errors
            if($errorCount > $this->error_threshold) {
                $this->results['status'] = true;
                $this->results['error_count'] = $errorCount;
                $this->results['error'] = "The uploaded file has too many errors. Please take time to review and try again.";
                $this->results['total_error_rows'] = $totalErrorRows;
                return $this->results;
            }

            if ($rowIndex === 1 && $this->has_header) {
                // Remove BOM and trim headers, then filter out empty values
                $header = array_filter(array_map(function($value) {
                    return trim($this->removeBom($value));
                }, $row), function($v) { return $v !== ''; });

                // Get the expected column names and filter out empty values
                $expectedColumns = array_filter(array_column($this->columns, 'column_name'), function($v) { return $v !== ''; });
                $missingColumns = array_diff($expectedColumns, $header); // Find missing columns
                $extraColumns = array_diff($header, $expectedColumns); // Find extra columns
                $duplicateHeaders = $this->checkForDuplicateHeaders($header);

                $errorMsg = "";
                if (!empty($missingColumns)) {
                    $errorMsg .= "The uploaded file is missing the following columns:" . custom_list_ul($missingColumns, ['style' => 'color: red;']);
                }
                if (!empty($extraColumns)) {
                    $errorMsg .= "The uploaded file contains columns that are not defined in the system:" . custom_list_ul($extraColumns, ['style' => 'color: red;']);
                }
                if (!empty($duplicateHeaders)) {
                    $errorMsg .= "Duplicate headers found: " . custom_list_ul($duplicateHeaders, ['style' => 'color: red;']);
                }
                if (empty($errorMsg) && count($header) !== count($expectedColumns)) {
                    if (count($this->columns) > count($header)) {
                        $errorMsg = "The uploaded file does not match the expected column structure. Some headers may be missing. Please review the file and try again.";
                    } else {
                        $errorMsg = "The uploaded file does not match the expected column structure. Extra headers may be present. Please review the file and try again.";
                    }
                }

                if (!empty($errorMsg)) {
                    fclose($handle);

                    $this->results['status'] = false;
                    $this->results['error'] = $errorMsg;
                    return $this->results;
                }

                // Count non-empty header fields
                $this->header_count = count(array_filter($header, fn($value) => $value !== '' && $value !== null));

                continue; // Skip processing this header row
            }

            // Build the assoc_row with precompiled mapping and trim whitespaces
            $assoc_row = $this->has_header ? array_map('trim', array_combine($header, $row)) : array_map('trim', $row);

            // Validate columns
            $validationResult = $this->validateColumns($assoc_row, $rowIndex);

            if (!$validationResult['status']) {
                for ($i = 0; $i < count($validationResult['errors']); $i++) {
                    $error = $validationResult['errors'][$i];
                    $errorCount++;
                    $this->results['errors'][] = ['message' => $error, 'row' => $rowIndex]; // Log error message and row index
                    $this->results['rows_with_errors'][] = $row; // Add row to error rows
                }
                $totalErrorRows++;
                continue; // Skip to the next row
            }

            // Step 3: Process row using the callback if defined
            if ($this->callback && $this->callback_context) {
                $callBackResult = call_user_func_array(
                    [$this->callback_context, $this->callback],
                    [$assoc_row, $rowIndex]
                );

                if (isset($callBackResult['status']) && $callBackResult['status'] === false) {
                    // Handle errors returned from the callback
                    $this->results['rows_with_errors'][] = $assoc_row; // Add the associative row to error rows

                    // If there are column-specific errors, add them to the results
                    if (!empty($callBackResult['column_errors'])) {
                        for ($i = 0; $i < count($callBackResult['column_errors']); $i++) {
                            $errorCount++;
                            $error = $callBackResult['column_errors'][$i];
                            $this->results['errors'][] = ['message' => $error, 'row' => $rowIndex];
                        }
                    }
                    $totalErrorRows++;
                    continue; // Skip to the next row
                }
            }

            // Step 4: Build processed row with 'name' instead of 'column_name'
            // to be ready for the insertion of the data in the database or further processing.
            $processedRow = [];
            for ($i = 0; $i < count($assoc_row); $i++) {
                $headerName = array_keys($assoc_row)[$i];
                $value = $assoc_row[$headerName];
                if (isset($columnNameToKey[$headerName])) {
                    $processedRow[$columnNameToKey[$headerName]] = $value;
                }
            }

            $this->results['rows_processed'][] = $processedRow; // Add processed row to results
            $this->results['processed']++; // Increment processed row count
        }

        fclose($handle); // Close the file handle
        $this->results['status'] = true; // Set status to true
        $this->results['total_error_rows'] = $totalErrorRows; // Count total error rows
        $this->results['error_count'] = count($this->results['errors']); // The sum of all errors in all columns

        if ($this->is_downloadable) {
            $this->storeErrorRows($this->results['rows_with_errors'], $header); // Store error rows in a CSV file
            $this->results['downloadable'] = $this->is_downloadable; // Set downloadable status
            unset($this->results['rows_processed']);
        }

        return $this->results; // Return the results of the read operation
    }

    /**
    * Check for duplicate headers in the CSV file.
    *
    * This method verifies that there are no duplicate header names in the provided
    * header array. Duplicate headers can cause issues when mapping data to columns
    * as it can create ambiguity in which column some data might belong to.
    *
    * @param array $header The header array to check for duplicates.
    * @return array|bool Returns an array of duplicate header names if found, or false if no duplicates are present.
    */
    private function checkForDuplicateHeaders(array $header): array
    {
        $headerCounts = array_count_values($header); // Count occurrences of each header
        $duplicates = [];
        
        // Identify headers with more than one occurrence
        foreach ($headerCounts as $headerName => $count) {
            if ($count > 1) {
                $duplicates[] = $headerName;
            }
        }
        
        return $duplicates;
    }

    /**
     * Validate the columns of the given associative row.
     *
     * @param array $assoc_row The associative array representing the row data.
     * @param int $rowIndex The index of the row being validated.
     * @return array An associative array containing the validation status and any errors encountered.
     */
    private function validateColumns(array &$assoc_row, int $rowIndex): array
    {
        $errors = []; // Initialize errors array

        foreach ($this->columns as $column) {
            $column_name = $column['column_name']; // Get the column name
            $expected_type = $column['type'] ?? null; // Get the expected type
            $is_required = $column['required'] ?? false; // Check if the column is required
            $value_exists = isset($assoc_row[$column_name]) && $assoc_row[$column_name] !== ''; // Check if the value exists
            $encoding_error_msg = "Unknown or invalid character or possible encoding error found in column";

            $optional_validations = explode('|', $column['validate'] ?? '');

            if ($is_required || in_array('required', $optional_validations)) {
                // Required field check
                if (!$value_exists) {
                    $errors[] = "Required column '{$column_name}' is empty."; // Log error for empty required column
                    continue; // No need to check further if required field is empty
                }
            }

            // If value exists (whether required or not)
            if ($value_exists) {
                $value = $assoc_row[$column_name]; // Get the value for validation

                // 1. Unicode replacement character, disallowed symbols, and mojibake patterns
                $pattern = '/[\x{FFFD}□▯▢]/u';
                if (preg_match($pattern, $value) || preg_match($this->mojibake_pattern, $value)) {
                    $errors[] = "$encoding_error_msg '{$column_name}'.";
                }

                // 1. Type validation
                if (!empty($expected_type) && !$this->validateType($value, $expected_type)) {
                    if ($expected_type === 'date') {
                        if(validate_date($value)) {
                            $value = date('m/d/Y', strtotime($value));
                            $assoc_row[$column_name] = $value;
                        } else {
                            $errors[] = "Invalid date format in column '{$column_name}'. Please use one of the following formats: m/d/Y, m-d-Y, Y/m/d, or Y-m-d.";
                        }
                    } else {
                        $errors[] = "Invalid type for column '{$column_name}': expected {$expected_type}, got '{$value}'"; // Log error for invalid type
                    }
                }

                // 2. Length validation
                $errors = array_merge(
                    $errors,
                    $this->validateLength($value, $column_name, $column['min_length'] ?? null, $column['max_length'] ?? null) // Validate length
                );

                // 3. Unique validation
                $errors = array_merge(
                    $errors,
                    $this->checkForDuplicatesInUniqueColumns($value, $column, $rowIndex) // Check for duplicates
                );

                // 4. Allowed values validation
                if (isset($column['allowed_values']) && is_array($column['allowed_values'])) {
                    if (!in_array($value, $column['allowed_values'], true)) {
                        $allowed = implode("', '", $column['allowed_values']);
                        $errors[] = "Invalid value in column '{$column_name}': expected one of ['{$allowed}'], found '{$value}'.";
                    }
                }
            }

            // perform additional manipulation to the column values such as strtoupper, strip_tags, htmlentities etc. if set. in $column['change']
            if (isset($assoc_row[$column_name]) && is_string($assoc_row[$column_name])) {
                foreach ($optional_validations as $validation) {
                    switch ($validation) {
                        case 'lowercase':
                            $assoc_row[$column_name] = strtoupper($assoc_row[$column_name]);
                            break;
                        case 'uppercase':
                            $assoc_row[$column_name] = strtoupper($assoc_row[$column_name]);
                            break;
                        case 'strip_tags':
                            $assoc_row[$column_name] = strip_tags($assoc_row[$column_name]);
                            break;
                        case 'htmlentities':
                            $assoc_row[$column_name] = htmlentities($assoc_row[$column_name]);
                            break;
                        case 'strip_quotes':
                            $entry = str_replace('"', "", $assoc_row[$column_name]);
		                    $entry = str_replace("'", "", $assoc_row[$column_name]);
                            $assoc_row[$column_name] = $entry;
                            break;
                        case 'urlencode':
                            $assoc_row[$column_name] = urlencode($assoc_row[$column_name]);
                            break;
                    }
                }
            }
        }

        return empty($errors) ? ['status' => true] : ['status' => false, 'errors' => $errors]; // Return validation result
    }

    /**
     * Check for duplicates in unique columns.
     *
     * @param mixed $value The value to check for duplicates.
     * @param array $column The column definition containing the unique constraint.
     * @param int|null $current_row_number The current row number being processed.
     * @return array An array of error messages for duplicates found.
     */
    private function checkForDuplicatesInUniqueColumns(mixed $value, array $column, ?int $current_row_number = null): array 
    {
        $errors = []; // Initialize errors array

        // Check if the column has the 'unique' key set to true
        if (isset($column['unique']) && $column['unique'] === true) {
            $unique_column_name = $column['column_name']; // Get the unique column name

            // Initialize if not set
            if (!isset($this->unique_values[$unique_column_name])) {
                $this->unique_values[$unique_column_name] = []; // Initialize unique values array
            }

            // Check if the value is already tracked
            if (isset($this->unique_values[$unique_column_name][$value])) {
                $first_row = $this->unique_values[$unique_column_name][$value]; // Get the first row where the value was found
                $errors[] = "Duplicate found in column '{$unique_column_name}': {$value} (same with row {$first_row})"; // Log duplicate error
            } else {
                // If no duplicate, track this value and its current row number
                $this->unique_values[$unique_column_name][$value] = $current_row_number; // Track the current row number
            }
        }

        return $errors; // Return any duplicate errors found
    }

    /**
     * Validate the length of a value against minimum and maximum constraints.
     *
     * @param mixed $value The value to validate.
     * @param string $column_name The name of the column being validated.
     * @param int|null $min_length The minimum length constraint.
     * @param int|null $max_length The maximum length constraint.
     * @return array An array of error messages for length validation.
     */
    private function validateLength(mixed $value, string $column_name, ?int $min_length = null, ?int $max_length = null) {
        $errors = []; // Initialize errors array

        if (!is_null($value) && $value !== '') {
            $length = strlen((string)$value); // Get the length of the value

            if (!is_null($min_length) && $length < $min_length) {
                $errors[] = "The value in column '{$column_name}' is too short: Expected minimum length of {$min_length}, got {$length}"; // Log error for short value
            }

            if (!is_null($max_length) && $length > $max_length) {
                $errors[] = "The value in column '{$column_name}' is too long: Expected maximum length of {$max_length}, got {$length}"; // Log error for long value
            }
        }

        return $errors; // Return any length validation errors found
    }

    /**
     * Store rows with errors into a CSV file.
     *
     * This method creates a directory (if it does not already exist) and writes the provided error rows
     * into a CSV file. The first row of the CSV will contain the headers, and the file will be saved
     * with a UTF-8 BOM for compatibility with Excel.
     *
     * @param array $error_rows An array of rows that contain errors.
     * @param array $header An array of headers to be included in the CSV file.
     * @return array An associative array containing the path to the stored CSV file.
     */
    public function storeErrorRows(array $error_rows, array $header): array
    {
        // Create the directory if it does not exist
        if (!is_dir($this->directory_path)) {
            mkdir($this->directory_path, 0755, true); // Create directory with appropriate permissions
        }

        $outputPath = $this->directory_path . $this->file_name; // Define the path to store the CSV file
        $output = fopen($outputPath, 'w'); // Open the output file for writing

        // Add the original headers from the uploaded CSV with UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF"); // Add BOM
        fputcsv($output, $header); // Write headers to the CSV file

        foreach ($error_rows as $row) {
            if (isset($row)) { // Check if the index 3 exists
                fputcsv($output, $row); // Write each error row to the CSV file
            }
        }

        fclose($output); // Close the output file

        return ['file_path' => $outputPath]; // Return the path to the stored CSV file
    }

    /**
     * Validate the type of a value against the expected type.
     *
     * @param mixed $value The value to validate.
     * @param string $expectedType The expected type of the value.
     * @return bool True if the value matches the expected type, false otherwise.
     */
    private function validateType(mixed $value, string $expectedType): bool
    {
        switch ($expectedType) {
            case 'string':
                return is_string($value); // Validate string type
            case 'integer':
                // Check for scientific notation 
                if (is_string($value) && preg_match('/^[+-]?\d+(\.\d+)?[eE][+-]?\d+$/', $value)) {
                    return false;
                }
                return is_int($value) || (is_string($value) && ctype_digit($value)); // Validate integer type
            case 'float':
                return is_float($value) || (is_string($value) && is_numeric($value)); // Validate float type
            case 'boolean':
                return is_bool($value) || in_array(strtolower($value), ['true', 'false'], true); // Validate boolean type
            default:
                return false; // Return false for unknown types
        }
    }

    /**
     * Remove the BOM (Byte Order Mark) from a string if present.
     *
     * @param string $str The string to process.
     * @return string The processed string without the BOM.
     */
    function removeBom(string $str): string
    {
        if (substr($str, 0, 3) === "\xEF\xBB\xBF") {
            $str = substr($str, 3); // Remove BOM
        }
        return $str; // Return the processed string
    }

    /**
     * Check if the uploaded file is a valid CSV file.
     *
     * This method checks the file extension and can also validate the content
     * to ensure it adheres to CSV format.
     *
     * @param string $file_path Path to the file to be checked.
     * @return bool True if the file is a valid CSV, false otherwise.
     */
    public function isValidCsvFile(string $file_path): bool
    {

        // Check the file extension
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        if (strtolower($file_extension) !== 'csv') {
            // Optionally check MIME type for temporary files
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);
            if ($mime_type !== 'text/plain' && $mime_type !== 'text/csv') {
                return false; // Invalid MIME type
            }
        }

        return true; // Valid CSV file
    }

}
