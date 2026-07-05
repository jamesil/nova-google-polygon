<?php

use Jamesil\NovaGooglePolygon\Exceptions\InvalidPoint;
use Jamesil\NovaGooglePolygon\Support\Polygon;

it('point on vertex', function () {
    $polygon = new Polygon([
        [0, 0],
        [0, 1],
    ]);

    expect($polygon)
        ->pointOnVertex([0, 0])->toBeTrue()
        ->pointOnVertex([5, 5])->toBeFalse()
        ->pointOnVertex([0, 0.5])->toBeFalse();
});

it('point in polygon', function () {
    $polygon = new Polygon([
        [1, 1],
        [1, 2],
        [3, 2],
        [3, 1],
    ]);

    expect($polygon)
        ->contain([0, 0])->toBeFalse()
        ->contain([2, 5])->toBeFalse()
        ->contain([1, 1])->toBeTrue()
        ->contain([2.5, 1.5])->toBeTrue();
});

it('point in polygon with lat/lng', function () {
    $polygon = new Polygon([
        [48.88296174, 2.38560516],
        [48.88310990, 2.386999901],
        [48.88318398, 2.387804571],
        [48.88334273, 2.388308826],
        [48.88350500, 2.388909641],
        [48.88369549, 2.389467540],
        [48.88410468, 2.390583339],
        [48.88435161, 2.390288296],
        [48.88465498, 2.390250745],
        [48.88528993, 2.390132728],
        [48.88609419, 2.389971796],
        [48.88653158, 2.390111270],
        [48.88706774, 2.389767948],
        [48.88798485, 2.389188591],
        [48.88646104, 2.381678405],
        [48.88471142, 2.383609596],
    ]);

    expect($polygon)
        ->contain([48.886821, 2.384657])->toBeTrue()
        ->contain([48.885984, 2.383921])->toBeTrue()
        ->contain([48.886965, 2.38463])->toBeTrue()
        ->contain([48.885819, 2.382452])->toBeTrue()
        ->contain([48.88296174, 2.38560516])->toBeTrue()
        ->contain([48.883459, 2.381774])->toBeFalse()
        ->contain([48.887191, 2.384579])->toBeFalse()
        ->contain([48.885067, 2.383058])->toBeFalse()
        ->contain([48.88296174, 2.38560515])->toBeFalse();
});


it('classifies points that share a longitude with a vertex', function () {
    // Triangle with vertices (lat,lng) = (0,0), (1,1), (0,2); minimum latitude is 0.
    // The previous implementation counted the ray through a vertex twice, so a
    // point sharing a vertex longitude was misclassified.
    $triangle = new Polygon([
        [0, 0],
        [1, 1],
        [0, 2],
    ]);

    expect($triangle)
        ->contain([-1, 1])->toBeFalse()   // below the polygon entirely (false positive before)
        ->contain([0.5, 1])->toBeTrue()   // strictly inside (false negative before)
        ->contain([2, 1])->toBeFalse();   // above the apex, sharing the apex longitude
});

it('does not crash for coordinates near the equator', function () {
    // Latitudes/longitudes near zero used to be cast to scientific-notation
    // strings and fed to bccomp(), which threw a ValueError on PHP 8.
    $polygon = new Polygon([
        [1, 0],
        [-1, 1],
        [-1, -1],
    ]);

    expect($polygon)
        ->contain([0.00001, 0.5])->toBeFalse()   // near-zero latitude, previously crashed
        ->contain([0.5, 0.499999])->toBeFalse()  // interpolated crossing near zero, previously crashed
        ->contain([0, 0])->toBeTrue();           // interior
});

it('treats degenerate polygons as containing nothing', function () {
    expect(new Polygon([]))->contain([0, 0])->toBeFalse();
    expect(new Polygon([[0, 0]]))->contain([0, 0])->toBeFalse();
    expect(new Polygon([[0, 0], [1, 1]]))->contain([0.5, 0.5])->toBeFalse();
});

it('classifies interior and exterior points for a simple square', function () {
    // Square covering latitude 1..3, longitude 1..2.
    $square = new Polygon([
        [1, 1],
        [1, 2],
        [3, 2],
        [3, 1],
    ]);

    expect($square)
        ->contain([2, 1.5])->toBeTrue()      // centre
        ->contain([2, 2.5])->toBeFalse()     // east of the square
        ->contain([2, 0.5])->toBeFalse()     // west of the square
        ->contain([0.5, 1.5])->toBeFalse()   // south of the square
        ->contain([3.5, 1.5])->toBeFalse();  // north of the square
});


it('applies a consistent half-open convention to points on an edge', function () {
    // Square covering latitude 1..3, longitude 1..2. Ray-casting containment is
    // half-open: the south (min latitude) and west (min longitude) edges are
    // inclusive, while the north and east edges are exclusive. These assertions
    // pin that convention so it cannot silently change.
    $square = new Polygon([
        [1, 1],
        [1, 2],
        [3, 2],
        [3, 1],
    ]);

    expect($square)
        ->contain([1, 1.5])->toBeTrue()    // south edge => inside
        ->contain([2, 1])->toBeTrue()      // west edge => inside
        ->contain([3, 1.5])->toBeFalse()   // north edge => outside
        ->contain([2, 2])->toBeFalse();    // east edge => outside
});

it('classifies interior, exterior and notch points for a concave polygon', function () {
    // C-shaped polygon: a square (lat 0..4, lng 0..4) with a rectangular notch
    // cut into its east side (lng 2..4, lat 1..3).
    $concave = new Polygon([
        [0, 0],
        [4, 0],
        [4, 4],
        [3, 4],
        [3, 2],
        [1, 2],
        [1, 4],
        [0, 4],
    ]);

    expect($concave)
        ->contain([2, 1])->toBeTrue()     // solid interior
        ->contain([3.5, 3])->toBeTrue()   // upper arm
        ->contain([0.5, 3])->toBeTrue()   // lower arm
        ->contain([2, 3])->toBeFalse()    // in the notch: inside the bounding box but outside the polygon
        ->contain([2, 5])->toBeFalse();   // fully outside
});

it('classifies points sharing a latitude with vertices (horizontal ray through vertices)', function () {
    // Diamond with left vertex (1,0) and right vertex (1,2) at the same latitude,
    // so a horizontal ray at latitude 1 passes straight through two vertices.
    $diamond = new Polygon([
        [0, 1],
        [1, 0],
        [2, 1],
        [1, 2],
    ]);

    expect($diamond)
        ->contain([1, -1])->toBeFalse()   // west of the diamond, ray grazes both side vertices
        ->contain([1, 1])->toBeTrue()     // centre
        ->contain([1, 3])->toBeFalse();   // east of the diamond

    $triangle = new Polygon([
        [0, 0],
        [2, 1],
        [0, 2],
    ]);

    expect($triangle)
        ->contain([2, 0])->toBeFalse()    // apex latitude, left of apex
        ->contain([2, 2])->toBeFalse();   // apex latitude, right of apex
});

it('applies the even-odd rule to a self-intersecting (bowtie) ring', function () {
    // Figure-eight whose two crossing edges meet at (lat 1, lng 1).
    $bowtie = new Polygon([
        [0, 0],
        [2, 2],
        [0, 2],
        [2, 0],
    ]);

    expect($bowtie)
        ->contain([1, 0.5])->toBeTrue()   // left lobe
        ->contain([1, 1.5])->toBeTrue()   // right lobe
        ->contain([1.5, 1])->toBeFalse()  // between the lobes, above the crossing
        ->contain([0.5, 1])->toBeFalse()  // between the lobes, below the crossing
        ->contain([3, 3])->toBeFalse();   // outside
});

it('handles collinear and duplicate consecutive vertices', function () {
    // Square (lat 1..3, lng 1..2) with an extra collinear vertex on the west edge.
    $collinear = new Polygon([
        [1, 1],
        [2, 1],
        [3, 1],
        [3, 2],
        [1, 2],
    ]);

    expect($collinear)
        ->contain([2, 1.5])->toBeTrue()
        ->contain([2, 2.5])->toBeFalse();

    // Duplicated vertex creates a zero-length edge.
    $duplicate = new Polygon([
        [1, 1],
        [1, 1],
        [1, 2],
        [3, 2],
        [3, 1],
    ]);

    expect($duplicate)
        ->contain([2, 1.5])->toBeTrue()
        ->contain([2, 2.5])->toBeFalse();
});

it('remains correct for a large polygon', function () {
    // 2000-vertex approximation of a circle of radius 10 centred at (0, 0).
    $points = [];
    for ($k = 0; $k < 2000; $k++) {
        $theta = 2 * M_PI * $k / 2000;
        $points[] = [10 * sin($theta), 10 * cos($theta)];
    }
    $circle = new Polygon($points);

    expect($circle)
        ->contain([0, 0])->toBeTrue()
        ->contain([3, 3])->toBeTrue()
        ->contain([0, 9.9])->toBeTrue()
        ->contain([0, 10.1])->toBeFalse()
        ->contain([9, 9])->toBeFalse();
});


it('get bounding box', function () {
    $polygon = new Polygon([
        [1, 1],
        [1.01, 1],
        [1, 2],
        [3, 2],
        [3, 1],
    ]);

    expect($polygon)
        ->getMaxLatitude()->toEqual(3)
        ->getMinLatitude()->toEqual(1)
        ->getMaxLongitude()->toEqual(2)
        ->getMinLongitude()->toEqual(1)
        ->getBoundingBox()->toEqual([[1, 1], [1, 2], [3, 2], [3, 1]]);
});


it('invalid point throws an exception', function () {
    new Polygon([
        [1, 'test'],
        [3, 2],
    ]);
})->throws(InvalidPoint::class);
