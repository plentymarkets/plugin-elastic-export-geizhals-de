# Release Notes for Elastic Export Geizhals.de

## v1.1.0 (2018-03-28)

### Changed
- All configured shipping costs are considered.
- The class FiltrationService is responsible for the filtration of all variations.

## v1.0.11 (2018-02-16)

### Changed
- Updated plugin short description.

## 1.0.10 (2017-11-01)

### Fixed
- An issue was fixed which caused the connection to elasticsearch to break.

## v1.0.9 (2017-08-22)

### Changed 
- The format plugin is now based on Elastic Search only.
- The performance has been improved.

## v1.0.8 (2017-07-18)

### Changed
- The plugin Elastic Export is now required to use the plugin format.

## v1.0.7 (2017-05-31)

### Fixed
- An issue was fixed which caused the export to use the wrong webstore client to get the price.

## v1.0.6 (2017-05-29)

### Fixed
- An issue was fixed which caused elastic search to ignore the set referrers for the barcodes.

## v1.0.5 (2017-05-16)

### Fixed
- An issue was fixed which caused the stock filter not to be correctly evaluated.
- An issue was fixed which caused the variations not to be exported in the correct order.
- An issue was fixed which caused the export format to export texts in the wrong language.

## v1.0.4 (2017-05-05)

### Fixed
- An issue was fixed which caused errors while loading the export format.

## v1.0.3 (2017-04-20)

### Changed
- This plugin works now with elastic search only.
- The performance has been improved.
- Outsourced the stock filter logic to the Elastic Export plugin.

## v1.0.2 (2017-03-21)

### Deleted
- Removed the predefined condition for invalid variations.
- Removed the imageMutator from the ResultField class, because is not necessarily in the export.

## v1.0.1 (2017-03-14)

### Added
- Added marketplace name.

### Changed
- Changed plugin icons.

## v1.0.0 (2017-02-24)
 
### Added
- Added initial plugin files
