<?php

if (!function_exists('validate_date')) {
    /**
        * Validates a date string according to multiple specified formats.
        *
        * This function checks if the given date string matches any of the specified formats.
        * It uses the DateTime::createFromFormat() method to parse the date and compare it with the original format.
        *
        * @param string $date The date string to validate.
        *
        * @return bool Returns true if the date is valid and matches any of the specified formats, otherwise false.
        *
        * @example
        * validate_date('12/31/2022'); // Returns true
        * validate_date('2022/12/31'); // Returns true
    */
    function validate_date($date) {
        // Define the acceptable date formats
        $formats = [
            'm/d/Y',
            'm-d-Y',
            'Y/m/d',
            'Y-m-d',
        ];
        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $date);
            if ($dt && $dt->format($format) === $date) {
                return true;
            }
            // Also check for non-padded input (e.g., 3/6/2000 vs 03/06/2000)
            if ($dt && $dt->format($format) === date($format, strtotime($date))) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('custom_list_ul')) {
    /**
     * Generates an HTML unordered list (<ul>) from an array of strings.
     * Allows optional styling via class or inline style.
     *
     * @param array $items Array of strings to be listed.
     * @param array $attributes Optional associative array of HTML attributes (e.g., ['class' => 'my-list', 'style' => 'color:red;']).
     * @return string HTML string of the unordered list.
     */
    function custom_list_ul(array $items, array $attributes = []) {
        if (empty($items)) {
            return '';
        }

        // Build the attribute string
        $attr_str = '';
        foreach ($attributes as $key => $value) {
            $attr_str .= ' ' . htmlspecialchars($key, ENT_QUOTES) . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
        }

        $html = "<ul$attr_str>";
        foreach ($items as $item) {
            $html .= '<li>' . htmlspecialchars($item, ENT_QUOTES) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}

function validate_date_strict($date) {
    $patterns = [
        // m/d/Y or m-d-Y
        '/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/',
        // Y/m/d or Y-m-d
        '/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $date, $matches)) {
            if (count($matches) === 4) {
                // Determine which is month, day, year
                if (strlen($matches[1]) === 4) {
                    // Y/m/d or Y-m-d
                    $year = (int)$matches[1];
                    $month = (int)$matches[2];
                    $day = (int)$matches[3];
                } else {
                    // m/d/Y or m-d-Y
                    $month = (int)$matches[1];
                    $day = (int)$matches[2];
                    $year = (int)$matches[3];
                }
                // Check valid ranges
                if ($month < 1 || $month > 12) return false;
                if ($day < 1 || $day > 31) return false;
                if ($year < 1000 || $year > 9999) return false;
                // Check if it's a real date
                if (!checkdate($month, $day, $year)) return false;
                return true;
            }
        }
    }
    return false;
}

function reformat_date_to_mdy($date) {
    $date = trim($date);
    $formats = [
        'm/d/Y',
        'm-d-Y',
        'Y/m/d',
        'Y-m-d',
    ];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $date);
        $errors = DateTime::getLastErrors();
        print_r($errors);
        if ($dt && $errors['warning_count'] === 0 && $errors['error_count'] === 0) {
            return $dt->format('m/d/Y');
        }
    }
    return $date; // fallback, but should not happen if validated first
}