<?php

/**
 * XHumanNameFilterValidator
 *
 * XHumanNameFilterValidator converts human name cases based on custom function
 *
 * First cases of each word within human name are converted to uppercase, rest are
 * converted to lowercase. There is a list of exceptions to this rule allowing to
 * define which words should always be in lowercase and which in uppercase.
 *
 * The following shows how to use this validator on model actions() method:
 * <pre>
 *    return array(
 *        array('firstname, lastname','XHumanNameFilterValidator'),
 *    );
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.1
 */
class XHumanNameFilterValidator extends CValidator
{
	/**
	 * @var array list of word delimiters ins
	 */
	public $delimiters=array("-",".","'","O'","Mc"," ");

	/**
	 * @var array list of conversion exceptions
	 * In lower case are words you want lowercase.
	 * In upper case are words you want uppercase.
	 */
	public $exceptions=array('bar','ben','bin','da','dal','de la','de','del','der','di','ibn','la','le','san','st','ste','van','van der','van den','vel','von','I', 'II', 'III', 'IV', 'V', 'VI');

	/**
	 * Validates the attribute of the object.
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object,$attribute)
	{
		$object->$attribute=$this->convertCase($object->$attribute);
	}

	protected function convertCase($string)
	{
		$string=mb_convert_case($string, MB_CASE_TITLE ,"UTF-8");
		foreach($this->delimiters as $delimiter)
		{
			$words=explode($delimiter,$string);
			$newwords=array();
			foreach($words as $word)
			{
				if(in_array(mb_strtoupper($word,"UTF-8"),$this->exceptions))
					$word=mb_strtoupper($word,"UTF-8");
				elseif(in_array(mb_strtolower($word,"UTF-8"),$this->exceptions))
					$word=mb_strtolower($word,"UTF-8");
				elseif(!in_array($word,$this->exceptions))
					$word=ucfirst($word);

				array_push($newwords,trim($word));
			}
			$string=join($delimiter,$newwords);
		}
		return $string;
	}
}