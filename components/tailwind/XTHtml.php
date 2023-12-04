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
	 * @var string the CSS class for highlighting error inputs. Form inputs will be appended
	 * with this CSS class if they have input errors.
	 */
	public static $errorCss='text-red-500 border-red-300 hover:border-red-400 focus:border-red-600 focus:ring-red-200 focus:ring-red-200';
	/**
	 * @var string the CSS class for displaying link.
	 */
	public static $linkCssClass='text-base font-medium text-blue-600 hover:text-blue-500 focus:active:text-blue-700';
	/**
	 * @var string the CSS class for displaying link.
	 */
	public static $buttonCssClass='focus-visible::ring-2 focus-visible::ring-green-300 cursor-pointer rounded bg-gray-900 py-3 pl-3 pr-4 text-center text-base font-medium text-white hover:bg-gray-700';

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

	protected static function assignClass($htmlOptions, $class)
	{
		if(isset($htmlOptions['class']))
			$htmlOptions['class'].=' '.$class;
		else
			$htmlOptions = array_merge($htmlOptions, array('class'=>$class));

		return $htmlOptions;
	}
}