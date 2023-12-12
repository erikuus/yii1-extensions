<?php

/**
 * XTHtml class
 *
 * This class adds tailwind CSS to CHtml class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTHtml extends CHtml
{
	/**
	 * @var string the CSS class for displaying error summaries (see {@link errorSummary}).
	 */
	public static $errorSummaryCss='-mx-4 my-4 rounded bg-red-50 p-4 text-sm text-red-700';
	public static $errorSummaryHeaderCss='mb-4 font-medium';
	public static $errorSummaryListCss='ml-6 list-disc space-y-2';
	/**
	 * @var string the CSS class for highlighting error inputs. Form inputs will be appended
	 * with this CSS class if they have input errors.
	 */
	public static $errorCss='border-red-300 hover:border-red-400 focus:border-red-600 focus:ring-red-200 focus:ring-red-200';
	/**
	 * @var string the CSS class for displaying link.
	 */
	public static $linkCssClass='font-medium text-blue-600 hover:text-blue-500 focus:active:text-blue-700';
	/**
	 * @var string the CSS class for displaying link.
	 */
	public static $buttonCssClass='focus-visible::ring-2 focus-visible::ring-green-300 cursor-pointer rounded bg-gray-900 py-3 pl-3 pr-4 text-center text-base font-medium text-white hover:bg-gray-700';
	/**
	 * @var string the CSS class name for labels.
	 * Individual {@link error} call may override this value by specifying the 'class' HTML option.
	 */
	public static $labelCssClass='mb-2 block text-base font-semibold leading-6 text-gray-800';

	/**
	 * Generates a hyperlink tag.
	 * @param string $text link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code such as an image tag.
	 * @param mixed $url a URL or an action route that can be used to create a URL.
	 * See {@link normalizeUrl} for more details about how to specify this parameter.
	 * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
	 * attributes are also recognized (see {@link clientChange} and {@link tag} for more details.)
	 * @return string the generated hyperlink
	 * @see normalizeUrl
	 * @see clientChange
	 */
	public static function link($text,$url='#',$htmlOptions=array())
	{
		$htmlOptions=self::assignClass($htmlOptions, self::$linkCssClass);

		if($url!=='')
			$htmlOptions['href']=self::normalizeUrl($url);
		self::clientChange('click',$htmlOptions);
		return self::tag('a',$htmlOptions,$text);
	}

	/**
	 * Generates a submit button.
	 * @param string $label the button label
	 * @param array $htmlOptions additional HTML attributes. Besides normal HTML attributes, a few special
	 * attributes are also recognized (see {@link clientChange} and {@link tag} for more details.)
	 * @return string the generated button tag
	 * @see clientChange
	 */
	public static function submitButton($label='submit',$htmlOptions=array())
	{
		$htmlOptions=self::assignClass($htmlOptions, self::$buttonCssClass);

		$htmlOptions['type']='submit';
		return self::button($label,$htmlOptions);
	}

	/**
	 * Generates a label tag for a model attribute.
	 * The label text is the attribute label and the label is associated with
	 * the input for the attribute (see {@link CModel::getAttributeLabel}.
	 * If the attribute has input error, the label's CSS class will be appended with {@link errorCss}.
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $htmlOptions additional HTML attributes. The following special options are recognized:
	 * <ul>
	 * <li>required: if this is set and is true, the label will be styled
	 * with CSS class 'required' (customizable with CHtml::$requiredCss),
	 * and be decorated with {@link CHtml::beforeRequiredLabel} and
	 * {@link CHtml::afterRequiredLabel}. This option has been available since version 1.0.2.</li>
	 * <li>label: this specifies the label to be displayed. If this is not set,
	 * {@link CModel::getAttributeLabel} will be called to get the label for display.
	 * If the label is specified as false, no label will be rendered.
	 * This option has been available since version 1.0.4.</li>
	 * </ul>
	 * @return string the generated label tag
	 */
	public static function activeLabel($model,$attribute,$htmlOptions=array())
	{
		$htmlOptions=self::assignClass($htmlOptions, self::$labelCssClass);

		if(isset($htmlOptions['for']))
		{
			$for=$htmlOptions['for'];
			unset($htmlOptions['for']);
		}
		else
			$for=self::getIdByName(self::resolveName($model,$attribute));
		if(isset($htmlOptions['label']))
		{
			if(($label=$htmlOptions['label'])===false)
				return '';
			unset($htmlOptions['label']);
		}
		else
			$label=$model->getAttributeLabel($attribute);
		if($model->hasErrors($attribute))
			self::addErrorCss($htmlOptions);
		return self::label($label,$for,$htmlOptions);
	}

	/**
	 * Displays a summary of validation errors for one or several models.
	 * @param mixed $model the models whose input errors are to be displayed. This can be either
	 * a single model or an array of models.
	 * @param string $header a piece of HTML code that appears in front of the errors
	 * @param string $footer a piece of HTML code that appears at the end of the errors
	 * @param array $htmlOptions additional HTML attributes to be rendered in the container div tag.
	 * This parameter has been available since version 1.0.7.
	 * A special option named 'firstError' is recognized, which when set true, will
	 * make the error summary to show only the first error message of each attribute.
	 * If this is not set or is false, all error messages will be displayed.
	 * This option has been available since version 1.1.3.
	 * @return string the error summary. Empty if no errors are found.
	 * @see CModel::getErrors
	 * @see errorSummaryCss
	 */
	public static function errorSummary($model,$header=null,$footer=null,$htmlOptions=array())
	{
		$content='';
		if(!is_array($model))
			$model=array($model);
		if(isset($htmlOptions['firstError']))
		{
			$firstError=$htmlOptions['firstError'];
			unset($htmlOptions['firstError']);
		}
		else
			$firstError=false;
		foreach($model as $m)
		{
			foreach($m->getErrors() as $errors)
			{
				foreach($errors as $error)
				{
					if($error!='')
						$content.="<li>$error</li>\n";
					if($firstError)
						break;
				}
			}
		}
		if($content!=='')
		{
			if($header===null)
				$header='<p class="'.self::$errorSummaryHeaderCss.'">'.Yii::t('yii','Please fix the following input errors:').'</p>';
			if(!isset($htmlOptions['class']))
				$htmlOptions['class']=self::$errorSummaryCss;
			return self::tag('div',$htmlOptions,$header."\n<ul class=\"".self::$errorSummaryListCss."\">\n$content</ul>".$footer);
		}
		else
			return '';
	}

	protected static function assignClass($htmlOptions, $class)
	{
		if(isset($htmlOptions['class']))
			$htmlOptions['class'].=' '.$class;
		else
			$htmlOptions = array_merge($htmlOptions, array('class'=>$class));

		return $htmlOptions;
	}
}