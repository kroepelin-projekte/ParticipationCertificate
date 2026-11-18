# Changelog

## [3.0.2]
### Fixed
* Exercise results are summed over all exercises of the group instead of using only the last one (table, certificate and the prefilled "homework" value when editing a certificate)
* Excel/CSV export: header and values are built from the same column list, no more shifted titles
* Self print (tracking tool) no longer fails with HTTP 500 when the user has no certificate user data; the name falls back to the entered or an empty name
* No blank page between page 1 and page 2 of a certificate when page 1 is completely filled
* Location/date and the signature block are no longer split across pages; if they do not fit on page 1 they move to the next page together
### Changed
* Faster certificate printing: tracking tool data is loaded once per course instead of per user
### Removed
* Result table column "Bearbeitung der Aufgaben zu überfachlichen Themen (Anzahl)" (duplicated the passed count of the passed/total column)

## [3.0.1]
### Changed
* Result table shows exercise progress as passed/total instead of progress bars (faster)

## [3.0.0]
### Updated
* Plugin für release_11 updated

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
