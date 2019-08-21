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
	 * Example: array(
	 *   [0]=>array('id'=>1, 'filename'=>'/path/to/image.tif')
	 * );
	 */
	public $imageData=array();

	/**
	 * @var array of widget configuration.
	 * Example: array(
	 *   'class'=>'ext.widgets.iipimage.iipmooviewer.XIIPMooViewer'
	 * )
	 */
	public $widgetConfig=array();

	/**
	 * @var array the configuration for the pager.
	 * Defaults to <code>array('cssFile'=>false)</code>.
	 */
	public $pager=array(
		'cssFile'=>false
	);

	/**
	 * @var string the CSS class name for the pager container.
	 * Defaults to 'iip-image-window-pager'.
	 */
	public $pagerCssClass='iip-image-window-pager';

	/**
	 * @var boolean whether to enable download link.
	 * Defaults to false.
	 */
	public $enableDownloadLink=false;

	/**
	 * @var boolean whether to enable download link.
	 * Defaults to ''.
	 */
	public $downloadLinkLabel='Download';

	/**
	 * @var array the html attributes for download link
	 * Defaults to
	 * <code>
	 * array(
	 *   'download'=>''
	 * )
	 * </code>
	 */
	public $downloadLinkOptions=array(
		'download'=>'',
		'class'=>'download'
	);

	/**
	 * @var array the list of parameters for IIP image download link
	 * @link https://iipimage.sourceforge.io/documentation/protocol/
	 * Defaults to
	 * <code>
	 * array(
	 *   'server'=>'/fcgi-bin/iipsrv.fcgi',
	 *   'wid'=>5000,
	 *   'hei'=>5000,
	 *   'qlt'=>100,
	 *   'cvt'=>'jpeg'
	 * )
	 * </code>
	 */
	public $downloadParams=array(
		'server'=>'/fcgi-bin/iipsrv.fcgi',
		'wid'=>5000,
		'hei'=>5000,
		'qlt'=>100,
		'cvt'=>'jpeg'
	);

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

	private $_countImageData;

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

		if(!isset($this->downloadParams['server']))
			throw new CException('The "downloadParams" array must have "server" key.');

		if(!isset($this->downloadParams['wid']))
			throw new CException('The "downloadParams" array must have "wid" key.');

		if(!isset($this->downloadParams['hei']))
			throw new CException('The "downloadParams" array must have "hei" key.');

		if(!isset($this->downloadParams['qlt']))
			throw new CException('The "downloadParams" array must have "qlt" key.');

		if(!isset($this->downloadParams['cvt']))
			throw new CException('The "downloadParams" array must have "cvt" key.');
	}

	/**
	 * Publish and register necessary client scripts.
	 * Render pager and viewer widget
	 */
	public function run()
	{
		// publish assets
		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);

		// register css
		$cs=Yii::app()->clientScript;
		$cs->registerCssFile($baseUrl.'/main.css');
		$cs->registerCssFile($baseUrl.'/pager.css');

		// get data ready
		$dataProvider=$this->dataProvider;
		$data=$dataProvider->getData();
		$this->_countImageData=count($this->imageData);

		// render
		if($this->_countImageData>1 || $this->enableDownloadLink)
		{
			echo '<div class="'.$this->pagerCssClass.'">';

			if($this->_countImageData>1)
				$this->renderPager($dataProvider, $data);

			if($this->enableDownloadLink)
				echo CHtml::link($this->downloadLinkLabel, $this->getIIPImageSource($data['0']['filename']), $this->downloadLinkOptions);

			echo '</div>';
		}

		$this->renderViewer($data);
	}

	/**
	 * Renders the pager.
	 * @param CArrayDataProvider $dataprovider the image dataprovider
	 * @param array $data the current image dat
	 */
	protected function renderPager($dataProvider, $data)
	{
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

		$pager['pages']=$dataProvider->getPagination();
		$this->widget($class,$pager);
	}

	/**
	 * Renders widget
	 * @param array $data the current image data
	 */
	public function renderViewer($data)
	{
		$this->widgetConfig['config']['htmlOptions']['class']=
			$this->_countImageData>1 || $this->enableDownloadLink ? $this->viewerMultipleCssClass : $this->viewerSingleCssClass;

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

	/**
	 * Get IIPImage source for html image
	 * @param string current filename
	 * @return string image source
	 */
	public function getIIPImageSource($filename)
	{
		return $this->downloadParams['server'].
			'?FIF='.$filename.
			'&wid='.$this->downloadParams['wid'].
			'&hei='.$this->downloadParams['hei'].
			'&qlt='.$this->downloadParams['qlt'].
			'&cvt='.$this->downloadParams['cvt'];
	}
}