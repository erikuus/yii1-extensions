<?php
/**
 * XFlowPlayer
 *
 * Yii extension for the [flowplayer](http://www.flowplayer.org)
 * This is an alpha version of the extension. It supports only the basic configuration.
 *
 * @version 0.2 alpha
 * @author Dimitrios Mengidis
 */
class XFlowPlayer extends CWidget
{
	/**
	 * The flv url.
	 * If the flv is a string the will be one video render.
	 * If flv is an array then multiple video will be generated.
	 * @var mixed
	 * @since 0.2
	 */
	public $flv;
	/**
	 * Tag element player use for container.
	 * @var string
	 * @since 0.2
	 */
	public $tag = 'div';
	/**
	 * The flowplayer.swf url
	 * @var string
	 * @since 0.2
	 */
	public $swfUrl;
	/**
	 * The htmlOptions of the video
	 * @var array
	 * @since 0.2
	 */
	public $htmlOptions;
	/**
	 * The js scripts to register.
	 * @var array
	 * @since 0.1
	 */
	private $js = array(
		'flowplayer-3.2.6.min.js'
	);
	/**
	 * The css scripts to register.
	 * @var array
	 * @since 0.1
	 */
	private $css = array(
		'eflowplayer.css',
	);
	/**
	 * The asset folder after published
	 * @var string
	 * @since 0.1
	 */
	private $assets;

	/**
	 * Publishing the assets.
	 * @since 0.1
	 */
	private function publishAssets()
	{
		$assets = dirname(__FILE__).DIRECTORY_SEPARATOR."assets".DIRECTORY_SEPARATOR;
		$this->assets = Yii::app()->getAssetManager()->publish($assets);
	}

	/**
	 * Register the core flowplayer js lib.
	 *
	 * @since 0.1
	 */
	private function registerScripts()
	{
		$cs = Yii::app()->clientScript;

		foreach($this->js as $file)
		{
			$cs->registerScriptFile($this->assets."/".$file, CClientScript::POS_END);
		}
		foreach($this->css as $file)
		{
			$cs->registerCssFile($this->assets."/".$file);
		}
	}

	/**
	 * Initialize the widget. :)
	 * Publish the assets. Register the flowplayer lib.
	 * Initialize all necessary properties.
	 *
	 * @since 0.1
	 */
	public function init()
	{
		$this->publishAssets();
		$this->registerScripts();

		if(!isset($this->htmlOptions['id'])) $this->htmlOptions['id'] = $this->id;
		if(!isset($this->swfUrl)) $this->swfUrl = $this->assets."/flowplayer-3.2.7.swf";
	}

	/**
	 * Render the containers and configure the flowplayer code.
	 * THOUGHTS: Really don't like what is happening to the poor,
	 *  $htmlOptions param here.
	 *
	 * @since 0.1
	 */
	public function run()
	{
		if(is_array($this->flv)) {
			foreach($this->flv as $id => $url)
			{
				$originalID = $this->htmlOptions['id'];
				if(is_int($id)) {
					$this->htmlOptions['id'] .= $id;
				} else {
					$this->htmlOptions['id'] = $id;
				}

				$this->renderContainer();
				$this->flowplayerScript($url);

				$this->htmlOptions['id'] = $originalID;
			}
		} else {
			$this->renderContainer();
			$this->flowplayerScript();
		}
	}

	/**
	 * Render the html element used as video container.
	 *
	 * @since 0.3
	 */
	private function renderContainer()
	{
		echo CHtml::openTag($this->tag, $this->htmlOptions);
		echo CHtml::closeTag($this->tag);
	}

	/**
	 * Configuration of the flowplayer.
	 * Register the javascript code to use the flowplayer()
	 * function.
	 *
	 * @param
	 * @since 0.3
	 */
	private function flowplayerScript($flv = null)
	{
		if(!isset($flv)) {
			$flv = $this->flv;
		}
		Yii::app()->clientScript->registerScript($this->htmlOptions['id'],
			"flowplayer('".$this->htmlOptions['id']."','".$this->swfUrl."', {clip: {url:'".$flv."',autoPlay:false}})",
			CClientScript::POS_READY
		);
	}
}
