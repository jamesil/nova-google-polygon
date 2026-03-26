<?php

use Jamesil\NovaGooglePolygon\Casts\AsPolygon;
use Jamesil\NovaGooglePolygon\Support\Polygon;

it('returns null when the stored polygon value is blank', function () {
    $cast = new AsPolygon();

    expect($cast->get(new stdClass(), 'coverage_area', '', []))->toBeNull();
});

it('hydrates a polygon from json', function () {
    $cast = new AsPolygon();
    $value = json_encode([
        ['lat' => 48.858361, 'lng' => 2.336164],
        ['lat' => 48.859361, 'lng' => 2.337164],
        ['lat' => 48.857361, 'lng' => 2.338164],
    ]);

    $polygon = $cast->get(new stdClass(), 'coverage_area', $value, []);

    expect($polygon)
        ->toBeInstanceOf(Polygon::class)
        ->and($polygon->toArray())->toEqual(json_decode($value, true));
});

it('serializes an array payload into polygon json', function () {
    $cast = new AsPolygon();
    $value = [
        ['lat' => 48.858361, 'lng' => 2.336164],
        ['lat' => 48.859361, 'lng' => 2.337164],
        ['lat' => 48.857361, 'lng' => 2.338164],
    ];

    expect($cast->set(new stdClass(), 'coverage_area', $value, []))
        ->toEqual(json_encode($value));
});

it('serializes a polygon instance into polygon json', function () {
    $cast = new AsPolygon();
    $polygon = new Polygon([
        ['lat' => 48.858361, 'lng' => 2.336164],
        ['lat' => 48.859361, 'lng' => 2.337164],
        ['lat' => 48.857361, 'lng' => 2.338164],
    ]);

    expect($cast->set(new stdClass(), 'coverage_area', $polygon, []))
        ->toEqual($polygon->toJson());
});

it('throws when serializing a non-polygon, non-array value', function () {
    $cast = new AsPolygon();

    $cast->set(new stdClass(), 'coverage_area', 'invalid', []);
})->throws(\Exception::class, 'coverage_area must be a Polygon instance or an array.');
