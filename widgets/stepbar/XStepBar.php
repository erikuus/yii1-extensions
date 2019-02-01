<?php
/**
 * XStepBar class file
 *
 * XStepBar displays progressbar of labeled steps.
 *
 * The following example shows how to use XStepBar:
 * <pre>
 * $this->widget('ext.widgets.stepbar.XStepBar', array(
 *     'steps'=>array(
 *         array('label'=>'fill application', 'active'=>true),
 *         array('label'=>'submit application'),
 *         array('label'=>'application is being processed'),
 *     ),
 * ));
 * </pre>
 *
 * @link http://kodhus.com/newsite/step-progress-bar-css-only/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.1
 */
class XStepBar extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var array list of steps. Each step is specified as an array of name-value pairs.
	 * Possible option names include the following:
	 * <ul>
	 * <li>label: string, required, specifies the step label. When {@link encodeLabel} is true, the label
	 * will be HTML-encoded.</li>
	 * <li>visible: boolean, optional, whether this step is visible. Defaults to true.</li>
	 * <li>active: boolean, optional, whether this step is active. Defaults to false.</li>
	 * <li>template: string, optional, the template used to render this step.
	 * In this template, the token "{step}" will be replaced with the corresponding text.</li>
	 * <li>options: array, optional, additional HTML attributes to be rendered for list tag of the step.</li>
	 * </ul>
	 */
	public $steps=array();
	/**
	 * @var string the template used to render an individual step. In this template,
	 * the token "{step}" will be replaced with the corresponding text.
	 * If this property is not set, each step will be rendered without any decoration.
	 * This property will be overridden by the 'template' option set in individual steps via {@steps}.
	 */
	public $stepTemplate;
	/**
	 * @var boolean whether the widget is visible. Defaults to true.
	 */
	public $visible=true;
	/**
	 * @var boolean whether the labels for steps should be HTML-encoded. Defaults to true.
	 */
	public $encodeLabel=true;
	/**
	 * @var string the menu's root container tag name. Defaults 'div'.
	 * If this property is set to null, no container is used.
	 */
	public $containerTag='div';
	/**
	 * @var string the CSS class for the widget container. Defaults to 'navbar'.
	 */
	public $containerCssClass='stepbar';
	/**
	 * @var array HTML attributes for the stepbar container tag
	 */
	public $htmlOptions=array();
	/**
	 * @var string the CSS class to be appended to the active step. Defaults to 'active'.
	 */
	public $activeCssClass='active';

	/**
	 * Initializes the widget.
	 * This method mainly normalizes the {@link steps} property.
	 * If this method is overridden, make sure the parent implementation is invoked.
	 */
	public function init()
	{
		if($this->visible)
		{
			$this->registerClientScript();
			$this->htmlOptions['id']=$this->getId();
			$this->steps=$this->normalizeSteps($this->steps);
		}
	}

	/**
	 * Calls {@link renderStepBar} to render the menu.
	 */
	public function run()
	{
		if($this->visible)
			$this->renderStepBar($this->steps);
	}

	/**
	 * Renders the stepbar.
	 * @param array steps.
	 */
	protected function renderStepBar($steps)
	{
		if(count($steps))
		{
			if(!isset($this->htmlOptions['class']))
				$this->htmlOptions['class']=$this->containerCssClass;
			else
				$this->htmlOptions['class'].=' '.$this->containerCssClass;

			// open container tag
			if($this->containerTag)
				echo CHtml::openTag($this->containerTag,$this->htmlOptions)."\n";

			echo "<ul>\n";
			$this->renderSteps($steps);
			echo "</ul>\n";

			// end container tag
			if($this->containerTag)
				echo CHtml::closeTag($this->containerTag);

			echo "<div style=\"clear: both\"></div>\n";
		}
	}

	/**
	 * Recursively renders the stepbar steps.
	 * @param array the steps to be rendered recursively
	 */
	protected function renderSteps($steps)
	{
		foreach($steps as $step)
		{
			if(isset($step['active']) && $step['active'] && $this->activeCssClass)
			{
				if(empty($step['options']['class']))
					$step['options']['class']=$this->activeCssClass;
				else
					$step['options']['class'].=' '.$this->activeCssClass;
			}

			$step=CHtml::tag('li',isset($step['options']) ? $step['options'] : array(), $step['label']);

			if(isset($this->stepTemplate) || isset($step['template']))
			{
				$template=isset($step['template']) ? $step['template'] : $this->stepTemplate;
				echo strtr($template,array('{step}'=>$step))."\n";
			}
			else
				echo $step."\n";
		}
	}

	/**
	 * Normalizes the steps property.
	 * @param array the steps to be normalized.
	 * @return array the normalized steps
	 */
	protected function normalizeSteps($steps)
	{
		foreach($steps as $i=>$step)
		{
			if(isset($step['visible']) && !$step['visible'])
			{
				unset($steps[$i]);
				continue;
			}
			if($this->encodeLabel)
				$steps[$i]['label']=CHtml::encode($step['label']);
		}
		return array_values($steps);
	}

	/**
	 * Register necessary client scripts.
	 */
	protected function registerClientScript()
	{
		// register css file
		if($this->cssFile===null)
		{
			$cssFile=CHtml::asset(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'stepbar.css');
			Yii::app()->clientScript->registerCssFile($cssFile);
		}
		else if($this->cssFile!==false)
			Yii::app()->clientScript->registerCssFile($this->cssFile);

		// register inline style
		$width=floor(100/count($this->steps));
		Yii::app()->clientScript->registerCss(__CLASS__, "
			.stepbar>ul li {
				width: $width%;
			}
		", 'screen', CClientScript::POS_HEAD);
	}
}