<?php
/**
 * Yii extension wrapping the jQuery UI MultiSelect Widget from Eric Hynds
 * @author C.Yildiz <c@cba-solutions.org>
 * @link http://www.erichynds.com/jquery/jquery-ui-multiselect-widget/
 * @link http://www.yiiframework.com/extension/echmultiselect/
 */

/**
 * IMPORTANT WHEN UPGRADING!!!
 * Renamed class and some of its attributes
 * Removed default options and default filter options
 * Added detailed comments for options and filter options
 * Added "filter" attribute (instead of filter being defined in options)
 * @author Erik Uus <erik.uus@gmail.com>
 */

/**
 * Example of usage:
 *
 * $this->widget('ext.widgets.multiselect.XMultiSelect', array(
 *     'model'=>$model,
 *     'attribute'=>'title',
 *     'data'=>$model->options,
 *     'filter'=>true,
 *     'options'=>array(
 *         'height'=>370,
 *         'header'=>true,
 *         'selectedList'=>1,
 *         'noneSelectedText'=>Yii::t('ui','Select'),
 *         'selectedText'=>Yii::t('ui','Selected #'),
 *     ),
 *     'filterOptions'=>array(
 *     'placeholder'=>Yii::t('ui','Enter keywords'),
 *     ),
 *     'htmlOptions'=> array(
 *         'style'=>'width:400px;',
 *     ),
 * ));
*/

Yii::import('zii.widgets.jui.CJuiInputWidget');
class XMultiSelect extends CJuiInputWidget
{
	/**
	 * @var CModel the data model associated with this widget.
	 */
	public $model;
	/**
	 * @var string the attribute associated with this widget.
	 * The name can contain square brackets (e.g. 'name[1]') which is used to collect tabular data input.
	 */
	public $attribute;
	/**
	 * @var string the name of the drop down list. This must be set if {@link model} is not set.
	 */
	public $name;
	/**
	 * @var string the selected input value(s). This is used only if {@link model} is not set.
	 */
	public $value=array();
	/**
	 * @var array data for generating the options of the drop down list
	 */
	public $data=array();
	/**
	 * @var array the options for the jQuery UI MultiSelect Widget
	 *
	 * header:
	 * Either a boolean value denoting whether or not to display the header, or a string value.
	 * If you pass a string, the default "check all", "uncheck all", and "close" links will be
	 * replaced with the specified text.
	 * Defaults to true.
	 *
	 * height:
	 * Height of the checkbox container (scroll area) in pixels. If set to "auto",
	 * the height will calculate based on the number of checkboxes in the menu.
	 * Defaults to 175
	 *
	 * minWidth:
	 * Minimum width of the entire widget in pixels. Setting to "auto" will disable.
	 * Defaults to 225
	 *
	 * checkAllText:
	 * The text of the "check all" link.
	 * Defaults to Check all
	 *
	 * uncheckAllText:
	 * The text of the "uncheck all" link.
	 * Defaults to Uncheck All
	 *
	 * noneSelectedText:
	 * The default text the select box when no options have been selected.
	 * Defaults to Select options
	 *
	 * selectedText:
	 * The text to display in the select box when options are selected (if selectedList is false). A pound sign (#) will automatically
	 * replaced by the number of checkboxes selected. If two pound signs are present in this parameter, the second will be replaced by
	 * the total number of checkboxes available. Example: "# of # checked". This parameter also accepts an anonymous function with three
	 * arguments: the number of checkboxes checked, the total number of checkboxes, and an array of the checked checkbox DOM elements.
	 * Defaults to # selected
	 *
	 * selectedList:
	 * A numeric (or boolean to disable) value denoting whether or not to display the checked opens in a list, and how many.
	 * A number greater than 0 denotes the maximum number of list items to display before switching over to the selectedText parameter.
	 * A value of 0 or false is disabled.
	 * Defaults to false
	 *
	 * show:
	 * The name of the effect to use when the menu opens. To control the speed as well, pass in an array: ['slide', 500]
	 * Defaults to empty string
	 *
	 * hide:
	 * The name of the effect to use when the menu closes.
	 * To control the speed as well, pass in an array: ['explode', 500]
	 * Defaults to empty string
	 *
	 * autoOpen:
	 * A boolean value denoting whether or not to automatically open the menu when the widget is initialized.
	 * Defaults to false
	 *
	 * multiple:
	 * If set to false, the widget will use radio buttons instead of checkboxes, forcing users to select only one option.
	 * Defaults to true
	 *
	 * classes:
	 * New in 1.5! Additional class(es) to apply to BOTH the button and menu for further customization. Separate multiple classes
	 * with a space. You'll need to scope your CSS to differentiate between the button/menu
	 * .ui-multiselect.myClass {} .ui-multiselect-menu.myClass {}
	 * Defaults to empty string
	 *
	 * position:
	 * New in 1.5! Requires jQuery 1.4.3+, jQuery UI position utility. This option allows you to position the menu anywhere you'd
	 * like relative to the button; centered, above, below (default), etc. Also provides collision detection to flip the menu above
	 * the button when near the bottom of the window. If you do not set this option or if the position utility has not been included,
	 * the menu will open below the button.
	 * Defaults to empty object
	 */
	public $options=array();
	/**
	 * @var boolean whether to include and initialize filter plugin.Defaults to false
	 */
	public $filter=false;
	/**
	 * @var array the options for the jQuery UI MultiSelect Filter Widget
	 *
	 * label:
	 * The text to appear left of the input.
	 * Defaults to "Filter:"
	 *
	 * width:
	 * The width of the input in pixels.
	 * Defaults to 100px in the style sheet, but you can override this for each instance.
	 *
	 * placeholder:
	 * The HTML5 placeholder attribute value of the input. Only supported in webkit as of this writing.
	 * Defaults to "Enter keywords"
	 *
	 * autoReset:
	 * A boolean value denoting whether or not to reset the search box & any filtered options when the widget closes.
	 * Defaults to false.
	 */
	public $filterOptions=array();
	/**
	 * @var array additional HTML attributes for the drop down list
	 * Options like class, style etc. are adopted by the jQuery UI MultiSelect Widget
	 */
	public $htmlOptions=array();

	public function init()
	{
		// make sure multiple="multiple" is set for dropdown list
		if(!isset($this->options['multiple']) || $this->options['multiple']===true)
			$this->htmlOptions['multiple']=true;

		$cs=Yii::app()->getClientScript();
		$assets=Yii::app()->getAssetManager()->publish(dirname(__FILE__).'/assets');
		$cs->registerScriptFile($assets.'/jquery.ui.widget.min.js');
		$cs->registerScriptFile($assets.'/jquery.multiselect.js');
		$cs->registerCssFile($assets.'/jquery.multiselect.css');

		if($this->filter===true)
		{
			$cs->registerScriptFile($assets.'/jquery.multiselect.filter.js');
			$cs->registerCssFile($assets.'/jquery.multiselect.filter.css');
		}

		parent::init();
	}

	/**
	 * Run this widget.
	 * This method registers necessary javascript and renders the needed HTML code.
	 */
	public function run()
	{
		list($name,$id)=$this->resolveDropDownNameID();

		// Render drop-down element and hide it with javascript
		if($this->hasModel())
			echo CHtml::activeDropDownList($this->model,$this->attribute,$this->data,$this->htmlOptions);
		else
			echo CHtml::dropDownList($name,$this->value,$this->data,$this->htmlOptions);

		// Put the script to hide the select-element directly after the element itself,
		// so it is hidden directly after it is rendered
		// Resource: http://www.electrictoolbox.com/jquery-hide-text-page-load-show-later/
		echo '<script type="text/javascript">$("#'.$id.'").hide();</script>';

		// Prepare client script
		$jsOptions=CJavaScript::encode($this->options);
		if($this->filter===true)
		{
			$jsFilterOptions=CJavaScript::encode($this->filterOptions);
			$jsCode="jQuery('#{$id}').multiselect({$jsOptions}).multiselectfilter({$jsFilterOptions});";
			unset($this->options['filter']);
		}
		else
			$jsCode="jQuery('#{$id}').multiselect({$jsOptions});";

		// Register client script
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,$jsCode);
	}

	/**
	 * @return array the name and the ID of the drop-down element.
	 */
	protected function resolveDropDownNameID()
	{
		$nameID=array();
		if(!empty($this->name))
			$name=$this->name;
		else if($this->hasModel())
		{
			$name=CHtml::activeName($this->model,$this->attribute);
			CHtml::resolveNameID($this->model,$this->attribute,$nameID);
		}
		else
			throw new CException('"model" and "attribute" or "name" have to be set');

		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else if(!empty($nameID['id']))
			$id=$nameID['id'];
		else
			$id=CHtml::getIdByName($name);

		return array($name, $id);
	}

	/**
	 * @return boolean whether this widget is associated with a data model.
	 */
	protected function hasModel()
	{
		return ($this->model instanceof CModel) && !empty($this->attribute);
	}
}
