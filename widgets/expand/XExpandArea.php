<?php
/**
 * XExpandArea class file
 *
 * Widget to expand and collapse an area when trigger text is clicked
 *
 * Example of usage:
 * <pre>
 *     $this->beginWidget('ext.widgets.expand.XExpandArea',array(
 *         'trigger'=>'Expand or collapse area'
 *     ));
 *     ... your expand or collapse content here ...
 *     $this->endWidget();
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XExpandArea extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var boolean whether the widget is visible. Defaults to true.
	 */
	public $visible=true;
	/**
	 * @var string the trigger of expand area.
	 */
	public $trigger;
	/**
	 * @var string the CSS class for the widget container. Defaults to 'hover'.
	 */
	public $cssClass='expand';
	/**
	 * @var string the CSS class for the widget trigger. Defaults to 'hoverTrigger'.
	 */
	public $triggerCssClass='expandTrigger';
	/**
	 * @var string the CSS class for the widget content. Defaults to 'hoverContent'.
	 */
	public $contentCssClass='expandContent';
	/**
	 * @var boolean whether to hide the portlet when the body content is empty. Defaults to true.
	 */
	public $hideOnEmpty=true;

	private $_openTag;

	/**
	 * Initializes the widget.
	 * This renders the header part of the widget, if it is visible.
	 */
	public function init()
	{
		if($this->visible)
		{
			ob_start();
			ob_implicit_flush(false);

			$this->registerClientScript();
			echo "<div class=\"{$this->cssClass}\">\n";
			echo "<div class=\"{$this->triggerCssClass} expandTrigger-collapsed\">".$this->trigger."</div>\n";
			echo "<div class=\"{$this->contentCssClass}\" style=\"display: none\">\n";

			$this->_openTag=ob_get_contents();
			ob_clean();
		}
	}

	/**
	 * Finishes rendering the portlet.
	 * This renders the body part of the portlet, if it is visible.
	 */
	public function run()
	{
		if($this->visible)
		{
			$this->renderContent();

			$content=ob_get_clean();
			if($this->hideOnEmpty&&trim($content)==='')
				return;
			echo $this->_openTag;

			echo $content;
			echo "</div><!-- {$this->contentCssClass} -->\n";
			echo "</div><!-- {$this->cssClass} -->";
		}
	}

	/**
	 * Register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		$cs=Yii::app()->clientScript;
		$cs->registerCoreScript('jquery');

		// publish and register css files
		if($this->cssFile===null)
		{
			$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');
			$cs->registerCssFile($assets.'/expand.css');
		}
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register inline javascript
		$script =
<<<SCRIPT
	jQuery('.{$this->cssClass} .{$this->triggerCssClass}').live('click',function(event) {
		event.preventDefault();
		jQuery(this).toggleClass("expandTrigger-collapsed").toggleClass("expandTrigger-expanded").next().toggle();
	});
SCRIPT;

		$cs->registerScript(__CLASS__, $script, CClientScript::POS_READY);
	}

	/**
	 * Renders the body part of the widget.
	 * Child classes should override this method to provide customized body content.
	 */
	protected function renderContent()
	{
	}
}