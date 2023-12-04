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

	protected function assignClass($htmlOptions, $class)
	{
		if(isset($htmlOptions['class']))
			$htmlOptions['class'].=' '.$class;
		else
			$htmlOptions = array_merge($htmlOptions, array('class'=>$class));

		return $htmlOptions;
	}
}