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
            'd/m/Y',
            'Y/m/d',
            'm-d-Y',
            'd-m-Y',
            'Y-m-d',
        ];
        foreach ($formats as $format) {
            $parsedDate = DateTime::createFromFormat($format, $date);
            // Check if the date is valid and matches the format
            if ($parsedDate && $parsedDate->format($format) === $date) {
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