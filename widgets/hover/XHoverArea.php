<?php
/**
 * XHoverArea class file
 *
 * Widget to display hover an area when trigger text is clicked
 *
 * Example of usage:
 * <pre>
 *     $this->beginWidget('ext.widgets.hover.XHoverArea',array(
 *         'trigger'=>'Open hover area'
 *     ));
 *     ... your hover area content here ...
 *     $this->endWidget();
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XHoverArea extends CWidget
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
	 * @var string the trigger of hover area.
	 */
	public $trigger;
	/**
	 * @var string the CSS class for the widget container. Defaults to 'hover'.
	 */
	public $cssClass='hover-area';
	/**
	 * @var string the CSS class for the widget trigger. Defaults to 'hoverTrigger'.
	 */
	public $triggerCssClass='hover-area-trigger';
	/**
	 * @var string the CSS class for the widget content. Defaults to 'hoverContent'.
	 */
	public $contentCssClass='hover-area-content';
	/**
	 * @var string alignment of hover area. Defaults to 'left'.
	 */
	public $align='left';

	/**
	 * Initializes the widget.
	 * This renders the header part of the widget, if it is visible.
	 */
	public function init()
	{
		if($this->visible)
		{
			$this->registerClientScript();
			if($this->align=='right')
				$cssClass=$this->cssClass.' hover-area-right';
			echo "<div class=\"{$cssClass}\">\n";
			echo "<div class=\"{$this->triggerCssClass}\">".$this->trigger."</div>\n";
			echo "<div class=\"{$this->contentCssClass}\" style=\"{$this->align}:0\">\n";
			if($this->align=='right')
				echo "<div class=\"hover-area-break\"></div>\n";
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
			$cssFile=CHtml::asset(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'hover.css');
			$cs->registerCssFile($cssFile);
		}
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register inline javascript
		$script =
<<<SCRIPT
	jQuery('.{$this->cssClass}').live('click',function(event) {
		event.stopPropagation();
		jQuery('.{$this->contentCssClass}:visible').hide();
		jQuery(this).children('.{$this->contentCssClass}').toggle();
	});
	jQuery('html').live('click', function() {
		jQuery('.{$this->contentCssClass}:visible').hide();
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