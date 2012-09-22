<?php
/**
 * XIIPImageWindow extends CWidget and implements a base class for IIP image viewer window.
 *
 * XIIPImageWindow enables to configure pager and different viewer widgets to display iip image in full page.
 * Please refer to the IIPImage project site http://iipimage.sourceforge.net for further details.
 *
 * The following example shows how to use XIIPImageWindow with XIIPZoomifyViewer:
 * <pre>
 * $this->widget('ext.widgets.iipimage.iipimagewindow.XIIPImageWindow', array(
 *     'imageData'=>array(0=>array('id'=>1,'filename'=>'/path/to/image.tif')),
 *     'widgetConfig'=>array(
 *         'class'=>'ext.widgets.iipimage.iipzoomifyviewer.XIIPZoomifyViewer',
 *         'config'=>array(
 *             'options'=>array(
 *                  'zoomifyImagePath'=>'Yii::app()->params["iipServer"]."?zoomify=".$filename' // php expression
 *             )
 *         )
 *     )
 * ));
 * </pre>
 *
 * The following example shows how to use XIIPImageWindow with XIIPMooViewer:
 * <pre>
 * $this->widget('ext.widgets.iipimage.iipimagewindow.XIIPImageWindow', array(
 *     'imageData'=>array(0=>array('id'=>1,'filename'=>'/path/to/image.tif')),
 *     'widgetConfig'=>array(
 *         'class'=>'ext.widgets.iipimage.iipmooviewer.XIIPMooViewer',
 *         'config'=>array(
 *             'options'=>array(
 *                 'server'=>'Yii::app()->params["iipServer"]',
 *                 'image'=>'$filename', // php expression
 *                 'credit'=>'$id' // php expression
 *             )
 *         )
 *     )
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XIIPImageWindow extends CWidget
{
	/**
	 * @var array of image filenames. The array elements must use zero-based integer keys.
	 * Example: array([0]=>array('id'=>1,'filename'=>'/path/to/image.tif'));
	 */
	public $imageData=array();

	/**
	 * @var array of widget configuration.
	 * Example: array('class'=>'ext.widgets.iipimage.iipmooviewer.XIIPMooViewer')
	 */
	public $widgetConfig=array();

	/**
	 * @var array the configuration for the pager.
	 * Defaults to <code>array('cssFile'=>false)</code>.
	 */
	public $pager=array('cssFile'=>false);

	/**
	 * @var string the CSS class name for the pager container.
	 * Defaults to 'iip-image-window-pager'.
	 */
	public $pagerCssClass='iip-image-window-pager';

	/**
	 * @var string the CSS class name for the widget container
	 * if there are many images so that also pager is displayed.
	 * Defaults to '.iip-image-window-viewer-multiple'.
	 */
	public $viewerMultipleCssClass='iip-image-window-viewer-multiple';

	/**
	 * @var string the CSS class name for the widget container
	 * if there are single images so that pager is not displayed.
	 * Defaults to '.iip-image-window-viewer-single'.
	 */
	public $viewerSingleCssClass='iip-image-window-viewer-single';

	/**
	 * Initialize the widget.
	 */
	public function init()
	{
		if(!isset($this->imageData[0]))
			throw new CException('The "imageData" property cannot be empty. Array elements must use zero-based integer keys.');

		if(!isset($this->widgetConfig['class']))
			throw new CException('The "class" value of "widgetConfig" array have to be set.');

		if(!isset($this->widgetConfig['config']))
			throw new CException('The "config" value of "widgetConfig" array have to be set.');
	}

	/**
	 * Publish and register necessary client scripts.
	 * Render pager and viewer widget
	 */
	public function run()
	{
		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);

		$cs=Yii::app()->clientScript;
		$cs->registerCssFile($baseUrl.'/layout.css');
		$cs->registerCssFile($baseUrl.'/pager.css');

		$this->renderPager();
		$this->renderViewer();
	}

	/**
	 * Renders the pager.
	 */
	protected function renderPager()
	{
		if(count($this->imageData)<2)
			return;

		$pager=array();
		$class='CLinkPager';
		if(is_string($this->pager))
			$class=$this->pager;
		else if(is_array($this->pager))
		{
			$pager=$this->pager;
			if(isset($pager['class']))
			{
				$class=$pager['class'];
				unset($pager['class']);
			}
		}

		// When using CArrayDataProvider, you cannot load the pagination
		// properly before the data is loaded via ->getData().
		$dataProvider=$this->dataProvider;
		$dataProvider->getData();
		$pager['pages']=$dataProvider->getPagination();

		echo '<div class="'.$this->pagerCssClass.'">';
			$this->widget($class,$pager);
		echo '</div>';
	}

	/**
	 * Renders widget
	 */
	public function renderViewer()
	{
		$this->widgetConfig['config']['htmlOptions']['class']=
			count($this->imageData)>1 ? $this->viewerMultipleCssClass : $this->viewerSingleCssClass;

		$data=$this->dataProvider->getData();

		array_walk($this->widgetConfig['config']['options'], array($this, 'arrayWalkEvaluateExpression'), $data[0]);

		$this->widget($this->widgetConfig['class'], $this->widgetConfig['config']);
	}

	/**
	 * @return CArrayDataProvider the data provider
	 */
	protected function getDataProvider()
	{
		return new CArrayDataProvider($this->imageData, array(
			'pagination'=>array(
				'pageSize'=>1
			),
		));
	}

	/**
	 * @param string value of array parameter
	 * @param string key of array parameter
	 * @param array image data
	 * @return string evaluated expression
	 */
	protected function arrayWalkEvaluateExpression(&$value, $key, $data)
	{
	    $value=$this->evaluateExpression($value, $data);
	}
}