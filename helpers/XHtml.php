<?php

/**
 * XHtml class
 *
 * This class adds helper methods to CHtml class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XHtml extends CHtml
{

	/**
	 * Makes the given filename relative to the /css directory
	 * @param string $filename the css filename
	 * @param boolean whether to return an absolute URL
	 * @return string css relative url
	 */
	public static function cssUrl($filename, $absolute=false)
	{
		return Yii::app()->getBaseUrl($absolute).'/css/'.$filename;
	}

	/**
	 * Makes the given URL relative to the /js directory
	 * @param string $filename the js filename
	 * @param boolean whether to return an absolute URL
	 * @return string js relative url
	 */
	public static function jsUrl($filename, $absolute=false)
	{
		return Yii::app()->getBaseUrl($absolute).'/js/'.$filename;
	}

	/**
	 * Makes the given URL relative to the /images directory
	 * @param string $filename the image filename
	 * @param boolean whether to return an absolute URL
	 * @return string image relative url
	 */
	public static function imageUrl($filename, $absolute=false)
	{
		return Yii::app()->getBaseUrl($absolute).'/images/'.$filename;
	}

	/**
	 * Makes the image tag inside link tag
	 * @param string $image the image filename
	 * @param string $linkUrl the url of the link
	 * @param array $linkHtmlOptions the link html options
	 * @return string image tag inside link tag
	 */
	public static function imageLink($image,$linkUrl='#',$linkHtmlOptions=array())
	{
		$altText=isset($linkHtmlOptions['title']) ? $linkHtmlOptions['title'] : null;
		return self::link(self::image(self::imageUrl($image), $altText, array(
			'align'=>'top'
		)), $linkUrl, $linkHtmlOptions);
	}

	/**
	 * Makes image tag followed by text
	 * @param string $image the image filename
	 * @param string $label the url of the link
	 * @param boolean $reverse whether text should appear before image
	 * @param string align image to text
	 * @return string image tag followed by text
	 */
	public static function imageLabel($image,$text='',$reverse=false,$align='top')
	{
		$image=self::image(self::imageUrl($image),'',array(
			'align'=>$align
		));
		$label=trim($text);
		return $reverse ? $label.' '.$image : $image.' '.$label;
	}

	/**
	 * Extremely simplified truncation method
	 * @param string $str the string to truncate
	 * @param integer $len the length to truncate to
	 * @param string $ellipsis the concatenation characters
	 * @return string
	 */
	public static function truncate($str,$length=50,$ellipsis='...', $encoding='utf-8')
	{
		if(mb_strlen($str)<$length)
			return $str;
		$tmp=mb_substr($str,0,($length-mb_strlen($ellipsis)),$encoding);
		return $tmp.$ellipsis;
	}

	/**
	 * Converts a type name into space-separated words.
	 * For example, 'UserRole' will be converted as 'User Role'.
	 * @param string the string to be converted
	 * @param boolean whether to capitalize the first letter in each word
	 * @return string the resulting words
	 */
	public static function labelize($name,$ucwords=true)
	{
		$result=trim(mb_strtolower(str_replace('_',' ',preg_replace('/(?<![A-Z])[A-Z]/',' \0',$name))));
		return $ucwords ? ucwords($result) : $result;
	}

	/**
	 * Format time
	 * @param integer unix time
	 * @param string time format
	 * @return string formatted datetime
	 */
	public static function formatTime($time,$format='d.m.Y H:i')
	{
		return $time ? date($format,$time) : null;
	}

	/**
	 * Format date
	 * @param string date
	 * @param string time format
	 * @param string date only format
	 * @return string formatted datetime
	 */
	public static function formatDate($date,$format='d.m.Y',$dateOnlyFormat=null)
	{
		$format = $dateOnlyFormat && strstr($date, '00:00:00') ? $dateOnlyFormat : $format;
		return $date ? date_format(date_create($date),$format) : null;
	}

	/**
	 * @param integer sec
	 * @param integer count
	 * @return string time in days and hours
	 */
	public static function formatHumanTime($sec)
	{
		$days=floor($sec/(60*60*24));
		$hours=floor(($sec-$days*60*60*24)/(60*60));
		$min=floor(($sec-($days*60*60*24)-($hours*60*60))/60);
		$sec=floor($sec-($days*60*60*24+$hours*60*60+$min*60));

		$human=Yii::t('XHtml.html','n==0#|n==1#1 day |n>1#{n} days ', array($days)).Yii::t('XHtml.html','n==0#|n==1#1 hour |n>1#{n} hours ', array($hours));

		if($days==0 || $hours==0)
			$human.=Yii::t('XHtml.html','n==0#|n==1#1 minute |n>1#{n} minutes ', array($min));

		if($days==0 && $hours==0)
			$human.=Yii::t('XHtml.html','n==0#|n==1#1 second|n>1#{n} seconds', array($sec));

		return $human;
	}

	/**
	 * Format bytes to kilobytes, megabytes, gigabytes
	 * @param integer bytes
	 * @param integer precision
	 * @return string formatted
	 */
	public static function formatBytes($bytes, $precision = 0)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/**
	 * Add option
	 * @return array of 'add new' option for dropdown
	 */
	public static function addOption($add='-add-')
	{
		return array(
			'-1'=>$add
		);
	}

	/**
	 * Boolean options
	 * @return array of boolean options for dropdown
	 */
	public static function booleanOptions()
	{
		return array(
			'1'=>Yii::t('zii','Yes'),'0'=>Yii::t('zii','No')
		);
	}

	/**
	 * Label boolean
	 * @param mixed $value boolean or integer
	 * @param string $fallback message to display when value is not boolean
	 * @return string yes/no label for boolean
	 */
	public static function booleanLabel($value, $fallback=null)
	{
		if($fallback && $value===null)
			return $fallback;
		else
			return $value ? Yii::t('zii','Yes') : Yii::t('zii','No');
	}

	/**
	 * Build html list
	 * @param array content
	 * @param array html options for li tag
	 * @param array html options for ul tag
	 * @return string html list
	 */
	public static function listContent($contents, $liHtmlOptions=array(), $ulHtmlOptions=array())
	{
		$list='';
		foreach ($contents as $content)
			$list.=self::tag('li', $liHtmlOptions, $content);
		return self::tag('ul', $ulHtmlOptions, $list);
	}

	/**
	 * Highlight words
	 * @param string $text
	 * @param mixed $words
	 * @param boolean $fullWords
	 * @param string $startTag
	 * @param string $endTag
	 * @return string
	 */
	public static function highlight($text,$words,$fullWords=false,$startTag='<strong>',$endTag='</strong>')
	{
		if(!$words||(is_array($words)&&!$words[0]))
			return $text;

		if(is_string($words))
			$words=explode(',',$words);

		foreach($words as $word)
		{
			$word=str_replace('/','\\/',preg_quote($word));
			if($fullWords)
				$text=preg_replace("/\b($word)\b/iu",$startTag.'\1'.$endTag,$text);
			else
				$text=preg_replace("/($word)/iu",$startTag.'\1'.$endTag,$text);
		}
		return $text;
	}

	/**
	 * Find any URLs in the block of text and turn them into hyperlinks
	 * @param string $text
	 * @return string
	 */
	public static function formatUrlsInText($text)
	{
		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		preg_match_all($reg_exUrl, $text, $matches);
		$usedPatterns = array();
		foreach($matches[0] as $pattern)
		{
			if(!array_key_exists($pattern, $usedPatterns)){
				$usedPatterns[$pattern]=true;
				$text =str_replace ($pattern, "<a href=\"{$pattern}\" rel=\"nofollow\">{$pattern}</a>", $text);
			}
		}
		return $text;
	}

	/**
	 * Explodes radioButtonList into array
	 * enabling to render buttons separately ($radio[0], $radio[1]...)
	 * @param CActiveForm $form the form widgets
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $data value-label pairs used to generate the radio button list.
	 * @return array of radio buttons
	 */
	public static function explodeRadioButtonList($form,$model,$attribute,$data)
	{
		return explode('|',$form->radioButtonList($model,$attribute,$data,array(
			'template'=>'{input}{label}','separator'=>'|'
		)));
	}

	/**
	 * Explodes checkBoxList into array
	 * enabling to render boxes separately ($box[0], $box[1]...)
	 * @param CActiveForm $form the form widgets
	 * @param CModel $model the data model
	 * @param string $attribute the attribute
	 * @param array $data value-label pairs used to generate the check box list.
	 * @return array of check boxes
	 */
	public static function explodeCheckBoxList($form,$model,$attribute,$data)
	{
		return explode('|',$form->checkBoxList($model,$attribute,$data,array(
			'template'=>'{input}{label}','separator'=>'|'
		)));
	}

	/**
	 * Displays a summary of validation errors form array returned by getErrors method.
	 * @param array arrErrors the array returned by getErrors method
	 * @param string $header a piece of HTML code that appears in front of the errors
	 * @param string $footer a piece of HTML code that appears at the end of the errors
	 * @param array $htmlOptions additional HTML attributes to be rendered in the container div tag.
	 * @return string the error summary. Empty if no errors are found.
	 * @see CModel::getErrors
	 * @see errorSummaryCss
	 */
	public static function errorSummaryFromArray($arrErrors,$header=null,$footer=null,$htmlOptions=array())
	{
		$content='';

		if(isset($htmlOptions['firstError']))
		{
			$firstError=$htmlOptions['firstError'];
			unset($htmlOptions['firstError']);
		}
		else
			$firstError=false;

		foreach($arrErrors as $errors)
		{
			foreach($errors as $error)
			{
				if($error!='')
					$content.="<li>$error</li>\n";
				if($firstError)
					break;
			}
		}

		if($content!=='')
		{
			if(!isset($htmlOptions['class']))
				$htmlOptions['class']=self::$errorSummaryCss;
			return self::tag('div',$htmlOptions,$header."\n<ul>\n$content</ul>".$footer);
		}
		else
			return '';
	}

	/**
	 * Displays a summary of validation errors in palin text format form array returned by getErrors method.
	 * @param array arrErrors the array returned by getErrors method
	 * @param string $header a piece of text that appears in front of the errors
	 * @param string $footer a piece of text that appears at the end of the errors
	 * @return string the error summary. Empty if no errors are found.
	 * @see CModel::getErrors
	 */
	public static function errorSummaryTextFromArray($arrErrors,$header=null,$footer=null)
	{
		$content='';

		foreach($arrErrors as $errors)
		{
			foreach($errors as $error)
			{
				if($error!='')
					$content.="$error\n";
			}
		}

		if($content!=='')
			return $header."\n".$content.$footer;
		else
			return '';
	}
}