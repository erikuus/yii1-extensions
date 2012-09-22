<?php
/**
 * XDateValidator validator
 *
 * The following shows how to use this validator on model actions() method
 * <pre>
 *    return array(
 *        array('date','XDateValidator'),
 *    );
 * </pre>
 *
 * Pay attention that the date's format should be equivalent to the one defined in Yii::app()->locale->dateFormat to avoid problems after validation.
 * If formats differ additional afterValidate()/beforeSave() functions are going to be needed.
 *
 * Note that when dealing with partial dates mandatory date parts need to be added in afterValidate()/beforeSave() methods.
 */
class XDateValidator extends CValidator
{
	/**
	 * @var boolean whether the attribute value can be null or empty. Defaults to true,
	 * meaning that if the attribute is empty, it is considered valid.
	 */
	public $allowEmpty=true;

	/**
	 * @var string the message to be displayed if validation fails
	 */
	public $message;

	/**
	 * @var string date's format, defaults to dd.MM.yyyy
	 */
	public $pattern='dd.MM.yyyy';

	/**
	 * @var string partial date's format, defaults to MM.yyyy
	 */
	public $partialPattern='MM.yyyy';

	/**
	 * @var array default values for month and day if we are dealing with partial dates
	 */
	public $defaults=array(
		'month'=>1,
		'day'=>1
	);

	/**
	 * Validates the attribute of the object.
	 * @param CModel the object being validated
	 * @param string the attribute being validated
	 */
	protected function validateAttribute($object,$attribute)
	{

		if($this->allowEmpty&&$this->isEmpty($object->$attribute))
			return;

		$message=$this->message!==null ? $this->message : Yii::t('yii','{attribute} is invalid.');

		/* trying to parse excact date */
		if(XDateTimeParser::parse($object->$attribute,$this->pattern)==false)
		{
			/* trying to parse partial date */
			if(XDateTimeParser::parse($object->$attribute,$this->partialPattern,$this->defaults)==false)
			{
				/* trying to parse year only */
				if(XDateTimeParser::parse($object->$attribute,'yyyy',$this->defaults)==false)
				{
					$this->addError($object,$attribute,$message);
					return false;
				}
				else
					return true;
			}
			else
				return true;
		}
		else
			return true;
	}
}