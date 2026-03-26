<?php

use Jamesil\NovaGooglePolygon\GooglePolygon;
use Laravel\Nova\Http\Requests\NovaRequest;

it('ignores missing polygon input when hydrating the model', function () {
    $field = new class('Coverage Area') extends GooglePolygon
    {
        public function hydrate(NovaRequest $request, string $requestAttribute, object $model, string $attribute): void
        {
            $this->fillAttributeFromRequest($request, $requestAttribute, $model, $attribute);
        }
    };

    $model = new class
    {
        public array $filled = [];

        public function forceFill(array $attributes): void
        {
            $this->filled = $attributes;
        }
    };

    $field->hydrate(NovaRequest::create('/', 'POST'), 'coverage_area', $model, 'coverage_area');

    expect($model->filled)->toBeEmpty();
});

it('decodes polygon json before filling the model', function () {
    $field = new class('Coverage Area') extends GooglePolygon
    {
        public function hydrate(NovaRequest $request, string $requestAttribute, object $model, string $attribute): void
        {
            $this->fillAttributeFromRequest($request, $requestAttribute, $model, $attribute);
        }
    };

    $payload = [
        ['lat' => 48.858361, 'lng' => 2.336164],
        ['lat' => 48.859361, 'lng' => 2.337164],
        ['lat' => 48.857361, 'lng' => 2.338164],
    ];

    $model = new class
    {
        public array $filled = [];

        public function forceFill(array $attributes): void
        {
            $this->filled = $attributes;
        }
    };

    $field->hydrate(
        NovaRequest::create('/', 'POST', ['coverage_area' => json_encode($payload)]),
        'coverage_area',
        $model,
        'coverage_area'
    );

    expect($model->filled)->toEqual([
        'coverage_area' => $payload,
    ]);
});
