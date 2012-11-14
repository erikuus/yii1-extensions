<?php
/**
 * XAjaxBox class file.
 *
 * XAjaxBox is a simple container the contents of which are drawn from an AJAX call
 *
 * The following example shows how to use XAjaxBox:
 * <pre>
 * $this->widget('ext.widgets.ajaxbox.XAjaxBox', array(
 *     'url'=>$this->createUrl('/some/action')
 *     'htmlOptions'=>array(
 *         'style'=>'width:400px',
 *     ),
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.1
 */
class XAjaxBox extends CWidget
{
	/**
	 * @var string the URL to which the ajax request is sent
	 */
	public $url;
	/**
	 * @var array HTML attributes for ajax box container tag
	 */
	public $htmlOptions=array();

	private $loadingImageUrl;

	/**
	 * Initializes the ajax box widget.
	 */
	public function init()
	{
		$assetsDir = dirname(__FILE__).'/assets';
		$cs = Yii::app()->getClientScript();

		$cs->registerCoreScript("jquery");

		// Publishing and registering JavaScript file
		$cs->registerScriptFile(
			Yii::app()->assetManager->publish(
				$assetsDir.'/ajax_box.js'
			),
			CClientScript::POS_END
		);

		// Publishing image. publish returns the actual URL
		// asset can be accessed with
		$this->loadingImageUrl = Yii::app()->assetManager->publish(
			$assetsDir.'/loading.gif'
		);
	}

	/**
	 * Renders the ajax box
	 */
	public function run()
	{
		if(!isset($this->htmlOptions['class']))
			$this->htmlOptions = array_merge($this->htmlOptions, array('class'=>'ajax-box'));
		else
			$this->htmlOptions['class'].=' ajax-box';

		$this->htmlOptions['data-url']=$this->url;

		echo CHtml::tag('div', $this->htmlOptions, CHtml::image($this->loadingImageUrl));
	}
}
