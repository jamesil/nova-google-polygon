<?php

namespace Jamesil\NovaGooglePolygon\Support;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Jamesil\NovaGooglePolygon\Casts\AsPolygon;
use Jamesil\NovaGooglePolygon\Exceptions\InvalidPoint;
use JsonSerializable;

final class Polygon implements Arrayable, Castable, Jsonable, JsonSerializable
{
    /**
     * @var Point[]
     */
    private array $points = [];

    /**
     * @param array<Point|array> $points
     * @throws InvalidPoint
     */
    public function __construct(array $points)
    {
        $this->setPoints($points);
    }

    /**
     * @param array<Point|array> $points
     * @return $this
     * @throws InvalidPoint
     */
    public function setPoints(array $points): Polygon
    {
        $newPoints = [];

        foreach ($points as $point) {
            if ($point instanceof Point) {
                $newPoints[] = $point;

                continue;
            }

            if (! is_array($point)) {
                throw new InvalidPoint($point);
            }

            $newPoints[] = Point::fromArray($point);
        }

        $this->points = $newPoints;

        return $this;
    }

    public function getMinLatitude(): float
    {
        return min(array_column($this->points, 'lat'));
    }

    public function getMaxLatitude(): float
    {
        return max(array_column($this->points, 'lat'));
    }

    public function getMinLongitude(): float
    {
        return min(array_column($this->points, 'lng'));
    }

    public function getMaxLongitude(): float
    {
        return max(array_column($this->points, 'lng'));
    }

    public function getBoundingBox(): array
    {
        $latitudes = array_column($this->points, 'lat');
        $longitudes = array_column($this->points, 'lng');
        $minLatitude = min($latitudes);
        $minLongitude = min($longitudes);
        $maxLatitude = max($latitudes);
        $maxLongitude = max($longitudes);

        return [
            [$minLatitude, $minLongitude],
            [$minLatitude, $maxLongitude],
            [$maxLatitude, $maxLongitude],
            [$maxLatitude, $minLongitude],
        ];
    }

    /**
     * @throws InvalidPoint
     */
    public function pointOnVertex(Point|array $point): bool
    {
        if (! ($point instanceof Point)) {
            $point = Point::fromArray($point);
        }

        foreach ($this->points as $vertexPoint) {
            if ($point->lat === $vertexPoint->lat && $point->lng === $vertexPoint->lng) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the given point lies inside the polygon.
     *
     * Uses the even–odd (ray casting) rule, casting a ray along increasing
     * longitude and counting edge crossings. A point exactly on a vertex is
     * treated as inside; a point lying exactly on an edge follows the
     * standard half-open convention of the ray-casting rule.
     *
     * @throws InvalidPoint
     */
    public function contain(Point|array $point): bool
    {
        if (! ($point instanceof Point)) {
            $point = Point::fromArray($point);
        }

        $n = count($this->points);

        // A polygon needs at least three vertices to enclose any area.
        if ($n < 3) {
            return false;
        }

        if ($this->pointOnVertex($point)) {
            return true;
        }

        $inside = false;
        $lat = $point->lat;
        $lng = $point->lng;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $iLat = $this->points[$i]->lat;
            $iLng = $this->points[$i]->lng;
            $jLat = $this->points[$j]->lat;
            $jLng = $this->points[$j]->lng;

            if (
                ($iLat > $lat) !== ($jLat > $lat)
                && $lng < ($jLng - $iLng) * ($lat - $iLat) / ($jLat - $iLat) + $iLng
            ) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @return Point[]
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    public function toArray(): array
    {
        return array_map(fn (Point $point) => $point->toArray(), $this->getPoints());
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public static function castUsing(array $arguments)
    {
        return AsPolygon::class;
    }
}
