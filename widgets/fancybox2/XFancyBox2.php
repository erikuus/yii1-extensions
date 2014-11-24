<?php
/*
 * XFancyBox widget class file.
 *
 * XFancyBox extends CWidget and implements a base class for a fancybox widget (http://fancyapps.com/fancybox/)
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

class XFancyBox2 extends CWidget
{
	public $target;
	public $config=array();
	public $enableButtons=false;
	public $enableThumbs=false;
	public $enableMedia=false;

	public function init()
	{
		$this->publishAssets();
	}

	public function run()
	{
		if(!$this->target)
			return false;

		$config = CJavaScript::encode($this->config);
		Yii::app()->clientScript->registerScript(__CLASS__.'#'.$this->id, "
			$('$this->target').fancybox($config);
		");
	}

	public function publishAssets()
	{
		$assets = dirname(__FILE__).'/assets';
		$baseUrl = Yii::app()->assetManager->publish($assets);
		if(is_dir($assets)){
			Yii::app()->clientScript->registerCoreScript('jquery');
			Yii::app()->clientScript->registerScriptFile($baseUrl . '/jquery.fancybox.pack.js?v=2.1.5', CClientScript::POS_HEAD);
			Yii::app()->clientScript->registerCssFile($baseUrl . '/jquery.fancybox.css?v=2.1.5');

			if($this->enableButtons)
			{
				Yii::app()->clientScript->registerScriptFile($baseUrl . '/helpers/jquery.fancybox-buttons.js?v=1.0.5', CClientScript::POS_HEAD);
				Yii::app()->clientScript->registerCssFile($baseUrl . '/helpers/jquery.fancybox-buttons.css?v=1.0.5');
			}

			if($this->enableThumbs)
			{
				Yii::app()->clientScript->registerScriptFile($baseUrl . '/helpers/jquery.fancybox-thumbs.js?v=1.0.7', CClientScript::POS_HEAD);
				Yii::app()->clientScript->registerCssFile($baseUrl . '/helpers/jquery.fancybox-thumbs.css?v=1.0.7');
			}

			if($this->enableMedia)
				Yii::app()->clientScript->registerCssFile($baseUrl . '/helpers/jquery.fancybox-media.js?v=1.0.6');
		}
		else
			throw new Exception('XFancyBox - Error: Couldn\'t find assets to publish.');
	}
}