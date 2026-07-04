<template>
    <div v-if="loadError" class="nova-google-polygon-map nova-google-polygon-error">
        {{ loadError }}
    </div>
    <div v-else :id="mapId" ref="map" class="nova-google-polygon-map"></div>
</template>

<style>
.nova-google-polygon-map {
    border-radius: 5px;
    overflow: hidden;
    height: 500px;
    width: 100%;
}

.nova-google-polygon-error {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    text-align: center;
    background-color: rgba(127, 29, 29, 0.06);
    border: 1px dashed rgba(127, 29, 29, 0.4);
    color: #7f1d1d;
}

.nova-google-polygon-clear {
    margin: 10px;
    padding: 0 17px;
    height: 40px;
    background-color: #fff;
    border: 0;
    border-radius: 2px;
    box-shadow: rgba(0, 0, 0, 0.3) 0 1px 4px -1px;
    color: #565656;
    font-family: Roboto, Arial, sans-serif;
    font-size: 18px;
    cursor: pointer;
}

.nova-google-polygon-clear:hover {
    color: #000;
}
</style>

<script>
import { setOptions, importLibrary } from '@googlemaps/js-api-loader';
import { TerraDraw, TerraDrawPolygonMode, TerraDrawSelectMode } from 'terra-draw';
import { TerraDrawGoogleMapsAdapter } from 'terra-draw-google-maps-adapter';
import { valueToRing, ringToValue, featureId, PRECISION } from '../support/ring';

const DEFAULT_OUTLINE_COLOR = '#7f1d1d';
const DEFAULT_FILL_COLOR = '#fca5a5';
const DEFAULT_FILL_OPACITY = 0.6;
const DEFAULT_OUTLINE_WIDTH = 3;

let loaderConfigured = false;

function loadGoogleMaps(key) {
    const existing = window.google && window.google.maps;

    // Another package may already have bootstrapped the Maps JS API loader —
    // reuse it rather than loading the API a second time.
    if (existing && typeof existing.importLibrary === 'function') {
        return existing.importLibrary('core').then(() => existing.importLibrary('maps'));
    }

    if (!loaderConfigured) {
        setOptions({ key: key, v: 'weekly' });
        loaderConfigured = true;
    }

    // LatLng/LatLngBounds/ControlPosition/event live in 'core'; Map/Polygon in 'maps'.
    return importLibrary('core').then(() => importLibrary('maps'));
}

// Terra Draw only accepts 6-digit hex colors and 0-1 opacities.
function hexColor(value, fallback) {
    if (typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value)) {
        return value;
    }

    return fallback;
}

function opacityValue(value, fallback) {
    const parsed = parseFloat(value);

    return isFinite(parsed) && parsed >= 0 && parsed <= 1 ? parsed : fallback;
}

function widthValue(value, fallback) {
    const parsed = parseFloat(value);

    return isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

export default {
    name: 'PolygonMap',
    props: ['value', 'center', 'shapeOptions', 'readonly'],
    emits: ['change'],
    data: function () {
        return {
            ready: false,
            loadError: null,
            localValue: [...this.value],
            // The Terra Draw Google Maps adapter requires the map container to
            // have an id; make it unique so two fields on one resource work.
            mapId: 'nova-google-polygon-' + Math.random().toString(36).slice(2, 10),
        };
    },
    created() {
        // Deliberately non-reactive: Vue's proxies break identity checks
        // inside the Google Maps and Terra Draw internals.
        this.map = null;
        this.draw = null;
        this.shape = null;
        this.clearButton = null;
        this.projectionListener = null;
        this.featureId = null;
        this.applyingExternal = false;
        this.destroyed = false;
    },
    methods: {
        polygonStyles() {
            return {
                fill: hexColor(this.shapeOptions.fillColor, DEFAULT_FILL_COLOR),
                fillOpacity: opacityValue(this.shapeOptions.fillOpacity, DEFAULT_FILL_OPACITY),
                outline: hexColor(this.shapeOptions.strokeColor, DEFAULT_OUTLINE_COLOR),
                outlineWidth: widthValue(this.shapeOptions.strokeWeight, DEFAULT_OUTLINE_WIDTH),
            };
        },
        buildPolygonMode() {
            const styles = this.polygonStyles();

            return new TerraDrawPolygonMode({
                styles: {
                    fillColor: styles.fill,
                    fillOpacity: styles.fillOpacity,
                    outlineColor: styles.outline,
                    outlineWidth: styles.outlineWidth,
                    closingPointColor: '#ffffff',
                    closingPointOutlineColor: styles.outline,
                    closingPointOutlineWidth: 2,
                },
            });
        },
        buildSelectMode() {
            const styles = this.polygonStyles();

            return new TerraDrawSelectMode({
                flags: {
                    polygon: {
                        feature: {
                            draggable: false,
                            coordinates: {
                                midpoints: true,
                                draggable: true,
                                deletable: true,
                            },
                        },
                    },
                },
                // The Delete key removes the whole selected feature regardless
                // of the coordinate flags above; clearing the polygon happens
                // through the explicit map control instead.
                keyEvents: { deselect: 'Escape', delete: null, rotate: null, scale: null },
                styles: {
                    selectedPolygonColor: styles.fill,
                    selectedPolygonFillOpacity: styles.fillOpacity,
                    selectedPolygonOutlineColor: styles.outline,
                    selectedPolygonOutlineWidth: styles.outlineWidth,
                    selectionPointColor: '#ffffff',
                    selectionPointOutlineColor: styles.outline,
                    selectionPointWidth: 6,
                    selectionPointOutlineWidth: 2,
                    midPointColor: '#ffffff',
                    midPointOutlineColor: styles.outline,
                    midPointWidth: 4,
                    midPointOutlineWidth: 1,
                },
            });
        },
        initDraw() {
            const { google } = window;

            this.draw = new TerraDraw({
                adapter: new TerraDrawGoogleMapsAdapter({
                    lib: google.maps,
                    map: this.map,
                    coordinatePrecision: PRECISION,
                }),
                modes: [this.buildPolygonMode(), this.buildSelectMode()],
            });

            // The adapter creates its OverlayView asynchronously; modes can
            // only be set once 'ready' has fired.
            this.draw.on('ready', () => {
                if (this.destroyed) {
                    return;
                }

                this.applyValueToDraw();
                this.ready = true;
            });

            this.draw.on('finish', (id, context) => {
                if (this.destroyed || this.applyingExternal) {
                    return;
                }

                if (context && context.action === 'draw') {
                    this.featureId = id;
                    this.syncFromDraw();
                    this.enterSelectMode();
                }
            });

            // Selection handles and midpoints are real store features that
            // also emit change events — only ever sync from events that
            // concern the tracked polygon itself, or an untouched form would
            // re-emit (and re-save) rounded coordinates on page load.
            this.draw.on('change', (ids, type, context) => {
                if (this.destroyed || this.applyingExternal) {
                    return;
                }

                if ((context && context.origin === 'api') || type === 'styling') {
                    return;
                }

                if (!this.featureId || ids.indexOf(this.featureId) === -1) {
                    return;
                }

                if (type === 'delete') {
                    this.handleFeatureDeleted();

                    return;
                }

                if (type === 'update' && this.draw.getMode() !== 'polygon') {
                    this.syncFromDraw();
                }
            });

            this.draw.start();
        },
        enterSelectMode() {
            this.draw.setMode('select');

            if (this.featureId && typeof this.draw.selectFeature === 'function') {
                try {
                    this.draw.selectFeature(this.featureId);
                } catch (e) {
                    // Selection is a convenience; the polygon stays clickable.
                    void e;
                }
            }

            this.updateClearButton();
        },
        applyValueToDraw() {
            const { google } = window;

            this.applyingExternal = true;
            this.deselectCurrentFeature();
            this.draw.clear();
            this.featureId = null;

            const ring = valueToRing(this.value);

            if (ring) {
                const id = featureId();
                const results = this.draw.addFeatures([
                    {
                        id: id,
                        type: 'Feature',
                        geometry: { type: 'Polygon', coordinates: [ring] },
                        properties: { mode: 'polygon' },
                    },
                ]);

                if (results.length && results[0].valid === false) {
                    console.warn(
                        '[nova-google-polygon] Stored polygon was rejected and cannot be edited: ' +
                            (results[0].reason || 'unknown reason')
                    );
                    this.draw.setMode('polygon');
                } else {
                    this.featureId = id;
                    this.enterSelectMode();
                    this.fitBoundsToValue();
                }
            } else {
                this.draw.setMode('polygon');
                this.map.setCenter(new google.maps.LatLng(this.center));
                this.map.setZoom(12);
            }

            this.applyingExternal = false;
            this.updateClearButton();
        },
        syncFromDraw() {
            const polygons = this.draw
                .getSnapshot()
                .filter(
                    (feature) =>
                        feature.geometry.type === 'Polygon' &&
                        feature.properties &&
                        feature.properties.mode === 'polygon' &&
                        !feature.properties.selectionPoint &&
                        !feature.properties.midPoint
                );

            if (polygons.length === 0) {
                this.handleFeatureDeleted();

                return;
            }

            if (polygons.length > 1) {
                this.applyingExternal = true;
                this.draw.removeFeatures(polygons.slice(0, -1).map((feature) => feature.id));
                this.applyingExternal = false;
            }

            const feature = polygons[polygons.length - 1];

            this.featureId = feature.id;
            this.updateValue(ringToValue(feature.geometry.coordinates[0]));
            this.updateClearButton();
        },
        handleFeatureDeleted() {
            this.featureId = null;
            this.updateValue([]);
            this.updateClearButton();

            window.requestAnimationFrame(() => {
                if (!this.destroyed && this.draw && this.draw.getMode() !== 'polygon') {
                    this.draw.setMode('polygon');
                }
            });
        },
        onClearClicked() {
            if (!this.draw || !this.featureId) {
                return;
            }

            this.applyingExternal = true;
            this.deselectCurrentFeature();
            this.draw.clear();
            this.applyingExternal = false;
            this.featureId = null;
            this.updateValue([]);
            this.updateClearButton();

            // Deferred so the click that pressed the button cannot leak into
            // the freshly armed drawing mode.
            window.requestAnimationFrame(() => {
                if (!this.destroyed && this.draw) {
                    this.draw.setMode('polygon');
                }
            });
        },
        deselectCurrentFeature() {
            if (this.featureId && this.draw && typeof this.draw.deselectFeature === 'function') {
                try {
                    this.draw.deselectFeature(this.featureId);
                } catch (e) {
                    // Feature may not be selected; nothing to clean up.
                    void e;
                }
            }
        },
        createClearControl() {
            const { google } = window;
            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'nova-google-polygon-clear';
            button.textContent = 'Clear shape';
            button.style.display = 'none';
            button.addEventListener('click', () => this.onClearClicked());

            this.clearButton = button;
            this.map.controls[google.maps.ControlPosition.TOP_LEFT].push(button);
        },
        updateClearButton() {
            if (this.clearButton) {
                this.clearButton.style.display = !this.readonly && this.featureId ? '' : 'none';
            }
        },
        fitBoundsToValue() {
            const { google } = window;

            if (this.value.length === 0) {
                return;
            }

            const bounds = new google.maps.LatLngBounds();

            for (let i = 0; i < this.value.length; i++) {
                bounds.extend(
                    new google.maps.LatLng({
                        lat: parseFloat(this.value[i].lat),
                        lng: parseFloat(this.value[i].lng),
                    })
                );
            }

            this.map.fitBounds(bounds);
        },
        renderStatic() {
            const { google } = window;

            if (this.shape) {
                this.shape.setMap(null);
                this.shape = null;
            }

            if (this.value.length !== 0) {
                this.shape = new google.maps.Polygon({
                    paths: this.value,
                    map: this.map,
                    ...this.shapeOptions,
                    // Static display must never show edit handles, whatever
                    // the shapeOptions carry (FormField hardcodes editable).
                    editable: false,
                    draggable: false,
                    clickable: false,
                });

                this.fitBoundsToValue();
            } else {
                this.map.setCenter(new google.maps.LatLng(this.center));
                this.map.setZoom(12);
            }
        },
        updateValue(value) {
            this.localValue = value;
            this.$emit('change', value);
        },
    },
    watch: {
        value(newValue) {
            if (!this.ready) {
                return;
            }

            if (JSON.stringify(newValue) !== JSON.stringify(this.localValue)) {
                this.localValue = newValue;

                if (this.readonly || !this.draw) {
                    this.renderStatic();
                } else {
                    this.applyValueToDraw();
                }
            }
        },
    },
    async mounted() {
        const config = Nova.config('googlePolygon');

        if (!config || !config.key) {
            this.loadError =
                'Google Maps API key is not configured. Set NOVA_GOOGLE_POLYGON_API_KEY in your .env file.';

            return;
        }

        try {
            await loadGoogleMaps(config.key);
        } catch (e) {
            console.error(
                '[nova-google-polygon] Failed to load the Google Maps JavaScript API.',
                e
            );
            this.loadError =
                'The Google Maps JavaScript API failed to load. Check your network and API key.';

            return;
        }

        if (this.destroyed) {
            return;
        }

        const { google } = window;

        this.map = new google.maps.Map(this.$refs.map, {
            zoom: 12,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            center: new google.maps.LatLng(this.center),
            mapTypeControl: false,
            streetViewControl: false,
            clickableIcons: false,
        });

        if (this.readonly) {
            // Only the drawing library was removed from the Maps JS API;
            // google.maps.Polygon itself is fine for static display.
            this.renderStatic();
            this.ready = true;

            return;
        }

        this.createClearControl();

        // The adapter can only be created once the map has a projection.
        this.projectionListener = google.maps.event.addListenerOnce(
            this.map,
            'projection_changed',
            () => {
                if (this.destroyed) {
                    return;
                }

                this.initDraw();
            }
        );
    },
    beforeUnmount() {
        this.destroyed = true;

        if (this.projectionListener) {
            this.projectionListener.remove();
            this.projectionListener = null;
        }

        if (this.draw) {
            try {
                this.draw.stop();
            } catch (e) {
                // Terra Draw may already have been stopped; teardown races are non-fatal.
                void e;
            }

            this.draw = null;
        }
    },
};
</script>
