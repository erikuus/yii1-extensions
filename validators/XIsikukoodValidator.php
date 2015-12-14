<?php

/**
 * XIsikukoodValidator
 *
 * Validator for personal identification number of Estonia.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.1
 */
class XIsikukoodValidator extends CValidator
{
	/**
	 * @var boolean whether the attribute value can be null or empty. Defaults to true,
	 * meaning that if the attribute is empty, it is considered valid.
	 */
	public $allowEmpty=true;

	protected function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;

		if($this->allowEmpty && $this->isEmpty($value))
			return;

		// Check format
		if(!preg_match("'^\d{11}$'",$value))
			$this->addError($object,$attribute,Yii::t(__CLASS__ . '.' . __CLASS__, 'The format of {attribute} is invalid.'));
		elseif(!$this->checkSum($value) || !$this->checkDate($value))
			$this->addError($object,$attribute,Yii::t(__CLASS__ . '.' . __CLASS__, 'The {attribute} is invalid.'));
	}

	/**
	 * Check control sum
	 * @param string $value the personal id number of Estonia
	 * @return boolean
	 */
	protected function checkSum($value)
	{
		$s1 = 0;
		$s2 = 0;
		$k1 = 1;
		$k2 = 3;
		for($i = 0; $i < strlen($value)-1; $i++)
		{
			$s1 += $value[$i]*$k1;
			$s2 += $value[$i]*$k2;
			$k1 = ($k1 == 9)? 1 : $k1+1;
			$k2 = ($k2 == 9)? 1 : $k2+1;
		}
		if(($s1%11) < 10)
			$sum = $s1%11;
		else if(($s2%11) < 10)
			$sum = $s2%11;
		else
			$sum = 0;

		return (intval(substr($value, 10, 1))  == $sum);
	}

	/**
	 * Check if date (as part of id number) is in past(true) or future(false) and if date is correct
	 * @param string $value the personal id number of Estonia
	 * @return boolean
	 */
	protected function checkDate($value)
	{
		if(in_array($value[0], array(1, 2)))
			$year = "18" . $value[1] . $value[2];
		else if(in_array($value[0], array(3, 4)))
			$year = "19" . $value[1] . $value[2];
		else if(in_array($value[0], array(5, 6)))
			$year = "20" . $value[1] . $value[2];
		else if(in_array($value[0], array(7, 8)))
			$year = "21" . $value[1] . $value[2];

		$date = $year . "-" . $value[3] . $value[4] . "-" . $value[5] . $value[6];

		if(!$this->validateDate($date))
			return false;

		$date = DateTime::createFromFormat('Y-m-d', $date);
		$now = new DateTime();
		return ($date < $now);
	}

	/**
	 * @link http://php.net/manual/en/function.checkdate.php#113205
	 * @param string $date
	 * @param string $format
	 * @return boolean
	 */
	protected function validateDate($date, $format='Y-m-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}
}