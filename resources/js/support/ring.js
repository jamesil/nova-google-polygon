// Conversion between the field's stored value ([{lat, lng}, ...]) and the
// closed GeoJSON rings ([[lng, lat], ...]) Terra Draw works with.

// Matches Terra Draw's default coordinatePrecision; addFeatures() rejects
// coordinates with more decimal places than the adapter is configured for.
export const PRECISION = 9;

export function roundCoordinate(input) {
    const factor = Math.pow(10, PRECISION);

    return Math.round(parseFloat(input) * factor) / factor;
}

// Returns null when the value cannot form a polygon — Terra Draw requires at
// least 3 distinct points and a ring whose first and last coordinates match.
export function valueToRing(value) {
    if (!Array.isArray(value) || value.length < 3) {
        return null;
    }

    const ring = value.map((point) => [
        roundCoordinate(point && point.lng),
        roundCoordinate(point && point.lat),
    ]);

    for (let i = 0; i < ring.length; i++) {
        if (!isFinite(ring[i][0]) || !isFinite(ring[i][1])) {
            return null;
        }
    }

    ring.push(ring[0].slice());

    return ring;
}

// The stored value never contains the duplicated closing point.
export function ringToValue(ring) {
    return ring.slice(0, -1).map((coordinate) => ({ lat: coordinate[1], lng: coordinate[0] }));
}

// Terra Draw's default idStrategy validates ids as UUID v4, so the fallback
// (for non-secure contexts without crypto.randomUUID) must be RFC4122-shaped.
export function featureId() {
    if (
        typeof window !== 'undefined' &&
        window.crypto &&
        typeof window.crypto.randomUUID === 'function'
    ) {
        return window.crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
        const random = (Math.random() * 16) | 0;
        const value = character === 'x' ? random : (random & 0x3) | 0x8;

        return value.toString(16);
    });
}
