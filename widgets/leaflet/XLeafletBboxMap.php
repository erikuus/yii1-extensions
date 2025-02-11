<?php

/**
 * XLeafletBboxMap class file
 *
 * Widget to display a Leaflet map with a bounding-box rectangle (if provided).
 * Uses Leaflet + OpenStreetMap for map tiles (no API key needed).
 *
 * Usage:
 * ~~~
 *   $this->widget('ext.widgets.leaflet.XLeafletBboxMap', [
 *       'model'       => $model,        // your model with bounding box attributes
 *       'defaultZoom' => 6,             // override default zoom if needed
 *       'width'       => '600px',       // override map width
 *       'height'      => '400px',       // override map height
 *   ]);
 * ~~~
 */
class XLeafletBboxMap extends CWidget
{
	/**
	 * @var integer the default zoom level for the map
	 */
	public $defaultZoom = 6;

	/**
	 * @var float the default center longitude for the map
	 */
	public $defaultCeLon = 25.048828;

	/**
	 * @var float the default center latitude for the map
	 */
	public $defaultCeLat = 58.568252;

	/**
	 * @var string the width for the map container
	 */
	public $width = '470px';

	/**
	 * @var string the height for the map container
	 */
	public $height = '300px';

	/**
	 * @var string the CSS style definitions for the map container
	 */
	public $cssStyles = 'margin: 10px 0';

	/**
	 * @var CModel the data model associated with this widget.
	 * Must contain the relevant latitude/longitude/zoom attributes.
	 */
	public $model;

	/**
	 * @var string the model attribute name for the map's center latitude
	 */
	public $ce_lat = 'ce_lat';

	/**
	 * @var string the model attribute name for the map's center longitude
	 */
	public $ce_lon = 'ce_lon';

	/**
	 * @var string the model attribute name for the map's zoom level
	 */
	public $zoom = 'zoom';

	/**
	 * @var string the model attribute for bounding-box SW latitude
	 */
	public $sw_lat = 'sw_lat';

	/**
	 * @var string the model attribute for bounding-box SW longitude
	 */
	public $sw_lon = 'sw_lon';

	/**
	 * @var string the model attribute for bounding-box NE latitude
	 */
	public $ne_lat = 'ne_lat';

	/**
	 * @var string the model attribute for bounding-box NE longitude
	 */
	public $ne_lon = 'ne_lon';

	/**
	 * Initializes the widget.
	 * Checks that the model is provided.
	 */
	public function init()
	{
		if (!$this->model) {
			throw new CException('"model" must be set!');
		}
	}

	/**
	 * Renders the widget.
	 * Outputs the <div> that will contain our Leaflet map.
	 */
	public function run()
	{
		$id = $this->getId();
		$this->registerClientScript();

		// The container for Leaflet map
		echo "<div id=\"{$id}_map_canvas\" style=\"width:{$this->width}; height:{$this->height}; {$this->cssStyles}\"></div>\n";
	}

	/**
	 * Check if the model has valid center lat, center lon, and zoom
	 * @return bool
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
	 * Check if the model has valid bounding box coordinates
	 * @return bool
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
	 * Registers the Leaflet scripts and initialization JS.
	 */
	protected function registerClientScript()
	{
		$cs     = Yii::app()->clientScript;
		$id     = $this->getId();
		$mapDiv = $id . '_map_canvas';

		// 1) Register Leaflet CSS & JS
		//    (Using the official Leaflet CDN; you can also host locally if you prefer)
		$cs->registerCssFile('https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
		$cs->registerScriptFile('https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', CClientScript::POS_END);

		// 2) Determine center coordinates and zoom
		$ceLat = $this->hasCenterAndZoom()
			? $this->model->{$this->ce_lat}
			: $this->defaultCeLat;
		$ceLon = $this->hasCenterAndZoom()
			? $this->model->{$this->ce_lon}
			: $this->defaultCeLon;
		$zoom  = $this->hasCenterAndZoom()
			? $this->model->{$this->zoom}
			: $this->defaultZoom;

		// 3) (Optional) bounding box variables
		$swLat = $this->model->{$this->sw_lat};
		$swLon = $this->model->{$this->sw_lon};
		$neLat = $this->model->{$this->ne_lat};
		$neLon = $this->model->{$this->ne_lon};

		// 4) Build the JavaScript code that initializes Leaflet, sets center, draws bounding box if present
		$initJs = "
			// Initialize Leaflet map
			var map = L.map('$mapDiv').setView([$ceLat, $ceLon], $zoom);

			// Add OpenStreetMap tiles
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				maxZoom: 19,
				attribution: '© OpenStreetMap'
			}).addTo(map);
		";

		// If bounding box is set, fit to it and draw a rectangle
		if ($this->hasBounds()) {
			$initJs .= "
				var southWest = L.latLng($swLat, $swLon);
				var northEast = L.latLng($neLat, $neLon);
				var bounds = L.latLngBounds(southWest, northEast);

				// Fit the map to the bounding box
				map.fitBounds(bounds);

				// Draw the bounding box rectangle
				L.rectangle(bounds, {
					color: 'red',
					weight: 2,
					fillColor: 'yellow',
					fillOpacity: 0.3
				}).addTo(map);
			";
		}

		// 5) Register the script
		$cs->registerScript(__CLASS__ . '#' . $id, $initJs, CClientScript::POS_END);
	}
}