# Changelog

## [3.0.0]
### Updated
* Plugin for release_11 updated

### Added
* Pagination and sorting in the results table (25 rows per page by default). Only the users on the current page are calculated, so large courses load faster
* Confirmation dialog (modal) before resetting the configuration
* Error message and redirect back to the course/group when the user has no permission (previously: redirect to the login page)

### Changed
* Results tables (overview, single result, multiple results) rebuilt with with the ILIAS UI framework (data tables, filters, forms)
* The plugin (tab, results, print) is only available for courses/groups  whose title contains a configured keyword. Empty keywords no longer match every title
* Initial test results are limited to the current course
* Self-printing of certificates is only possible for members of the course/group within the configured time window

### Removed
* Attendance list (xali) integration
* Unused code

### Security
* CSRF protection for all commands that change data (ilCtrlSecurityInter, token checks)
* Commands restricted to fixed allow-lists in all GUIs
* Checks that users belong to the course/group for all user IDs taken  from the request
* Output escaped against XSS; CSV/Excel export protected against formula injection
* UDF field "UDF Editor Object for Name Entry" (`true_name_helper`), including its config entry

## [2.1.0]
### Removed
* Option to print certificates in courses for users
### Added
* Display individual assessments in certificate
* Display sessions in certificate
### Changed
* Font of certificate

## [1.2.0]
* Update for ilias9

## [1.1.0]
* fixes in permission handling

## [0.1.5]
* Only show e-mentorings of specific group
* Only show exercises of specific group
* Count Attendance List
