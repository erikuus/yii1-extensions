<?php
/**
 * XIIPZoomifyViewer extends CWidget and implements a base class for Zoomify Flash Fullscreen Viewer.
 *
 * Zoomify makes high-quality images zoom-and-pan for fast, interactive viewing on the web.
 * Please refer to the Zoomify project site http://www.zoomify.com/flash.htm for further details.
 * Please refer to the IIPImage project site http://iipimage.sourceforge.net for further details.
 *
 * The following example shows how to use XIIPZoomifyViewer widget:
 * <pre>
 *    $this->widget('ext.widgets.iipimage.iipzoomifyviewer.XIIPZoomifyViewer', array(
 *        'options'=>array(
 *            'zoomifyImagePath'=>'/fcgi-bin/iipsrv.fcgi?zoomify=/path/to/image.tif'
 *        ),
 *    ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XIIPZoomifyViewer extends CWidget
{
	/**
	 * @var array of zoomify flash options.
	 * <pre>
	 * zoomifyImagePath=ZoomifyImageExample	relative or absolute URL path to image folder
	 * zoomifyInitialX=center	initial view horizontal position in pixels from left edge of image*
	 * zoomifyInitialY=center	initial view vertical position in pixels from top edge of image*
	 * zoomifyInitialZoom=-1	1 to 100 (percent), default is -1 to zoom image to fit view area
	 * zoomifyMinZoom=-1		permitted zoom-out percent, default is -1 to zoom-to-fit
	 * zoomifyMaxZoom=100	default is 100, over 110 distorts most images
	 * zoomifySplashScreen=1	0 to hide, 1 to show (replacing splash requires Flash editing)
	 * zoomifyClickZoom=1	0 to disable, 1 to enable (click zooms to next image tier)
	 * zoomifyZoomSpeed=5	rate of zoom, 1 is very slow, 20 is very fast, default is 5
	 * zoomifyFadeInSpeed=300	duration in milliseconds, 0 is instant, 1000 is slow, default 300
	 * zoomifyPanConstrain=1	0 for now contraint, default is 1
	 * zoomifyToolbarVisible=1	0 to hide, 1 to show, default is 1
	 * zoomifySliderVisible=1	0 to hide, 1 to show, default is 1
	 * zoomifyToolbarLogo=0	0 to hide, 1 to show, (replacing splash requires Flash editing)
	 * zoomifyToolbarTooltips=1	0 to disable, 1 to enable, default is 1
	 * zoomifyToolbarSpacing=7	button spacing in pixels, 1 is minimal, 20 is wide, default is 7
	 * zoomifyNavigatorVisible=0	0 to hide, 1 to show, default is 1
	 * zoomifyNavigatorWidth=200	width in pixels, default is 130, useful max is thumbnail width
	 * zoomifyNavigatorHeight=200	height in pixels, default is 130, useful max is thumbnail height
	 * zoomifyNavigatorX=0	distance from stage left edge, 0 is default
	 * zoomifyNavigatorY=0	distance from stage top edge, 0 is default
	 * zoomifyEvents=0	0 to disable, 1 to enable, default is 0
	 * </pre>
	 */
	public $options=array();

	/**
	 * @var array HTML attributes for the viewer container tag
	 */
	public $htmlOptions=array();

	/**
	 * Checks required properties.
	 * Renders the open tag of the container.
	 */
	public function init()
	{
		if(!isset($this->options['zoomifyImagePath']))
			throw new CException('The "zoomifyImagePath" value of "option" array have to be set.');

		echo CHtml::openTag('div',$this->htmlOptions)."\n";
	}

	/**
	 * Renders flash object and container close tag
	 */
	public function run()
	{
		$baseUrl=$this->registerClientScript();
		$source=$baseUrl.'/zoomifyFullscreenViewer.swf';
		$flashvars=http_build_query($this->options,'','&amp;');

		echo '
		<object type="application/x-shockwave-flash" data="'.$source.'" width="100%" height="100%">
			<param name="flashvars" value="'.$flashvars.'" />
			<param name="src" value="'.$source.'" />
			<param name="menu" value="false" />
			<param name="allowFullScreen" value="true" />
			<param name="bgcolor" value="#FFFFFF" />
		</object>
		';

		echo CHtml::closeTag('div');
	}

	/**
	 * Publish and register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		// For some reason flash height 100% overflows viewport by few pixels
		Yii::app()->clientScript->registerCss(__CLASS__, 'body {overflow: hidden;}', 'screen', CClientScript::POS_HEAD);

		// Publish swf file
		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);
		return $baseUrl;
	}
}