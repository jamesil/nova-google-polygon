# Changelog

All notable changes to `nova-google-polygon` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## Unreleased

### Fixed

- `Polygon::contain()` now uses a standard even-odd ray-casting test. The previous implementation returned wrong results whenever the queried point shared a longitude with a polygon vertex, and threw a `ValueError` for coordinates near the equator (from feeding scientific-notation strings to `bccomp`). Points on a vertex are inside; edges follow a documented half-open convention.
- `Polygon::contain()` no longer errors on empty or degenerate (fewer than three vertices) polygons — it returns `false`.

### Changed

- Removed the `ext-bcmath` requirement; the corrected algorithm does not need it.

## 1.2.0 - 2026-07-05

### Fixed

- Polygon drawing was broken by Google's removal of the Maps JavaScript API drawing library (deprecated August 2025, removed May 2026). Drawing and editing now use [Terra Draw](https://terradraw.io), the ecosystem-endorsed successor. Versions up to and including 1.1.0 no longer work on default Maps API channels.
- Fields marked `->readonly()` no longer render an editable polygon.

### Changed

- New drawing interactions: click to place vertices; finish by clicking the first or last placed point, or by pressing Enter; Escape cancels an in-progress shape. Drag vertices to move them, click a midpoint to insert a vertex, right-click a vertex to remove it (a polygon keeps at least 3 vertices), and use the new "Clear shape" map button to start over. Touch devices are now supported.
- Replaced the abandoned `load-google-maps-api` loader with `@googlemaps/js-api-loader`.
- The unused `places` and `geometry` Maps libraries are no longer requested.
- A missing API key or a failed Maps API load now shows a visible message in the field instead of an empty map area.

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
