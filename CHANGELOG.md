# Changelog

## [1.3.0] - 2025-06-13
### Added
- Added a new feature: users can now modify rows using the callback by returning an array with `'status' => true` and `'row' => $modifiedRow`.
- Added the ability to skip processing of a row from within the callback by returning an array with `'status' => false` and `'skip' => true`.

### Changed
- Removed the `required` and `unique` column definitions from the column configuration.
- The `required` and `unique` validations are now specified inside the `validate` key for each column, e.g. `validate => 'required|unique|max_length[10]'`.
- All column validations are now specified in the `validate` string, including `required`, `unique`, `min_length`, `max_length`, `lowercase`, `uppercase`, `strip_tags`, `htmlentities`, `strip_quotes`, and `urlencode`. For example: `validate => 'required|unique|min_length[3]|max_length[10]|lowercase'`.
- If the `type` is not set for a column, it now defaults to `string`.
- Added more internal validations for better data integrity and error reporting.
- Various minor bug fixes and improvements to error handling and validation logic.

## [1.2.0] - 2025-05-12
### Added
- Added progress callback feature: You can now set a progress callback to receive updates on CSV processing progress, including support for custom callback intervals.
- Updated README to document the progress callback usage and configuration.

## [1.1.1] - 2025-05-11
### Fixed
- Fixed errors path in CSV error file handling.

## [1.1.0] - 2025-05-10
### Added
- Added new column definition option: `allowed_values`.

## [1.0.1] - 2025-05-09
### Fixed
- Fixed incorrect package name in README.

## [1.0.0] - 2025-05-08
### Added
- Initial release.
- Required PHP 8.2.
- Added date validations and documentation.
- Added tests.
- Updated README.
