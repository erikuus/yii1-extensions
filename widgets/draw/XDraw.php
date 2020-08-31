<?php
/**
 * XDraw extends CWidget and implements a base class for the JQuery dRawr.
 *
 * JQuery dRawr is an open source jquery plugin that enables to draw on canvas using different tools and brushes.
 *
 * Note that some significant changes have been made in jquery plugin specially for XDraw widget:
 *
 * - you can customize tool and zoom panel titles
 * - you can set canvas background image
 * - you can fit canvas to window and scroll to center
 * - you can set list of tools/buttons to be used
 * - you can customize each button (order, size, alpha etc)
 * - there is a new tool named "unfilledsquare"
 * - zoom panel and slider are larger
 * - zooming is centered
 *
 * The following example shows how to use XDraw widget:
 *
 * <!doctype html>
 * <html lang=en>
 * <head>
 *     <style>
 *     body {
 *         margin:0px;
 *         -webkit-touch-callout: none;
 *         -webkit-text-size-adjust: none;
 *         -webkit-user-select: none;
 *         overflow: hidden;
 *     }
 * </style>
 * </head>
 * <body>
 * <?php $this->widget('ext.widgets.draw.XDraw', array(
 *    'options'=>array(
 *        'enable_tranparency'=>true,
 *        'canvas_width'=>2536,
 *        'canvas_height'=>1968,
 *        'brushes_title'=>'Tools',
 *        'zoom_title'=>'Zoom',
 *        'background_image'=>'path/to/image.jpg',
 *        'scroll_to_center'=>true,
 *        'fit_to_window'=>true,
 *        'buttons'=>array(
 *            'move'=>array(
 *                'order'=>1
 *            ),
 *            'pen'=>array(
 *                'order'=>2,
 *                'size'=>15,
 *                'icon'=>'mdi mdi-grease-pencil mdi-24px'
 *            ),
 *            'brush'=>array(
 *                'order'=>3,
 *                'size'=>30
 *            ),
 *            'airbrush'=>array(
 *                'order'=>4,
 *                'size'=>150,
 *                'alpha'=>0.5
 *            ),
 *            'filledsquare'=>array(
 *                'order'=>5
 *            ),
 *            'unfilledsquare'=>array(
 *                'order'=>6
 *            ),
 *            'eraser'=>array(
 *                'order'=>7,
 *                'size'=>50
 *            )
 *        )
 *    ),
 *    'containerHtmlOptions'=>array(
 *        'width'=>'100vw',
 *        'height'=>'100vh',
 *        'overflow'=>'hidden'
 *    ),
 *    'enableDownloadButton'=>true,
 *    'enableUploadButton'=>true,
 *    'enableSaveButton'=>true,
 *    'saveUrl'=>$this->createUrl('save'),
 *    'loadFile'=>'path/to/layer.png'
 * )); ?>
 * </body>
 * </html>
 *
 * @link https://github.com/avokicchi/jquery-drawr
 * @link https://github.com/erikuus/jquery-drawr-php-imagick
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XDraw extends CWidget
{
	/**
	 * @var array $options the initial JavaScript options that should be passed to the Drawr plugin
	 *
	 * Possible options include the following:
	 *
	 * enable_tranparency - whether canvas is transparent [default: true]
	 * canvas_width - width of the canvas [default: container width]
	 * canvas_height - height of the canvas [default: container height]
	 * undo_max_levels - the number of undoable actions [default: 5]
	 * color_mode - the color palette mode [default: "picker", options: "picker","presets"]
	 * clear_on_init - whether to clean canvas when initialized [default: true]
	 * brushes_title - title of the brushes/tools/buttons panel [default: "Brushes"]
	 * zoom_title - title of the zoom panel [default: "Zoom"]
	 * background_image - the path to canvas background image [default: ""]
	 * scroll_to_center - whether to center the canvas [default: false]
	 * fit_to_window - whether to fit canvas to window [default: false]
	 * buttons - The list of buttons (brushes/tools) to be displayed [default: {}]
	 *     By default all available buttons are displayed. By setting this list you can select set of buttons
	 *     to be displayed and customize each button. Options: "pencil","pen","brush","eyedropper","airbrush",
	 *     "eraser","square","filledsquare","unfilledsquare","move","marker","text","palette". For example:
	 *     ```php
	 *     array(
	 *         'move'=>array(
	 *             'order'=>1
	 *         ),
	 *         'pen'=>array(
	 *             'order'=>2,
	 *             'size'=>15,
	 *             'icon'=>'mdi mdi-grease-pencil mdi-24px'
	 *         ),
	 *         'brush'=>array(
	 *              'order'=>3,
	 *              'size'=>30
	 *         ),
	 *         'airbrush'=>array(
	 *              'order'=>4,
	 *              'size'=>150,
	 *              'alpha'=>0.5
	 *         ),
	 *         'filledsquare'=>array(
	 *              'order'=>5
	 *         ),
	 *         'unfilledsquare'=>array(
	 *              'order'=>6
	 *         ),
	 *         'eraser'=>array(
	 *              'order'=>7,
	 *              'size'=>50
	 *         )
	 *     )
	 *     ```
	 */
	public $options=array();
	/**
	 * @var array the HTML attributes for the container tag
	 * Defaults to array()
	 */
	public $containerHtmlOptions=array();
	/**
	 * @var array the HTML attributes for the canvas tag
	 * Defaults to array().
	 */
	public $canvasHtmlOptions=array();
	/**
	 * @var boolean whether to enable button that uploads drawing
	 * Defaults to false
	 */
	public $enableUploadButton=false;
	/**
	 * @var string the icon class name for upload button
	 * Defaults to 'mdi mdi-download mdi-24px'
	 */
	public $uploadButtonIcon='mdi mdi-folder-open mdi-24px';
	/**
	 * @var boolean whether to enable button that downloads drawing
	 * Defaults to false
	 */
	public $enableDownloadButton=false;
	/**
	 * @var string the icon class name for download button
	 * Defaults to 'mdi mdi-download mdi-24px'
	 */
	public $downloadButtonIcon='mdi mdi-download mdi-24px';
	/**
	 * @var string the mimetype of drawing to be downloaded
	 * Defaults to 'image/png'
	 */
	public $downloadMimetype='image/png';
	/**
	 * @var string the filename of drawing to be downloaded
	 * Defaults to 'drawing.png'
	 */
	public $downloadFilename='drawing.png';
	/**
	 * @var boolean whether to enable button that saves drawing
	 * Defaults to false
	 */
	public $enableSaveButton=false;
	/**
	 * @var string the icon class name for save button
	 * Defaults to 'mdi mdi-content-save mdi-24px'
	 */
	public $saveButtonIcon='mdi mdi-content-save mdi-24px';
	/**
	 * @var string the mimetype of drawing to be saved
	 * Defaults to 'image/png'
	 */
	public $saveMimetype='image/png';
	/**
	 * @var string the url to the save action that is called when save button is clicked
	 *
	 * Note that variable named "imagedata" is posted to save action. The value of this variable
	 * is the base64 encoded content of drawing in the format of inline image. Inside this action
	 * you can save drawing as text into database or as image into filesystem. For example:
	 *
	 * ```php
	 * if (isset($_POST['imagedata']) && $_POST['imagedata']) {
	 *     $layerBase64 = preg_replace('#^data:image/[^;]+;base64,#', '', $_POST['imagedata']);
	 *     $layerBlob = base64_decode($layerBase64);
	 *     if (file_put_contents('img/layer.png', $layerBlob))
	 *         echo 'Saved image data!';
	 *     } else {
	 *         echo 'Saving image data failed!';
	 *     }
	 * } else {
	 *     echo 'Could not find image data!';
	 * }
	 * ```
	 */
	public $saveUrl;
	/**
	 * @var string the path to drawing image file that will be loaded into a canvas on start
	 */
	public $loadFile;
	/**
	 * @var string the base64 encoded content of drawing image that will be loaded into a canvas on start
	 *
	 * Note that this string must be in inline image format, for example "data:image/png;base64,iVBORw0KGgoAAAANS..."
	 * Also note that this property is used only if {@link loadFile} is not defined
	 */
	public $loadBase64;
	/**
	 * @var string the url to css file that enables to implementing icons as a web font
	 * Defaults to 'https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/3.6.95/css/materialdesignicons.css'
	 */
	public $iconCssUrl='https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/3.6.95/css/materialdesignicons.css';

	/**
	 * Renders the open tag of the viewer.
	 * This method also registers the necessary javascript code.
	 */
	public function init()
	{
		if(isset($this->htmlOptions['id']))
			$this->id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$this->id;

		$this->registerClientScriptFiles();
		$this->registerClientScript();

		echo CHtml::openTag('div', $this->containerHtmlOptions)."\n";
		echo CHtml::openTag('canvas', $this->canvasHtmlOptions)."\n";
	}

	/**
	 * Renders the close tags and file input.
	 */
	public function run()
	{
		echo CHtml::closeTag('canvas')."\n";
		echo CHtml::closeTag('div')."\n";

		if($this->enableUploadButton)
			echo "<input type=\"file\" id=\"{$this->id}-file-picker\" style=\"display:none;\">\n";
	}

	/**
	 * Publish and register necessary client script files.
	 */
	protected function registerClientScriptFiles()
	{
		$cs=Yii::app()->clientScript;

		// publish
		$assets=dirname(__FILE__).'/assets';
		$baseUrl=Yii::app()->assetManager->publish($assets);

		// css file
		$cs->registerCssFile($this->iconCssUrl);

		// core script
		$cs->registerCoreScript('jquery');

		// drawr jquery plugin
		$cs->registerScriptFile($baseUrl.'/jquery.drawr.combined.js', CClientScript::POS_HEAD);
	}

	/**
	 * Register necessary inline client scripts.
	 */
	protected function registerClientScript()
	{
		$cs=Yii::app()->clientScript;

		// set meta tag
		$cs->registerMetaTag('width=device-width, initial-scale=1', 'viewport');

		// prepare options
		$options=CJavaScript::encode($this->options);

		// register and start plugin
		$script=<<<SCRIPT
			$("#{$this->id}").drawr({$options});
			$("#{$this->id}").drawr("start");
		SCRIPT;

		// preload drawing from file or as base64 string
		if($this->loadFile)
		{
			$errorMessage=Yii::t('XDraw.draw', 'Could not load drawing!');
			$script.=<<<SCRIPT
				$.ajax({
					url: "{$this->loadFile}",
					cache: false,
					xhr: function(){
						var xhr = new XMLHttpRequest();
						xhr.responseType= 'blob'
						return xhr;
					},
					success: function(data){
						var reader = new FileReader();
						reader.onloadend = function () {
							$("#{$this->id}").drawr("load", reader.result);
						}
						reader.readAsDataURL(data);
					},
					error: function(){
						alert("{$errorMessage}");
					}
				});
			SCRIPT;
		}
		elseif($this->loadBase64)
		{
			$script.=<<<SCRIPT
				$("#{$this->id}").drawr("load", {$this->loadBase64}});
			SCRIPT;
		}

		// register upload button
		if($this->enableUploadButton)
		{
			$script.=<<<SCRIPT
				var buttoncollection = $("#{$this->id}").drawr("button", {
					"icon": "{$this->uploadButtonIcon}"
				}).on("touchstart mousedown", function() {
				    $("#{$this->id}-file-picker").click();
				});
				$("#file-picker")[0].onchange = function(){
					var file = $("#{$this->id}-file-picker")[0].files[0];
					if (!file.type.startsWith("image/")){return}
					var reader = new FileReader();
					reader.onload = function(e) {
						$("#{$this->id}").drawr("load",e.target.result);
					};
					reader.readAsDataURL(file);
				};
			SCRIPT;
		}

		// register download button
		if($this->enableDownloadButton)
		{
			$script.=<<<SCRIPT
				var buttoncollection = $("#{$this->id}").drawr("button", {
					"icon": "{$this->downloadButtonIcon}"
				}).on("touchstart mousedown", function() {
					var imagedata = $("#{$this->id}").drawr("export","{$this->downloadMimetype}");
					var element = document.createElement("a");
					element.setAttribute("href", imagedata);
					element.setAttribute("download", "{$this->downloadFilename}");
					element.style.display = "none";
					document.body.appendChild(element);
					element.click();
					document.body.removeChild(element);
				});
			SCRIPT;
		}

		// register save button
		if($this->enableSaveButton)
		{
			$script.=<<<SCRIPT
				var buttoncollection = $("#{$this->id}").drawr("button", {
					"icon": "{$this->saveButtonIcon}"
				}).on("touchstart mousedown", function() {
					var imagedata = $("#{$this->id}").drawr("export","{$this->saveMimetype}");
					$.post("{$this->saveUrl}", {imagedata: imagedata}, function(data) {
						alert(data);
					});
				});
			SCRIPT;
		}

		$cs->registerScript(__CLASS__.'#'.$this->id, $script, CClientScript::POS_READY);
	}
}
