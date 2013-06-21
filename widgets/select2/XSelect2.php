<?php

/**
 * XSelect2 class file.
 *
 * Select2 is a jQuery based replacement for select boxes. It supports searching, remote data sets, and infinite scrolling of results.
 * This widget is wrapper for ivaynberg jQuery select2 (https://github.com/ivaynberg/select2)
 *
 * @author Anggiajuang Patria <anggiaj@gmail.com>
 * @link http://git.io/Mg_a-w
 * @license http://www.opensource.org/licenses/apache2.0.php
 * @version 1.0
 */

/**
 * Renamed class
 * Restructured code
 * Added cssFile property
 * Dropped selector property
 * Added text field generation for ajax option
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0
 */
class XSelect2 extends CInputWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var array select2 options
	 */
	public $options=array();
	/**
	 * @var array CHtml::dropDownList $data param
	 */
	public $data=array();
	/**
	 * @var array javascript event handlers
	 */
	public $events=array();
	/**
	 * @var boolean should the items of a multiselect list be sortable using jQuery UI
	 */
	public $sortable=false;

	/**
	 * Initializes the widget.
	 * Publish and register client files
	 */
	public function init()
	{
		list($name, $id)=$this->resolveNameId();

		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		if (isset($this->htmlOptions['placeholder']))
			$this->options['placeholder'] = $this->htmlOptions['placeholder'];

		if (!isset($this->htmlOptions['multiple']))
		{
			$data = array();
			if (isset($this->options['placeholder']))
				$data[] = '';
			$this->data = $data + $this->data;
		}

		$this->registerClientScript();
		$this->registerClientScriptFiles();
	}

	/**
	 * Render widget input.
	 */
	public function run()
	{
		if (isset($this->options['ajax']))
		{
			if ($this->hasModel())
				echo CHtml::activeTextField($this->model, $this->attribute, $this->htmlOptions);
			else
				echo CHtml::textField($this->name, $this->value, $this->htmlOptions);
		}
		else
		{
			if (isset($this->htmlOptions['multiple']) && $this->htmlOptions['multiple']=='true')
			{
				if($this->hasModel())
					echo CHtml::activeListBox($this->model, $this->attribute, $this->data, $this->htmlOptions);
				else
					echo CHtml::listBox($this->model, $this->attribute, $this->data, $this->htmlOptions);
			}
			else
			{
				if($this->hasModel())
					echo CHtml::activeDropDownList($this->model, $this->attribute, $this->data, $this->htmlOptions);
				else
					echo CHtml::dropDownList($this->name, $this->value, $this->data, $this->htmlOptions);
			}
		}
	}

	/**
	 * Register necessary inline client scripts.
	 */
	protected function registerClientScript()
	{
		$id=$this->htmlOptions['id'];
		$cs=Yii::app()->clientScript;

		// prepare options
		$options = CJavaScript::encode($this->options);

		// prepare events
		// Note that since jquery 1.7 there is a new method on() that can be used instead of bind()
		$events='';
		foreach ($this->events as $event=>$handler)
			$events.=".bind('{$event}', ".CJavaScript::encode($handler).")";

		// prepare sortable
		$sortable=
<<<SCRIPT
	jQuery('#{$id}').select2("container").find("ul.select2-choices").sortable({
		containment: 'parent',
		start: function() { jQuery('#{$id}').select2("onSortStart"); },
		update: function() { jQuery('#{$id}').select2("onSortEnd"); }
	});
SCRIPT;

		// register inline script
		$script="jQuery('#{$id}').select2({$options}){$events};\n";

		if ($this->sortable)
			$script.=$sortable;

		$cs->registerScript(__CLASS__ . '#' . $id, $script, CClientScript::POS_READY);
	}

	/**
	 * Publish and register necessary client script files.
	 */
	protected function registerClientScriptFiles()
	{
		$cs=Yii::app()->clientScript;
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		// register css file
		if($this->cssFile===null)
			$cs->registerCssFile($assets.'/select2.css');
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register js files
		if ($this->sortable)
			$cs->registerCoreScript('jquery.ui');

		if (YII_DEBUG)
			$cs->registerScriptFile($assets. '/select2.js');
		else
			$cs->registerScriptFile($assets. '/select2.min.js');
	}
}
