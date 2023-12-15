<?php
/**
/**
 * XTActiveForm class file.
 *
 * XTActiveForm extends XDynamicForm adding Tailwind CSS classes to labels and fields
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
Yii::import('ext.widgets.form.XDynamicForm');
Yii::import('ext.components.tailwind.XTHtml');
class XTActiveForm extends XDynamicForm
{
	public $enableRadioToggle=false;

	public $enableChecboxToggle=false;

	public $errorMessageCssClass='mt-2 text-sm text-red-500';

	public $labelCssClass='mb-2 block text-base font-semibold leading-6 text-gray-800';

	public $fieldCssClass='block w-full rounded border border-gray-200 px-4 py-3 text-gray-900 hover:border-gray-300 focus:border-gray-500 focus:outline-none focus:ring-2 focus:ring-green-300 sm:text-base sm:leading-6 disabled:bg-gray-50 disabled:text-gray-400 disabled:border-gray-100
	';

	public $radioCssClass='h-4 w-4 mr-3 text-green-500 focus:ring-offset-0 focus:ring-2 border-gray-200 hover:border-gray-300 focus:border-gray-500 focus:ring-green-300 focus:ring-green-300';

	public $htmlOptions=array('class'=>'space-y-6');


	public function label($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->labelCssClass);
		return XTHtml::activeLabel($model,$attribute,$htmlOptions);
	}

	public function labelEx($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->labelCssClass);
		return XTHtml::activeLabelEx($model,$attribute,$htmlOptions);
	}

	public function textField($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activeTextField($model,$attribute,$htmlOptions);
	}

	public function passwordField($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activePasswordField($model,$attribute,$htmlOptions);
	}

	public function textArea($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activeTextArea($model,$attribute,$htmlOptions);
	}

	public function dropDownList($model,$attribute,$data,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activeDropDownList($model,$attribute,$data,$htmlOptions);
	}

	public function radioButton($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->radioCssClass);
		return XTHtml::activeRadioButton($model,$attribute,$htmlOptions);
	}

	public function radioButtonList($model,$attribute,$data,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->radioCssClass);
		return XTHtml::activeRadioButtonList($model,$attribute,$data,$htmlOptions);
	}

	public function DynamicDropDownList($model, $attribute, $data, $htmlOptions=array())
	{
		$id=CHtml::activeId($model, $attribute);

		$script =
<<<SCRIPT
		var selected=$('#{$id}').val();
		$('.{$id}').hide();
		$('.{$id} :input').prop('disabled',true);
		$('.{$id}.selected_'+selected).show();
		$('.{$id}.selected_'+selected+' :input').prop('disabled',false);
		$('#{$id}').live('change', function(){
			var selected=$(this).val();
			$('.{$id}').hide();
			$('.{$id}.selected_'+selected).show();
			$('.{$id}.selected_'+selected+' :input').prop('disabled',false);
		});
SCRIPT;

		if(Yii::app()->request->isAjaxRequest)
			echo CHtml::script($script);
		else
			Yii::app()->clientScript->registerScript(__CLASS__.'#dropdown#'.$id, $script, CClientScript::POS_READY);

		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activeDropDownList($model, $attribute, $data, $htmlOptions);
	}

	public function errorSummary($models,$header=null,$footer=null,$htmlOptions=array())
	{
		if(!$this->enableAjaxValidation && !$this->enableClientValidation)
			return XTHtml::errorSummary($models,$header,$footer,$htmlOptions);

		if(!isset($htmlOptions['id']))
			$htmlOptions['id']=$this->id.'_es_';
		$html=XTHtml::errorSummary($models,$header,$footer,$htmlOptions);
		if($html==='')
		{
			if($header===null)
				$header='<p>'.Yii::t('yii','Please fix the following input errors:').'</p>';
			if(!isset($htmlOptions['class']))
				$htmlOptions['class']=XTHtml::$errorSummaryCss;
			$htmlOptions['style']=isset($htmlOptions['style']) ? rtrim($htmlOptions['style'],';').';display:none' : 'display:none';
			$html=XTHtml::tag('div',$htmlOptions,$header."\n<ul><li>dummy</li></ul>".$footer);
		}

		$this->summaryID=$htmlOptions['id'];
		return $html;
	}

	protected function assignClass($htmlOptions, $class)
	{
		if(isset($htmlOptions['class']))
			$htmlOptions['class'].=' '.$class;
		else
			$htmlOptions = array_merge($htmlOptions, array('class'=>$class));

		return $htmlOptions;
	}
}