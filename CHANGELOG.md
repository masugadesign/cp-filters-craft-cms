# Changelog

## 1.3.1 - 2023-04-12

### Changed
- Added support for Craft 3.8.

## 1.3.0 - 2022-03-25

### Added
- Added `Saved Filters` field type to establish relationships between CP Filters `SavedFilter` elements and other Craft elements.

## 1.2.0 - 2021-11-15

### Added
- Added support for Craft Commerce Products and Orders.

### Fixed
- Fixed lightswitch "onLabel" and "offLabel" support for pre-3.5.4 Craft versions.

## 1.1.3 - 2021-08-25

### Fixed
- Fixed element query select statements where the table names were being prefixed but Craft automatically aliases the table names in the query.
- Fixed entry types select options to account for redundant "default" entry types in Craft 3.7+.

## 1.1.2 - 2021-07-15

### Added
- Added support for Craft 3.7.

## 1.1.1.3 - 2021-07-07

### Added
- Added SlimSelect for searching and selecting multi-option filter values.

### Fixed
- Filter value select options quantity is no longer limited.

## 1.1.1.2 - 2021-06-30

### Fixed
- Fixed element criteria formatting when multiple criteria are applied to a single field.

## 1.1.1.1 - 2021-05-20

### Added
- Added `firstName` and `lastName` field filters to `User` element filtering.

### Fixed
- Fixed pagination links that were linking to entries element type by default.

## 1.1.1 - 2021-02-01

### Fixed
- Fixed the default config values in the `Settings` model.

### Changed
- Updated composer.json to support Craft 3.6.

## 1.1.0 - 2021-01-05

### Added
- Added "Saved Filters" functionality

### Fixed
- Replaced the `ucwords` filter to fix deprecation warnings

### Changed
- Set the minimum version requirement for Craft CMS to **3.3.16**

## 1.0.2 - 2020-12-10

### Added
- Added `siteId` filter for all core element types.
- Added support for `craft\fields\Lightswitch` field filtering.

### Fixed
- Fixed default config values that are incompatible with PostgreSQL.
- Fixed the changelog URL in composer.json.

## 1.0.1.1 - 2020-10-28

### Fixed
- Fixed the support URLs in the composer.json file.

### Fixed
- Fixed an asort() error that occurred for element types with no defined groups.

## 1.0.1 - 2020-10-28

### Fixed
- Fixed an asort() error that occurred for element types with no defined groups.

## 1.0.0 - 2020-10-28
