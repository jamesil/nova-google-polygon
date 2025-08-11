# Nova Google Polygon Field

A Laravel Nova field for creating and editing polygons on Google Maps.

## Features

- Interactive polygon drawing and editing on Google Maps
- Support for multiple polygon points
- Real-time coordinate updates
- Customizable map center and zoom
- Laravel model casting support
- Polygon containment checking utilities

## Requirements

- PHP 8.1 or higher
- Laravel Nova 4.0 or 5.0
- Laravel 9.0 or higher
- Google Maps API key with Maps JavaScript API enabled

## Version Compatibility

| Laravel Nova | Laravel     | PHP  | Package Version |
|--------------|-------------|------|-----------------|
| 5.0          | 10.x - 11.x | 8.1+ | 1.x             |
| 4.0          | 9.x - 11.x  | 8.1+ | 1.x             |

## Installation

Install the package via Composer:

```bash
composer require jamesil/nova-google-polygon
```

### Configuration

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

- `contain(Point|array $point)`: Check if a point is inside the polygon
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
