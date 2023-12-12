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
	/**
	 * @var boolean whether to enable radiobutton to toggle dynamic area
	 * Defaults to false
	 */
	public $enableRadioToggle=false;
	/**
	 * @var boolean whether to enable checkbox to toggle dynamic area
	 * Defaults to false
	 */
	public $enableChecboxToggle=false;
	/**
	 * @var string the CSS class name for error messages. Defaults to 'errorMessage'.
	 * Individual {@link error} call may override this value by specifying the 'class' HTML option.
	 */
	public $errorMessageCssClass='mt-2 text-sm text-red-500';
	/**
	 * @var string the CSS class name for labels.
	 * Individual {@link error} call may override this value by specifying the 'class' HTML option.
	 */
	public $labelCssClass='mb-2 block text-base font-semibold leading-6 text-gray-800';
	/**
	 * @var string the CSS class name for fields.
	 * Individual {@link error} call may override this value by specifying the 'class' HTML option.
	 */
	public $fieldCssClass='block w-full rounded border border-gray-200 px-4 py-3 text-gray-900 hover:border-gray-300 focus:border-gray-500 focus:outline-none focus:ring-2 focus:ring-green-300 sm:text-base sm:leading-6';
	/**
	 * @var array additional HTML attributes that should be rendered for the form tag.
	 */
	public $htmlOptions=array('class'=>'space-y-6');

	/**
	 * Renders an HTML label for a model attribute.
	 * This method is a wrapper of {@link XTHtml::activeLabel}.
	 * Please check {@link XTHtml::activeLabel} for detailed information
	 * about the parameters for this method.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes.
	 * @return string the generated label tag
	 */
	public function label($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->labelCssClass);
		return XTHtml::activeLabel($model,$attribute,$htmlOptions);
	}

	/**
	 * Renders an HTML label for a model attribute.
	 * This method is a wrapper of {@link XTHtml::activeLabelEx}.
	 * Please check {@link XTHtml::activeLabelEx} for detailed information
	 * about the parameters for this method.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes.
	 * @return string the generated label tag
	 */
	public function labelEx($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->labelCssClass);
		return XTHtml::activeLabelEx($model,$attribute,$htmlOptions);
	}

	/**
	 * Renders a text field for a model attribute.
	 * This method is a wrapper of {@link XTHtml::activeTextField}.
	 * Please check {@link XTHtml::activeTextField} for detailed information
	 * about the parameters for this method.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes.
	 * @return string the generated input field
	 */
	public function textField($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activeTextField($model,$attribute,$htmlOptions);
	}

	/**
	 * Renders a password field for a model attribute.
	 * This method is a wrapper of {@link XTHtml::activePasswordField}.
	 * Please check {@link XTHtml::activePasswordField} for detailed information
	 * about the parameters for this method.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes.
	 * @return string the generated input field
	 */
	public function passwordField($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activePasswordField($model,$attribute,$htmlOptions);
	}

	/**
	 * Renders a text area for a model attribute.
	 * This method is a wrapper of {@link XTHtml::activeTextArea}.
	 * Please check {@link XTHtml::activeTextArea} for detailed information
	 * about the parameters for this method.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes.
	 * @return string the generated text area
	 */
	public function textArea($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activeTextArea($model,$attribute,$htmlOptions);
	}

	/**
	 * Renders a dropdown list for a model attribute.
	 * This method is a wrapper of {@link CHtml::activeDropDownList}.
	 * Please check {@link CHtml::activeDropDownList} for detailed information
	 * about the parameters for this method.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $data data for generating the list options (value=>display)
	 * @param array $htmlOptions additional HTML attributes.
	 * @return string the generated drop down list
	 */
	public function dropDownList($model,$attribute,$data,$htmlOptions=array())
	{
		$htmlOptions=$this->assignClass($htmlOptions, $this->fieldCssClass);
		return XTHtml::activeDropDownList($model,$attribute,$data,$htmlOptions);
	}

	/**
	 * Renders a dropdown list for a model attribute and registers clientscript
	 * needed to show/hide areas depending on selected option of dropdown list
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $data data for generating the list options (value=>display)
	 * @param array $htmlOptions additional HTML attributes.
	 * @return string the generated drop down list
	 */
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

	/**
	 * Displays a summary of validation errors for one or several models.
	 * This method is very similar to {@link XTHtml::errorSummary} except that it also works
	 * when AJAX validation is performed.
	 * @param mixed $models the models whose input errors are to be displayed. This can be either
	 * a single model or an array of models.
	 * @param string $header a piece of HTML code that appears in front of the errors
	 * @param string $footer a piece of HTML code that appears at the end of the errors
	 * @param array $htmlOptions additional HTML attributes to be rendered in the container div tag.
	 * @return string the error summary. Empty if no errors are found.
	 * @see XTHtml::errorSummary
	 */
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