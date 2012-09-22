<?php
/**
 * XIIPMooViewer extends CWidget and implements a base class for a IIPMooViewer.
 *
 * IIPMooViewer is a high performance light-weight HTML5 Ajax-based javascript image streaming and zooming client designed
 * for the IIPImage high resolution imaging system. It is compatible with Firefox, Chrome, Internet Explorer (Versions 6-10),
 * Safari and Opera as well as mobile touch-based browsers for iOS and Android. Although designed for use with the IIP protocol
 * and IIPImage, it has multi-protocol support and is additionally compatible with the Zoomify and Deepzoom protocols.
 * Version 2.0 of IIPMooViewer is HTML5/CSS3 based and uses the Mootools javascript framework (version 1.4+).
 * Please refer to the project site http://iipimage.sourceforge.net for further details
 *
 * IMPORTANT!!! When using this widget start html page as follows:
 *    <!doctype html>
 *    <!--[if lt IE 7]><html lang="en" class="lt-ie7"><![endif]-->
 *    <!--[if lt IE 10]><html lang="en" class="ie"><![endif]-->
 *    <!--[if gt IE 9]><!--><html lang="en"><!--<![endif]-->
 *    <head>
 *
 * For IE6 compatibility you should add:
 *    <!--[if lt IE 7]>
 *    <script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE7.js">IE7_PNG_SUFFIX = ".png";</script>
 *    <![endif]-->
 *
 * The following example shows how to use XIIPMooViewer widget:
 * <pre>
 *    $this->widget('ext.widgets.iipimage.iipmooviewer.XIIPMooViewer', array(
 *        'options'=>array(
 *            'image'=>'/path/to/image.tif',
 *            'credit'=>'My Title'
 *        ),
 *        'htmlOptions'=>array(
 *            'style'=>'height: 100%; min-height: 100%; width: 100%; position: absolute; top: 0; left: 0; margin: 0;padding: 0;'
 *        )
 *    ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XIIPMooViewer extends CWidget
{
	/**
	 * @var array the initial JavaScript options that should be passed to the IIPMooViewer plugin.
	 * Possible options include the following (The only obligatory option is the "image" variable):
	 * <pre>
	 * <b>image</b> : The full path to the image.
	 *        This path does not need to be in the web server root directory.
	 *        On Windows as on other systems this should be a UNIX style path such as "/path/to/image.tif"
	 * <b>server</b> : The address of the IIPImage server. [default : "/fcgi-bin/iipsrv.fcgi"]
	 * <b>credit</b> : a credit, copyright or information to be shown on the image itself
	 * <b>render</b> : the way in which tiles are rendered. Either "random" where the
	 *        tiles are fetched and rendered randomly or "spiral" where the
	 *        tiles are rendered from the center outwards [default : "spiral"]
	 * <b>showNavWindow</b> : whether to show the navigation window. [default : true]
	 * <b>showNavButtons</b> : whether to show the navigation buttons on start up: true
	 *         or false [default : true]
	 * <b>navWinSize</b> : ratio of navigation window size to the main window. [default: 0.2]
	 * <b>scale</b> : adds a scale to the image. Specify the number of pixels per mm
	 * <b>prefix</b>: path prefix if image subdirectory moved (for example to a different host) [default "images/"]
	 * <b>enableFullscreen</b> : allow full screen mode. If "native" will attempt to use Javascript Fullscreen API.
	 *         Otherwise it will fill the viewport. "page" allows fullscreen but only in viewport fill mode.
	 *         False disables. [default: "native"]
	 * <b>winResize</b> : whether view is reflowed on window resize. [default: true]
	 * <b>viewport</b> : object containing x, y, resolution, rotation and contrast of initial view
	 * <b>protocol</b> : protocol to use with the server: iip, zoomify or deepzoom [default: "iip"]
	 * <b>annotations</b> : An array of annotations containing struct with parameters x, y, w, h, title, text
	 *         where x, y, w and h are the position and size of the annotation in relative [0-1] values,
	 *         title is an optional title for the annotation and text is the HTML body of the annotation.
	 * </pre>
	 */
	public $options=array();

	/**
	 * @var array HTML attributes for the viewer container tag
	 */
	public $htmlOptions=array();

	/**
	 * @var string language. Options [en | et]. Defaults to 'en'.
	 * If you want to add optional languages, you have to compile
	 * "iipmooviewer-2.0-compressed-xx.js" and include it into assets folder
	 * For more info please refer to http://iipimage.sourceforge.net
	 */
	public $lang='en';

	/**
	 * Renders the open tag of the viewer.
	 * This method also registers the necessary javascript code.
	 */
	public function init()
	{
		if(!isset($this->options['image']))
			throw new CException('The "image" value of "option" array have to be set.');

		$baseUrl=$this->registerClientScript();

		if(!isset($this->options['prefix']))
			$this->options['prefix']=$baseUrl.'/images/';

		$id=$this->getId();
		if (isset($this->htmlOptions['id']))
			$id = $this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id, "new IIPMooViewer('$id',$options);",CClientScript::POS_HEAD);
		echo CHtml::openTag('div',$this->htmlOptions)."\n";
	}

	/**
	 * Renders the close tag of the dialog.
	 */
	public function run()
	{
		echo CHtml::closeTag('div');
	}

	/**
	 * Publish and register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		// publish
		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);

		$cs=Yii::app()->clientScript;

		// meta tags
		$cs->registerMetaTag('IE=edge,chrome=1', null, 'X-UA-Compatible');
		$cs->registerMetaTag('width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;', 'viewport');
		$cs->registerMetaTag('yes', 'apple-mobile-web-app-capable');
		$cs->registerMetaTag('black-translucent', 'apple-mobile-web-app-status-bar-style');

		// css
		$cs->registerCssFile($baseUrl.'/css/iip.compressed.css');
		$cs->registerCssFile($baseUrl.'/css/ie.compressed.css');

		// javascript
		$cs->registerScriptFile($baseUrl.'/javascript/mootools-core-1.4.5-full-nocompat-yc.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($baseUrl.'/javascript/mootools-more-1.4.0.1-compressed.js', CClientScript::POS_HEAD);
		$cs->registerScriptFile($baseUrl.'/javascript/iipmooviewer-2.0-compressed-'.$this->lang.'.js', CClientScript::POS_HEAD);

		return $baseUrl;
	}
}