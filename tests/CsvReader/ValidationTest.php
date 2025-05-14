<?php

use Jletrondo\CsvReader\CsvReader;

beforeEach(function () {
    // Default columns for most tests
    $this->columns = [
        ['column_name' => 'name', 'name' => 'name', 'type' => 'string', 'validate' => 'required'],
        ['column_name' => 'email', 'name' => 'email', 'type' => 'string', 'validate' => 'required|unique'],
    ];
});

test('fails if required column is missing', function () {
    $csv = <<<CSV
        name
        John
        Jane
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeFalse();
    expect($result['error'])->toContain('missing the following columns');
    unlink($file);
});

test('fails when required column is empty', function () {
    $csv = <<<CSV
        name,email
        John,john@example.com
        ,jane@example.com
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
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain("Required column 'name' is empty.");
    unlink($file);
});

test('fails if required field is empty', function () {
    $csv = <<<CSV
        name,email
        John,
        Jane,jane@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain('Required column');
    unlink($file);
});

test('fails if unique constraint is violated', function () {
    $csv = <<<CSV
        name,email
        John,john@example.com
        Jane,john@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $this->columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain('Duplicate found');
    unlink($file);
});

test('validates type: integer', function () {
    $columns = [
        [
            'column_name' => 'id', 
            'name' => 'id', 
            'type' => 'integer', 
            'required' => true
        ],
    ];
    $csv = <<<CSV
        id
        123
        abc
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain('Invalid type');
    unlink($file);
});

test('fails when value is not in allowed_values column definition', function () {
    $csv = <<<CSV
        name,role
        Alice,admin
        Bob,user
        Charlie,guest
        Dave,invalid_role
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
            'column_name' => 'role',
            'name' => 'role',
            'type' => 'string',
            'validate' => 'required',
            'allowed_values' => ['admin', 'user', 'guest'],
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'])->not->toBeEmpty();
    expect($result['errors'][0]['message'])->toContain("Invalid value in column 'role'");
    expect($result['errors'][0]['message'])->toContain("invalid_role");
    unlink($file);
});

test('fails when type is invalid', function () {
    $csv = <<<CSV
        name,age
        John,twenty
        Jane,30
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'name',
            'name'        => 'name',
            'type'        => 'string',
            'validate'    => 'required',
        ],
        [
            'column_name' => 'age',
            'name'        => 'age',
            'type'        => 'integer',
            'validate'    => 'required',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain("Invalid type for column 'age'");
    unlink($file);
});

test('fails when value is too short (min_length)', function () {
    $csv = <<<CSV
        username
        ab
        abc
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'username',
            'name' => 'username',
            'type' => 'string',
            'validate' => 'required|min_length[3]',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain("too short");
    unlink($file);
});

test('fails when value is too long (max_length)', function () {
    $csv = <<<CSV
        username
        abcdefghij
        abc
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'username',
            'name' => 'username',
            'type' => 'string',
            'validate' => 'required|max_length[5]',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain("too long");
    unlink($file);
});

test('fails when value is not unique', function () {
    $csv = <<<CSV
        email
        john@example.com
        jane@example.com
        john@example.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'email',
            'name' => 'email',
            'type' => 'string',
            'validate' => 'required|unique',
            'unique' => true,
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['total_error_rows'])->toBe(1);
    expect($result['errors'][0]['message'])->toContain("Duplicate found in column 'email'");
    unlink($file);
});

test('transforms value to uppercase when "uppercase" validation is set', function () {
    $csv = <<<CSV
        name
        john
        jane
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'name',
            'name' => 'name',
            'type' => 'string',
            'validate' => 'uppercase',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'][0]['name'])->toBe('JOHN');
    expect($result['rows_processed'][1]['name'])->toBe('JANE');
    unlink($file);
});

test('transforms value to lowercase when "lowercase" validation is set', function () {
    $csv = <<<CSV
        name
        JOHN
        JANE
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'name',
            'name' => 'name',
            'type' => 'string',
            'validate' => 'lowercase',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'][0]['name'])->toBe('john');
    expect($result['rows_processed'][1]['name'])->toBe('jane');
    unlink($file);
});

test('strips HTML tags when "strip_tags" validation is set', function () {
    $csv = <<<CSV
        comment
        "<b>Hello</b>"
        "<i>World</i>"
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'comment',
            'name' => 'comment',
            'type' => 'string',
            'validate' => 'strip_tags',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'][0]['comment'])->toBe('Hello');
    expect($result['rows_processed'][1]['comment'])->toBe('World');
    unlink($file);
});

test('encodes HTML entities when "htmlentities" validation is set', function () {
    $csv = <<<CSV
        text
        <div>Hello</div>
        <span>World</span>
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'text',
            'name' => 'text',
            'type' => 'string',
            'validate' => 'htmlentities',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'][0]['text'])->toBe('&lt;div&gt;Hello&lt;/div&gt;');
    expect($result['rows_processed'][1]['text'])->toBe('&lt;span&gt;World&lt;/span&gt;');
    unlink($file);
});

test('strips quotes when "strip_quotes" validation is set', function () {
    $csv = <<<CSV
        quoted
        "Hello"
        'World'
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'quoted',
            'name' => 'quoted',
            'type' => 'string',
            'validate' => 'strip_quotes',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'][0]['quoted'])->not->toContain('"');
    expect($result['rows_processed'][1]['quoted'])->not->toContain("'");
    unlink($file);
});

test('urlencodes value when "urlencode" validation is set', function () {
    $csv = <<<CSV
        url
        hello world
        foo@bar.com
        CSV;
    $file = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($file, $csv);

    $columns = [
        [
            'column_name' => 'url',
            'name' => 'url',
            'type' => 'string',
            'validate' => 'urlencode',
        ],
    ];

    $reader = new CsvReader(['columns' => $columns]);
    $result = $reader->read($file);

    expect($result['status'])->toBeTrue();
    expect($result['rows_processed'][0]['url'])->toBe('hello+world');
    expect($result['rows_processed'][1]['url'])->toBe('foo%40bar.com');
    unlink($file);
});