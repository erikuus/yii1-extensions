<?php
/**
 * XUrlValidator class file.
 * XUrlValidator validates that the attribute value is a valid http or https URL.
 * This validator is more relaxed then CUrlValidator, accepting URLs with no protocol.
 */
class XUrlValidator extends CValidator
{
	/**
	 * @var string the regular expression used to validates the attribute value.
	 */
	public $protocolPattern='/^(http|https)$/i';

	/**
	 * @var string the regular expression used to validates the attribute value.
	 */
	public $uriPattern='/^(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i';

	/**
	 * @var string default protocol for URLs with no protocol specified.
	 *
	 * Defaults to "http://", meaning "www.google.com" is considered valid. To perform
	 * strict URL validation, set this to null.
	 */
	public $defaultProtocol='http://';

	/**
	 * @var boolean whether the attribute value can be null or empty. Defaults to true,
	 * meaning that if the attribute is empty, it is considered valid.
	 */
	public $allowEmpty=true;

	/**
	 * Validates the attribute of the object.
	 *
	 * If there is any error, the error message is added to the object.
	 *
	 * If $defaultProtocol is enabled, and the URL does not contain a protocol, the attribute
	 * is updated with the default protocol applied to it.
	 *
	 * @param CModel the object being validated
	 * @param string the attribute being validated
	 */
	protected function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		if($this->allowEmpty&&$this->isEmpty($value))
			return;
		if($validated=$this->validateValue($value))
		{
			$object->$attribute=$validated;
		}
		else
		{
			$message=$this->message!==null ? $this->message : Yii::t('yii','{attribute} is not a valid URL.');
			$this->addError($object,$attribute,$message);
		}
	}

	/**
	 * Validates a static value to see if it is a valid URL.
	 *
	 * Note that this method does not respect {@link allowEmpty} property.
	 * This method is provided so that you can call it directly without going through the model validation rule mechanism.
	 *
	 * @param mixed the value to be validated
	 * @return mixed the fully qualified URL (with protocol applied, if missing) or null, if the URL was invalid
	 */
	public function validateValue($value)
	{
		if(!is_string($value))
			return false;

		$parts=explode('://',$value,2);

		if(count($parts)==1&&$this->defaultProtocol!==null)
			return preg_match($this->uriPattern,$parts[0]) ? $this->defaultProtocol.$value : null;
		else
			return count($parts)==2&&preg_match($this->protocolPattern,$parts[0])&&preg_match($this->uriPattern,$parts[1]) ? $value : null;
	}
}