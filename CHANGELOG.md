# Changelog

All notable changes to `nova-google-polygon` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## V2.0.1 - 2026-04-15

### Changed

- preserve Google Maps attribution in the field UI
- add Dependabot configuration for GitHub Actions, Composer, and npm
- add CI and Packagist badges to the README
- remove the unused screenshot asset

### Notes

This is a maintenance release with repo, CI, and documentation improvements. No package runtime API changes were introduced.

## Unreleased

## 2.0.0 - 2026-04-15

### Changed

- `main` now tracks the 2.x release line for Laravel 12 and 13
- Laravel 9 to 11 maintenance moves to the `1.x` branch
- CI now uses a Laravel 12/13-specific Pest toolchain and skips secret-dependent test jobs for forked pull requests

## 1.1.0 - 2026-03-26

### Added

- Support for Laravel 12
- Support for Laravel 13 when used with Laravel Nova 5.8.0 or newer

### Changed

- Expanded Composer compatibility to allow `illuminate/support` 12.x and 13.x
- Updated CI coverage to test explicit Laravel and Nova compatibility combinations
- Updated the compatibility documentation to clarify the Nova 5.8.0+ requirement for Laravel 13

## 1.0.0 - 2024

### Added

- Support for Laravel Nova 5.0
- Support for Laravel 11
- Namespace changed to `jamesil/nova-google-polygon`
- Comprehensive documentation and examples
- Version compatibility table

### Changed

- Minimum Laravel version is now 9.0
- Updated all namespaces from `YieldStudio\NovaGooglePolygon` to `Jamesil\NovaGooglePolygon`
- Completely rewritten README with better examples

## 0.0.5 - 2023-07-31

### What's Changed

- Add ability to customize center coordinates by @fnematov in https://github.com/jamesil/nova-google-polygon/pull/3

### New Contributors

- @fnematov made their first contribution in https://github.com/jamesil/nova-google-polygon/pull/3

**Full Changelog**: https://github.com/jamesil/nova-google-polygon/compare/0.0.4...0.0.5

## 0.0.4 - 2023-04-20

### What's Changed

- Support laravel 10 by @Peterragheb in https://github.com/jamesil/nova-google-polygon/pull/2

### New Contributors

- @Peterragheb made their first contribution in https://github.com/jamesil/nova-google-polygon/pull/2

**Full Changelog**: https://github.com/jamesil/nova-google-polygon/compare/0.0.3...0.0.4

## 0.0.3 - 2022-09-05

**Full Changelog**: https://github.com/jamesil/nova-google-polygon/compare/0.0.2...0.0.3

- Rewrite Polygon::contain method

## 0.0.2 - 2022-08-31

**Full Changelog**: https://github.com/jamesil/nova-google-polygon/compare/0.0.1...0.0.2

- Polygon class
- Point class
- Custom cast

## 0.0.1 - 2022-08-31

**Full Changelog**: https://github.com/jamesil/nova-google-polygon/commits/0.0.1

First version of the package, the field is not yet fully customisable.
