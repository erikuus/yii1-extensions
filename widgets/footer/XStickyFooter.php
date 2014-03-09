<?php
/**
 * XStickyFooter class file
 *
 * Widget to display footer that sticks to the bottom of page
 *
 * Example of usage:
 * <pre>
 *     $this->beginWidget('ext.widgets.footer.XStickyFooter);
 *     ... your footer content here ...
 *     $this->endWidget();
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XStickyFooter extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var string the HTML tag name for the container of footer. Defaults to 'div'.
	 */
	public $tagName='div';
	/**
	 * @var array HTML attributes for footer container tag
	 */
	public $htmlOptions=array();
	/**
	 * @var boolean whether the widget sticks to the bottom of page. Defaults to true.
	 */
	public $sticky=true;

	/**
	 * Initializes the widget.
	 * This renders the header part of the widget, if it is visible.
	 */
	public function init()
	{
		if(!isset($this->htmlOptions['class']))
			$this->htmlOptions = array_merge($this->htmlOptions, array('class'=>'sticky-footer'));
		else
			$this->htmlOptions['class'].=' sticky-footer';

		$this->registerClientScript();
		echo CHtml::openTag($this->tagName, $this->htmlOptions);
	}

	/**
	 * Finishes rendering the portlet.
	 * This renders the body part of the portlet, if it is visible.
	 */
	public function run()
	{
		$this->renderContent();
		echo CHtml::closeTag($this->tagName);
	}

	/**
	 * Register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		// publish
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		// register core script
		$cs=Yii::app()->clientScript;
		$cs->registerCoreScript('jquery');

		// register css files
		if($this->cssFile===null)
			$cs->registerCssFile($assets.'/stickyFooter.css');
		else if($this->cssFile!==false)
			$cs->registerCssFile($assets.'/'.$this->cssFile);

		// register javascript file
		if($this->sticky)
			$cs->registerScriptFile($assets.'/jquery.stickyFooter.js', CClientScript::POS_END);
	}

	/**
	 * Renders the body part of the widget.
	 * Child classes should override this method to provide customized body content.
	 */
	protected function renderContent()
	{
	}
}