<?php
/**
 * XLeafletInputMap class file
 *
 * Widget to implement a Leaflet + OpenStreetMap-based map as a form input.
 * Users can draw a rectangle bounding box, which automatically updates hidden fields for:
 *   - Map center latitude/longitude
 *   - Map zoom level
 *   - Bounding box corners (SW/NE lat/lon)
 *
 * Usage in a form view:
 * ~~~
 *  $this->widget('ext.widgets.leaflet.XLeafletInputMap', [
 *      'form'         => $form,
 *      'model'        => $model,
 *      'defaultZoom'  => 6,
 *      'defaultCeLat' => 58.568252,
 *      'defaultCeLon' => 25.048828,
 *      'width'        => 470,
 *      'height'       => 300,
 *      'enableHintText' => true,
 *      'enableClearMap' => true,
 *      // attributes => sw_lat, sw_lon, ne_lat, ne_lon, ce_lat, ce_lon, zoom
 *  ]);
 * ~~~
 */
class XLeafletInputMap extends CInputWidget
{
    /**
     * @var int default zoom level for the map
     */
    public $defaultZoom = 6;

    /**
     * @var float default center latitude
     */
    public $defaultCeLat = 58.568252;

    /**
     * @var float default center longitude
     */
    public $defaultCeLon = 25.048828;

    /**
     * @var int width for map in pixels
     */
    public $width = 470;

    /**
     * @var int height for map in pixels
     */
    public $height = 300;

    /**
     * @var string custom inline CSS for the map container
     */
    public $cssStyles = 'margin:10px 0';

    /**
     * @var bool whether to show a brief hint text above the map
     */
    public $enableHintText = true;

    /**
     * @var bool whether to show a "Clear map" link under the map
     */
    public $enableClearMap = true;

    /**
     * @var CActiveForm the form associated with this widget
     */
    public $form;

    /**
     * @var CModel the data model associated with this widget
     */
    public $model;

    /**
     * @var string attribute name for map center latitude
     */
    public $ce_lat = 'ce_lat';

    /**
     * @var string attribute name for map center longitude
     */
    public $ce_lon = 'ce_lon';

    /**
     * @var string attribute name for the map zoom
     */
    public $zoom = 'zoom';

    /**
     * @var string attribute name for rectangle SW latitude
     */
    public $sw_lat = 'sw_lat';

    /**
     * @var string attribute name for rectangle SW longitude
     */
    public $sw_lon = 'sw_lon';

    /**
     * @var string attribute name for rectangle NE latitude
     */
    public $ne_lat = 'ne_lat';

    /**
     * @var string attribute name for rectangle NE longitude
     */
    public $ne_lon = 'ne_lon';

    /**
     * @var string the Leaflet tile layer URL template.
     */
    public $tileLayerUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

    /**
     * @var string the attribution text displayed on the map for the tile layer.
     * For example: 'Map data © OpenStreetMap contributors'
     */
    public $tileLayerAttribution = '© OpenStreetMap';

    /**
     * Widget initialization
     */
    public function init()
    {
        if (!$this->form) {
            throw new CException('"form" must be set!');
        }

        if (!$this->model) {
            throw new CException('"model" must be set!');
        }

        // If neither bounding-box nor center/zoom is prefilled,
        // fall back to default center & zoom.
        if (!$this->hasBounds() && !$this->hasCenterAndZoom()) {
            $this->model->{$this->ce_lat} = $this->defaultCeLat;
            $this->model->{$this->ce_lon} = $this->defaultCeLon;
            $this->model->{$this->zoom}   = $this->defaultZoom;
        }
    }

    /**
     * Render the widget output
     */
    public function run()
    {
        $id = $this->getId();
        $this->registerClientScripts($id);

        // Optional hint text
        if ($this->enableHintText) {
            echo Yii::t(__CLASS__ . '.' . __CLASS__, 'Click the rectangle button to draw a box. Click the edit button to move or resize it.');
        }

        // The map container
        echo CHtml::tag('div', [
            'id'    => $id . '_map_canvas',
            'style' => "width:{$this->width}px; height:{$this->height}px; overflow:hidden; {$this->cssStyles}"
        ], '', true);

        // Clear link
        if ($this->enableClearMap) {
            echo CHtml::link(Yii::t(__CLASS__ . '.' . __CLASS__, 'Clear map'), '#', [
                'onclick' => $id . '_clearMap(); return false;'
            ]);
        }

        // Hidden fields
        echo $this->form->hiddenField($this->model, $this->ce_lat, ['id' => $id . '_ce_lat']);
        echo $this->form->hiddenField($this->model, $this->ce_lon, ['id' => $id . '_ce_lon']);
        echo $this->form->hiddenField($this->model, $this->zoom,   ['id' => $id . '_zoom']);

        echo $this->form->hiddenField($this->model, $this->sw_lat, ['id' => $id . '_sw_lat']);
        echo $this->form->hiddenField($this->model, $this->sw_lon, ['id' => $id . '_sw_lon']);
        echo $this->form->hiddenField($this->model, $this->ne_lat, ['id' => $id . '_ne_lat']);
        echo $this->form->hiddenField($this->model, $this->ne_lon, ['id' => $id . '_ne_lon']);
    }

    /**
     * Determine if we already have a bounding box in the model
     */
    protected function hasBounds()
    {
        return (
            !empty($this->model->{$this->sw_lat}) &&
            !empty($this->model->{$this->sw_lon}) &&
            !empty($this->model->{$this->ne_lat}) &&
            !empty($this->model->{$this->ne_lon})
        );
    }

    /**
     * Determine if we already have center/zoom in the model
     */
    protected function hasCenterAndZoom()
    {
        return (
            !empty($this->model->{$this->ce_lat}) &&
            !empty($this->model->{$this->ce_lon}) &&
            !empty($this->model->{$this->zoom})
        );
    }

    /**
     * Registers the Leaflet + Leaflet Draw scripts and the initialization JS
     */
    protected function registerClientScripts($id)
    {
        $cs = Yii::app()->clientScript;

        // Include the CSS/JS
        $cs->registerCssFile('https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
        $cs->registerScriptFile('https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', CClientScript::POS_END);
        $cs->registerCssFile('https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css');
        $cs->registerScriptFile('https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js', CClientScript::POS_END);

        // Map defaults
        $ceLat = $this->hasCenterAndZoom()
            ? $this->model->{$this->ce_lat}
            : $this->defaultCeLat;

        $ceLon = $this->hasCenterAndZoom()
            ? $this->model->{$this->ce_lon}
            : $this->defaultCeLon;

        $zoom  = $this->hasCenterAndZoom()
            ? $this->model->{$this->zoom}
            : $this->defaultZoom;

        // Possibly pre-existing bounding box
        $swLat = $this->model->{$this->sw_lat};
        $swLon = $this->model->{$this->sw_lon};
        $neLat = $this->model->{$this->ne_lat};
        $neLon = $this->model->{$this->ne_lon};

        // Build our JS
        $js = <<<JS
(function() {
    var mapDiv = document.getElementById('{$id}_map_canvas');
    if (!mapDiv) return;

    // 1) Initialize Leaflet map
    var map = L.map(mapDiv).setView([$ceLat, $ceLon], $zoom);

    // 2) Add OpenStreetMap tile layer
    L.tileLayer('{$this->tileLayerUrl}', {
        maxZoom: 19,
        attribution: '{$this->tileLayerAttribution}'
    }).addTo(map);

    // 3) Track map center + zoom changes
    map.on('moveend', function() {
        var center = map.getCenter();
        document.getElementById('{$id}_ce_lat').value = center.lat.toFixed(6);
        document.getElementById('{$id}_ce_lon').value = center.lng.toFixed(6);
    });
    map.on('zoomend', function() {
        document.getElementById('{$id}_zoom').value = map.getZoom();
    });

    // 4) Prepare a FeatureGroup to store our rectangle
    var drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    // 5) Create draw controls (only rectangle)
    var drawControl = new L.Control.Draw({
        draw: {
            polygon: false,
            polyline: false,
            circle: false,
            circlemarker: false,
            marker: false,
            rectangle: {
              shapeOptions: {
                    color: 'red',
                    weight: 2,
                    fillColor: 'yellow',
                    fillOpacity: 0.3
                }
            },
        },
        edit: {
            featureGroup: drawnItems,
            remove: false
        }
    });
    map.addControl(drawControl);

    // Helper to update hidden fields from the bounds of a rectangle
    function updateBoundsFromRectangle(rect) {
        var b = rect.getBounds();
        var sw = b.getSouthWest();
        var ne = b.getNorthEast();
        document.getElementById('{$id}_sw_lat').value = sw.lat.toFixed(6);
        document.getElementById('{$id}_sw_lon').value = sw.lng.toFixed(6);
        document.getElementById('{$id}_ne_lat').value = ne.lat.toFixed(6);
        document.getElementById('{$id}_ne_lon').value = ne.lng.toFixed(6);
    }

    // 6) Listen for new rectangles
    map.on('draw:created', function(e) {
        if (e.layerType === 'rectangle') {
            // Remove any existing shape so there's only 1 bounding box
            drawnItems.clearLayers();
            drawnItems.addLayer(e.layer);
            updateBoundsFromRectangle(e.layer);
        }
    });

    // 7) Listen for edits to the existing rectangle
    map.on('draw:edited', function(e) {
        e.layers.eachLayer(function(layer) {
            if (layer instanceof L.Rectangle) {
                updateBoundsFromRectangle(layer);
            }
        });
    });

    // 8) Listen for deleted rectangle
    map.on('draw:deleted', function(e) {
        e.layers.eachLayer(function(layer) {
            if (layer instanceof L.Rectangle) {
                // If the user uses the delete tool, also clear hidden fields
                document.getElementById('{$id}_sw_lat').value = '';
                document.getElementById('{$id}_sw_lon').value = '';
                document.getElementById('{$id}_ne_lat').value = '';
                document.getElementById('{$id}_ne_lon').value = '';
            }
        });
    });

    // 9) If there's already bounding box data, draw a rectangle
    if ('{$swLat}' && '{$swLon}' && '{$neLat}' && '{$neLon}') {
        // Convert from strings to floats
        var swLatF = parseFloat('{$swLat}');
        var swLonF = parseFloat('{$swLon}');
        var neLatF = parseFloat('{$neLat}');
        var neLonF = parseFloat('{$neLon}');
        var bounds = L.latLngBounds([swLatF, swLonF], [neLatF, neLonF]);

        var existingRect = L.rectangle(bounds, {
            color: 'red',
            weight: 2,
            fillColor: 'yellow',
            fillOpacity: 0.3
        });
        drawnItems.addLayer(existingRect);
        map.fitBounds(bounds);
    }

    // 10) Clear map function
    window.{$id}_clearMap = function() {
        drawnItems.clearLayers();
        document.getElementById('{$id}_sw_lat').value = '';
        document.getElementById('{$id}_sw_lon').value = '';
        document.getElementById('{$id}_ne_lat').value = '';
        document.getElementById('{$id}_ne_lon').value = '';
    };
})();
JS;

        // Register the script at end of the page
        $cs->registerScript(__CLASS__ . '#' . $id, $js, CClientScript::POS_END);
    }
}