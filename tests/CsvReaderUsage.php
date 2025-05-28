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
            ['name' => 'company', 'column_name' => 'company', 'type' => 'string', 'validate' => 'required|max_length[20]'],
            ['name' => 'nationality', 'column_name' => 'nationality', 'type' => 'string', 'validate' => 'required|max_length[30]'],
            ['name' => 'employee_id', 'column_name' => 'employee id', 'type' => 'string', 'validate' => 'unique|required|max_length[20]'],
            ['name' => 'bio_no', 'column_name' => 'bio no', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'last_name', 'column_name' => 'last name', 'type' => 'string', 'validate' => 'required|max_length[30]'],
            ['name' => 'first_name', 'column_name' => 'first name', 'type' => 'string', 'validate' => 'required|max_length[30]'],
            ['name' => 'middle_name', 'column_name' => 'middle name', 'type' => 'string', 'validate' => 'max_length[30]'],
            ['name' => 'extension', 'column_name' => 'extension', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'nickname', 'column_name' => 'nickname', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'gender', 'column_name' => 'gender', 'type' => 'string', 'validate' => 'max_length[1]'],
            ['name' => 'birth_date', 'column_name' => 'birth date', 'type' => 'date', 'validate' => 'required|max_length[15]'],
            ['name' => 'place_of_birth', 'column_name' => 'place of birth', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'civil_status', 'column_name' => 'civil status', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'personal_email_address', 'column_name' => 'personal email address', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'company_email_address', 'column_name' => 'company email address', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'spouses_name', 'column_name' => "spouse's name", 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'no_of_children', 'column_name' => 'no. of children', 'type' => 'string', 'validate' => 'max_length[5]'],
            ['name' => 'tin', 'column_name' => 'tin', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'sss_no', 'column_name' => 'sss no.', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'mid_no', 'column_name' => 'mid no.', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'mp2_no', 'column_name' => 'mp2 no.', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'phealth_no', 'column_name' => 'phealth no.', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'fathers_name', 'column_name' => "father's name", 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'mothers_maiden_name', 'column_name' => "mother's maiden name", 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'division', 'column_name' => 'division', 'type' => 'string', 'validate' => 'max_length[30]'],
            ['name' => 'department', 'column_name' => 'department', 'type' => 'string', 'validate' => 'max_length[30]'],
            ['name' => 'section', 'column_name' => 'section', 'type' => 'string', 'validate' => 'max_length[30]'],
            ['name' => 'date_hired', 'column_name' => 'date hired', 'type' => 'string', 'validate' => 'max_length[15]'],
            ['name' => 'activity', 'column_name' => 'activity', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'activity_date', 'column_name' => 'activity date', 'type' => 'string', 'validate' => 'max_length[15]'],
            ['name' => 'status', 'column_name' => 'status', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'job_level', 'column_name' => 'job level', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'job_grade', 'column_name' => 'job grade', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'position', 'column_name' => 'position', 'type' => 'string', 'validate' => 'max_length[30]'],
            ['name' => 'contract_status', 'column_name' => 'contract status', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'regularization_date', 'column_name' => 'regularization date', 'type' => 'string', 'validate' => 'max_length[15]'],
            ['name' => 'payment_mode', 'column_name' => 'payment mode', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'taxcode', 'column_name' => 'taxcode', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'basic_rate', 'column_name' => 'basic rate', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'secondary_school', 'column_name' => 'secondary school', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'sec_grad_year', 'column_name' => 'sec grad year', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'college', 'column_name' => 'college', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'college_degree', 'column_name' => 'college degree', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'college_grad_year', 'column_name' => 'college grad year', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'post_graduate', 'column_name' => 'post graduate', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'postgrad_degree', 'column_name' => 'postgrad degree', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'postgrad_grad_year', 'column_name' => 'postgrad grad year', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'certification', 'column_name' => 'certification', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'certification_year', 'column_name' => 'certification year', 'type' => 'string', 'validate' => 'max_length[10]'],
            ['name' => 'immediate_superior', 'column_name' => 'immediate superior', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'manager_am', 'column_name' => 'manager/AM', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'head_of_department', 'column_name' => 'head of department', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'referred_by', 'column_name' => 'referred by', 'type' => 'string', 'validate' => 'max_length[50]'],
            ['name' => 'payroll_classification', 'column_name' => 'payroll classification', 'type' => 'string', 'validate' => 'max_length[30]'],
            ['name' => 'costcenter', 'column_name' => 'costcenter', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'bankcode', 'column_name' => 'bankcode', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'account_number', 'column_name' => 'account number', 'type' => 'string', 'validate' => 'max_length[30]'],
            ['name' => 'sss_employee_amount', 'column_name' => 'sss employee amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'sss_employer_amount', 'column_name' => 'sss employer amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'sss_ecc_amount', 'column_name' => 'sss ecc amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'provident_fund_employee_amount', 'column_name' => 'provident fund employee amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'provident_fund_employer_amount', 'column_name' => 'provident fund employer amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'philhealth_employee_amount', 'column_name' => 'philhealth employee amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'philhealth_employer_amount', 'column_name' => 'philhealth employer amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'pagibig_employee_amount', 'column_name' => 'pagibig employee amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'pagibig_employer_amount', 'column_name' => 'pagibig employer amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'withholding_tax_amount', 'column_name' => 'withholding tax amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'income_tax_amount', 'column_name' => 'income tax amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'mp2_amount', 'column_name' => 'mp2 amount', 'type' => 'string', 'validate' => 'max_length[20]'],
            ['name' => 'notes', 'column_name' => 'notes', 'type' => 'string', 'validate' => 'max_length[100]'],
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
            $this->reader->setProgressCallback(function($processed, $total) {
                if ($total) {
                    echo "Progress: " . round($processed / $total * 100, 2) . "%\n";
                } else {
                    echo "Rows processed: $processed\n";
                }
            });
            $this->reader->setCallback('custom_validation', $this);
            $result = $this->reader->read(__DIR__ . '/CsvReader/actual_data.csv');
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function custom_validation($row, $index) 
    {
        $errors = [];

        // check if company exists
        if ($row['company'] !== 'APPLE') {
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
