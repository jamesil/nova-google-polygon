import { describe, expect, it } from 'vitest';
import { PRECISION, featureId, ringToValue, roundCoordinate, valueToRing } from './ring';

const V4_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;

describe('valueToRing', () => {
    it('converts {lat,lng} objects to a closed [lng,lat] ring', () => {
        const ring = valueToRing([
            { lat: 1, lng: 2 },
            { lat: 3, lng: 4 },
            { lat: 5, lng: 6 },
        ]);

        expect(ring).toEqual([
            [2, 1],
            [4, 3],
            [6, 5],
            [2, 1],
        ]);
    });

    it('closes the ring with a copy, not a reference', () => {
        const ring = valueToRing([
            { lat: 1, lng: 2 },
            { lat: 3, lng: 4 },
            { lat: 5, lng: 6 },
        ]);

        expect(ring[ring.length - 1]).not.toBe(ring[0]);
    });

    it('rounds coordinates to the shared precision', () => {
        // Strings so the literals themselves cannot lose float precision;
        // the helpers parseFloat their input either way.
        const ring = valueToRing([
            { lat: '48.8583612345678901', lng: '2.3361645678901234' },
            { lat: 48.9, lng: 2.4 },
            { lat: 48.7, lng: 2.5 },
        ]);

        expect(ring[0]).toEqual([2.336164568, 48.858361235]);
        expect(String(ring[0][0]).replace(/^-?\d+\.?/, '').length).toBeLessThanOrEqual(PRECISION);
    });

    it('returns null for degenerate values', () => {
        expect(valueToRing(null)).toBeNull();
        expect(valueToRing(undefined)).toBeNull();
        expect(valueToRing([])).toBeNull();
        expect(valueToRing([{ lat: 1, lng: 2 }])).toBeNull();
        expect(
            valueToRing([
                { lat: 1, lng: 2 },
                { lat: 3, lng: 4 },
            ])
        ).toBeNull();
    });

    it('returns null when a point is missing or not numeric', () => {
        expect(
            valueToRing([
                { lat: 1, lng: 2 },
                { lat: 'nope', lng: 4 },
                { lat: 5, lng: 6 },
            ])
        ).toBeNull();
        expect(valueToRing([{ lat: 1, lng: 2 }, null, { lat: 5, lng: 6 }])).toBeNull();
    });
});

describe('ringToValue', () => {
    it('drops the closing point and restores {lat,lng} order', () => {
        const value = ringToValue([
            [2, 1],
            [4, 3],
            [6, 5],
            [2, 1],
        ]);

        expect(value).toEqual([
            { lat: 1, lng: 2 },
            { lat: 3, lng: 4 },
            { lat: 5, lng: 6 },
        ]);
    });

    it('round-trips a value through a ring unchanged (at stored precision)', () => {
        const value = [
            { lat: 48.88296174, lng: 2.38560516 },
            { lat: 48.8831099, lng: 2.386999901 },
            { lat: 48.88318398, lng: 2.387804571 },
        ];

        expect(ringToValue(valueToRing(value))).toEqual(value);
    });
});

describe('roundCoordinate', () => {
    it('keeps values already within precision untouched', () => {
        expect(roundCoordinate(48.858361)).toBe(48.858361);
    });

    it('parses numeric strings', () => {
        expect(roundCoordinate('48.858361')).toBe(48.858361);
    });
});

describe('featureId', () => {
    it('produces RFC4122 v4-shaped ids (Terra Draw rejects anything else)', () => {
        for (let i = 0; i < 25; i++) {
            expect(featureId()).toMatch(V4_PATTERN);
        }
    });

    it('produces unique ids', () => {
        expect(featureId()).not.toBe(featureId());
    });
});
