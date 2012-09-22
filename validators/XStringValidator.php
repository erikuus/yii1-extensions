<?php
/**
 * XStringValidator validator
 *
 * As compared to CStringValidator, this one also checks for valid characters and
 * automatically defines 'encoding' parameter
 *
 * The following shows how to use this validator on model actions() method
 * <pre>
 * return array(
 *     array('attr1','XStringValidator','max'=>64),
 * );
 * </pre>
 */
class XStringValidator extends CStringValidator
{
	public $wrongCharset;  // custom message for wrong character set

	public function __construct() {
		$this->encoding = Yii::app()->charset;
	}

	protected function validateAttribute($object,$attribute) {
		$value = $object->$attribute;
		if (!$this->isCharsetCorrect($value)) {
			$message=$this->wrongCharset !== null ? $this->wrongCharset : Yii::t('yii','Wrong character set.');
			$object->$attribute = '';
			$this->addError($object,$attribute,$message);
		}
		parent::validateAttribute($object,$attribute);
	}

	public function isCharsetCorrect($string) {
		$string = (string)$string;
		$convertCS = 'UTF-8';
		$sourceCS = Yii::app()->charset;
		return $string === mb_convert_encoding ( mb_convert_encoding ( $string, $convertCS, $sourceCS ), $sourceCS, $convertCS );
	}
}