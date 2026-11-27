# Changelog

## [Unreleased]
### Fixed
* Wrong language keys for "Results for", the eMentoring print buttons and "No users selected"
* Hard-coded texts replaced by language variables: hidden column title, Excel sheet name, file removal messages, title of new templates, placeholder descriptions in the certificate text forms
* The section title of the "Period & Calculation Type" form now matches its tab title
* Certificate PDF: "Yes"/"No" and the result sentences come from the language files; the PDF texts (incl. labels and salutation) are always German, independent of the user language

### Notes
* After updating, refresh the plugin languages (Administration → Plugins → Refresh Languages), since the version is unchanged

## [3.2.0]
### Changed
* Result table: "Processing of Cross-Disciplinary Homework (Passed/Total)" is colored again with the rules of the former progress bars (green/orange/red/grey, based on the configured period), now as a colored badge around the value
* Result table: "Processing of Recommended Mathematics Learning Modules" is now colored with the same rules as well (previously only the fallback without results was a progress bar)
* While the period is running, the badge tooltip shows the target value; the Excel/CSV export stays plain text
* Faster certificate printing: tracking tool data is loaded once per course instead of per user
* Result table shows exercise progress as passed/total instead of progress bars (faster)
### Fixed
* Printing several certificates without a selection shows "No Users Selected" instead of "-no_records_selected-"
* Exercise results are summed over all exercises of the group instead of using only the last one (table, certificate and the prefilled "homework" value when editing a certificate)
* Self print (tracking tool) no longer fails with HTTP 500 when the user has no certificate user data; the name falls back to the entered or an empty name
* No blank page between page 1 and page 2 of a certificate when page 1 is completely filled
* Location/date and the signature block are no longer split across pages; if they do not fit on page 1 they move to the next page together
### Removed
* Result table column "Bearbeitung der Aufgaben zu überfachlichen Themen (Anzahl)" (duplicated the passed count of the passed/total column)

## [3.1.0]
### Added
* New columns "Generated at" (date and time the learning suggestions were generated), "Learning objective suggestions" and "Notification sent" in the results table
* New row action "Generate learning recommendation" in the results table: starts the calculation of the learning suggestions (LearningObjectiveSuggestions plugin) for an active participant of the course's initial test and sends them to the user, if no suggestions exist yet

### Fixed
* Excel/CSV export: header and values are built from the same column list, so every value is under the right title (the header no longer contains an "invisible" column, missing values no longer shift the following columns)
* Excel/CSV export: cells contain plain text only (no HTML or progress bar markup, line breaks kept)

### Security
* The regenerate action only accepts course/group members and requires a selected row

## [3.0.1]
### Removed
* UDF field "UDF Editor Object for Name Entry" (`true_name_helper`), including its config entry

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
