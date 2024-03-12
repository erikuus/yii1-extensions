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

	public static $errorSummaryCss='-mx-4 my-4 rounded bg-red-50 p-4 text-sm text-red-700';

	public static $errorSummaryHeaderCss='mb-4 font-medium';

	public static $errorSummaryListCss='ml-6 list-disc space-y-2';

	public static $errorCss='border-red-300 hover:border-red-400 focus:border-red-600 focus:ring-red-200 focus:ring-red-200';

	public static $linkCssClass='font-medium text-blue-600 hover:text-blue-500 focus:active:text-blue-700';

	public static $buttonCssClass='focus-visible::ring-2 focus-visible::ring-green-300 cursor-pointer rounded bg-gray-900 py-3 px-4 text-center text-base font-medium text-white hover:bg-gray-700';

	public static $buttonLinkCssClass='focus-visible::ring-2 focus-visible::ring-green-300 cursor-pointer border border-gray-200 rounded bg-white py-3 px-4 text-center text-base font-medium text-gray-900 hover:bg-gray-100';

	public static $labelCssClass='mb-2 block text-base font-semibold leading-6 text-gray-800';


	public static function link($text,$url='#',$htmlOptions=array())
	{
		$htmlOptions=self::assignClass($htmlOptions, self::$linkCssClass);

		if($url!=='')
			$htmlOptions['href']=self::normalizeUrl($url);
		self::clientChange('click',$htmlOptions);
		return self::tag('a',$htmlOptions,$text);
	}

	public static function buttonLink($text,$url='#',$htmlOptions=array())
	{
		$htmlOptions=self::assignClass($htmlOptions, self::$buttonLinkCssClass);

		if($url!=='')
			$htmlOptions['href']=self::normalizeUrl($url);
		self::clientChange('click',$htmlOptions);
		return self::tag('a',$htmlOptions,$text);
	}

	public static function submitButton($label='submit',$htmlOptions=array())
	{
		$htmlOptions=self::assignClass($htmlOptions, self::$buttonCssClass);

		$htmlOptions['type']='submit';
		return self::button($label,$htmlOptions);
	}

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