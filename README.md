# Nova Google Polygon Field

[![Tests](https://github.com/jamesil/nova-google-polygon/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/jamesil/nova-google-polygon/actions/workflows/tests.yml)
[![Check & fix styling](https://github.com/jamesil/nova-google-polygon/actions/workflows/styling.yml/badge.svg?branch=main)](https://github.com/jamesil/nova-google-polygon/actions/workflows/styling.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/jamesil/nova-google-polygon?label=Packagist)](https://packagist.org/packages/jamesil/nova-google-polygon)
[![Total Downloads](https://img.shields.io/packagist/dt/jamesil/nova-google-polygon)](https://packagist.org/packages/jamesil/nova-google-polygon)

A Laravel Nova field for creating and editing polygons on Google Maps.

> [!WARNING]
> **Versions up to and including 2.0.1 (and 1.1.0 on the 1.x line) no longer work.** Google removed the Maps JavaScript API drawing library in May 2026, which those versions relied on for polygon drawing. Upgrade to **2.1.0** (Laravel 12/13) or **1.2.0** (Laravel 9–11), which use [Terra Draw](https://terradraw.io) instead.

## Features

- Interactive polygon drawing and editing on Google Maps
- Support for multiple polygon points
- Real-time coordinate updates
- Customizable map center and zoom
- Laravel model casting support
- Polygon containment checking utilities

## Requirements

- PHP 8.2 or higher for the 2.x line
- Laravel Nova 5.0+
- Laravel 12.0 or higher
- Google Maps API key with Maps JavaScript API enabled

Laravel 13 requires PHP 8.3+ plus Laravel Nova 5.8.0 or newer.

## Version Compatibility

| Package Version | Laravel      | Laravel Nova | PHP         |
|-----------------|--------------|--------------|-------------|
| 1.x             | 9.x - 11.x   | 4.x or 5.x   | 8.1+        |
| 2.x             | 12.x         | 5.0+         | 8.2+        |
| 2.x             | 13.x         | 5.8+         | 8.3+        |

The `main` branch tracks the 2.x release line. The `1.x` line remains the maintenance line for Laravel 9 through 11 users.

Laravel 13 support was added by Nova in version 5.8.0, so do not use earlier Nova 5 releases on Laravel 13.

## Installation

Install the package via Composer:

```bash
composer require jamesil/nova-google-polygon:^2.0
```

If you need the legacy compatibility line instead:

```bash
composer require jamesil/nova-google-polygon:^1.0
```

Use `^1.0` for Laravel 9 to 11 projects. Use `^2.0` for Laravel 12 and 13 projects.

### Configuration

The package merges its default configuration automatically. You only need to publish the config file if you want to customize the defaults.

1. **Publish the configuration file** (optional):

```bash
php artisan vendor:publish --provider="Jamesil\NovaGooglePolygon\FieldServiceProvider"
```

2. **Set up your Google Maps API key**:

First, create a Google Cloud project and enable the Maps JavaScript API:
- Visit [Google Cloud Console](https://console.cloud.google.com)
- Create a new project or select an existing one
- Enable the Maps JavaScript API
- Create credentials to get your API key

3. **Add environment variables**:

Add these variables to your `.env` file:

```env
NOVA_GOOGLE_POLYGON_API_KEY=your-google-maps-api-key
NOVA_GOOGLE_POLYGON_CENTER_LAT=48.858361
NOVA_GOOGLE_POLYGON_CENTER_LNG=2.336164
```

## Usage

### Basic Field Usage

Add the field to your Nova resource:

```php
use Jamesil\NovaGooglePolygon\GooglePolygon;

class Location extends Resource
{
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            
            Text::make('Name'),
            
            GooglePolygon::make('Coverage Area', 'coverage_area'),
        ];
    }
}
```

### Drawing and editing

On a form with no polygon yet, the map is in drawing mode:

- **Click** on the map to place each vertex.
- **Finish** the shape by clicking the first (or last) placed point, or by pressing **Enter**.
- **Escape** cancels an in-progress shape.

Once a polygon exists it switches to editing mode:

- **Drag a vertex** to move it.
- **Click a midpoint** (the smaller handle between two vertices) to insert a new vertex.
- **Right-click a vertex** to remove it — a polygon always keeps at least 3 vertices.
- **Clear shape** (button on the map) removes the polygon so you can draw a new one.

If the handles disappear after clicking elsewhere on the map, click the polygon to select it again. Touch devices are supported: tap to place vertices and drag handles to edit.

### Database Setup

The polygon data is stored as JSON. Create a JSON column in your migration:

```php
Schema::create('locations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->json('coverage_area')->nullable();
    $table->timestamps();
});
```

### Model Casting

Use the provided cast to automatically handle polygon data:

```php
use Jamesil\NovaGooglePolygon\Casts\AsPolygon;

class Location extends Model
{
    protected $casts = [
        'coverage_area' => AsPolygon::class,
    ];
}
```

### Working with Polygons

The package provides utility classes for working with polygon data:

```php
use Jamesil\NovaGooglePolygon\Support\Polygon;
use Jamesil\NovaGooglePolygon\Support\Point;

// Create a polygon
$polygon = new Polygon([
    ['lat' => 48.858361, 'lng' => 2.336164],
    ['lat' => 48.859361, 'lng' => 2.337164],
    ['lat' => 48.857361, 'lng' => 2.338164],
]);

// Check if a point is inside the polygon
$point = new Point(48.858500, 2.337000);
$isInside = $polygon->contain($point); // returns true

// Get bounding box
$boundingBox = $polygon->getBoundingBox();

// Get min/max coordinates
$minLat = $polygon->getMinLatitude();
$maxLat = $polygon->getMaxLatitude();
$minLng = $polygon->getMinLongitude();
$maxLng = $polygon->getMaxLongitude();
```

### Advanced Example

Here's a complete example of using the field with a taxi pickup zone system:

```php
// Nova Resource
use Jamesil\NovaGooglePolygon\GooglePolygon;

class PickupZone extends Resource
{
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Zone Name'),
            Number::make('Base Fare'),
            Boolean::make('Active'),
            GooglePolygon::make('Pickup Area', 'pickup_area'),
        ];
    }
}

// Model
use Jamesil\NovaGooglePolygon\Casts\AsPolygon;
use Jamesil\NovaGooglePolygon\Support\Point;

class PickupZone extends Model
{
    protected $casts = [
        'pickup_area' => AsPolygon::class,
        'active' => 'boolean',
    ];
    
    public function isPickupAllowed($latitude, $longitude)
    {
        if (!$this->active || !$this->pickup_area) {
            return false;
        }
        
        $point = new Point($latitude, $longitude);
        return $this->pickup_area->contain($point);
    }
    
    public static function findAvailableZone($latitude, $longitude)
    {
        return static::where('active', true)
            ->get()
            ->first(function ($zone) use ($latitude, $longitude) {
                return $zone->isPickupAllowed($latitude, $longitude);
            });
    }
    
    public function getTotalFare($distance, $duration)
    {
        // Calculate fare based on zone's base fare
        return $this->base_fare + ($distance * 2.5) + ($duration * 0.5);
    }
}
```

## API Reference

### Polygon Class Methods

- `contain(Point|array $point)`: Check if a point is inside the polygon (even-odd ray casting). A point on a vertex counts as inside; points exactly on an edge follow a half-open convention (the minimum-latitude and minimum-longitude edges are inclusive, the opposite edges exclusive)
- `pointOnVertex(Point|array $point)`: Check if a point is on a polygon vertex
- `getBoundingBox()`: Get the bounding box coordinates
- `getMinLatitude()`, `getMaxLatitude()`: Get latitude bounds
- `getMinLongitude()`, `getMaxLongitude()`: Get longitude bounds
- `getPoints()`: Get all polygon points
- `setPoints(array $points)`: Set polygon points

### Point Class Methods

- `fromArray(array $input)`: Create a point from array
- `toArray()`: Convert point to array
- `toJson()`: Convert point to JSON

## Testing

Run the test suite:

```bash
composer test
```

## Credits

- [James Embling](https://github.com/jamesil)
- Based on the original work by [YieldStudio](https://github.com/YieldStudio/nova-google-polygon)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
