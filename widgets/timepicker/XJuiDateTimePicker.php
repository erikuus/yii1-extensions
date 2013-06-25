<?php
/**
 * XJuiDateTimePicker displays a DateTimePicker or TimePicker.
 *
 * XJuiDateTimePicker encapsulates the {@link http://trentrichardson.com/examples/timepicker/} addon.
 *
 * Example:
 *
 * <pre>
 * $this->widget('ext.widgets.timepicker.XJuiDateTimePicker', array(
 *     'model'     => $model,
 *     'attribute' => 'attribute',
 *     'options' => array(
 *         'minDate'=>0, // minimum selectable datetime is current time
 *         'dateFormat'=>'dd.mm.yy',
 *         'timeFormat=>'HH:mm'
 *     ),
 *     'htmlOptions'=>array(
 *         'style'=>'height:20px;'
 *     ),
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

Yii::import('zii.widgets.jui.CJuiDatePicker');
class XJuiDateTimePicker extends CJuiDatePicker
{
	/**
	 * Run this widget.
	 * This method registers necessary javascript and renders the needed HTML code.
	 */
	public function run()
	{
		list($name,$id)=$this->resolveNameID();

		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;
		if(isset($this->htmlOptions['name']))
			$name=$this->htmlOptions['name'];

		if($this->flat===false)
		{
			if($this->hasModel())
				echo CHtml::activeTextField($this->model,$this->attribute,$this->htmlOptions);
			else
				echo CHtml::textField($name,$this->value,$this->htmlOptions);
		}
		else
		{
			if($this->hasModel())
			{
				echo CHtml::activeHiddenField($this->model,$this->attribute,$this->htmlOptions);
				$attribute=$this->attribute;
				$this->options['defaultDate']=$this->model->$attribute;
			}
			else
			{
				echo CHtml::hiddenField($name,$this->value,$this->htmlOptions);
				$this->options['defaultDate']=$this->value;
			}

			$this->options['altField']='#'.$id;

			$id=$this->htmlOptions['id']=$id.'_container';
			$this->htmlOptions['name']=$name.'_container';

			echo CHtml::tag('div',$this->htmlOptions,'');
		}

		$options = CJavaScript::encode($this->options);
		$js = "jQuery('#{$id}').datetimepicker($options);";

		$assetsDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
		$assets = Yii::app()->assetManager->publish($assetsDir);

		$i18nScriptFile = 'jquery-ui-timepicker-' . $this->language . '.js';
		$i18nScriptPath = $assetsDir . DIRECTORY_SEPARATOR . 'localization' . DIRECTORY_SEPARATOR . $i18nScriptFile;

		$cs = Yii::app()->clientScript;
		$cs->registerScriptFile($assets . '/jquery-ui-timepicker-addon.js', CClientScript::POS_END);

		if($this->language!='' && $this->language!='en')
		{
			$this->registerScriptFile($this->i18nScriptFile);

			if (file_exists($i18nScriptPath))
				$cs->registerScriptFile($assets . '/localization/' . $i18nScriptFile, CClientScript::POS_END);

			$js  = "jQuery('#{$id}').datetimepicker(jQuery.extend(jQuery.datepicker.regional['{$this->language}'], {$options}));";
		}

		if(isset($this->defaultOptions))
		{
			$this->registerScriptFile($this->i18nScriptFile);

			if (file_exists($i18nScriptPath))
				$cs->registerScriptFile($assets . '/localization/' . $i18nScriptFile, CClientScript::POS_END);

			$cs->registerScript(__CLASS__,$this->defaultOptions!==null?'jQuery.datetimepicker.setDefaults('.CJavaScript::encode($this->defaultOptions).');':'');
		}
		$cs->registerScript(__CLASS__.'#'.$id,$js);
	}
}

