<?php

/**
 * XBusinessRegisterValidator
 *
 * Validates that institution name matches Estonian Business Registry code returned by API.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.1
 */
class XBusinessRegisterValidator extends CValidator
{
	/**
	 * @var string the url to Estonian Business Registry API
	 */
	public $apiUrl='https://ariregister.rik.ee/est/api/autocomplete';

	/**
	 * @var string the name of registry code attribute
	 */
	public $regCodeAttribute;

	/**
	 * @var boolean whether the registry code attribute value can be null or empty. Defaults to true,
	 * meaning that if the attribute is empty, it is considered valid.
	 */
	public $allowRegCodeAttributeEmpty=true;

	/**
	 * @var boolean $enableLogging whether to log failed login requests.
	 */
	public $enableLogging=true;

	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param CModel the object being validated
	 * @param string the attribute being validated
	 */
	protected function validateAttribute($object, $attribute)
	{
		$code=trim($object->{$this->regCodeAttribute});
		$name=trim($object->$attribute);

		if($this->allowRegCodeAttributeEmpty && $this->isEmpty($code))
			return;

		try
		{
			$apiResponse=$this->requestApi($name);
		}
		catch(Exception $e)
		{
			if($this->enableLogging)
				Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
			return;
		}

		if($apiResponse['status']!='OK')
			return;

		foreach($apiResponse['data'] as $row)
		{
			if($row['reg_code']==$code && $row['name']==$name)
				return;
		}

		$this->addError($object,$attribute,Yii::t(__CLASS__ . '.' . __CLASS__, 'The {attribute} does not match registry code!'));
	}

	/**
	 * Sends request to Estonian Business Registry
	 * @param string the institution name
	 * @param array the api resonse
	 * [
	 *   "status" => "OK",
	 *   "data" => [
	 *       [
	 *         "company_id" => 9000176081,
	 *         "reg_code" => 70000310,
	 *         "name" => "Registrite ja Infosüsteemide Keskus",
	 *         "historical_names" => [],
	 *         "status" => "R",
	 *         "legal_address" => "Harju maakond, Tallinn, Kesklinna linnaosa, Lubja tn 4",
	 *         "zip_code" => "10115",
	 *         "url" => "https://ariregister.rik.ee/est/company/70000310/Registrite-ja-Infosüsteemide-Keskus"
	 *       ]
	 *    ]
	 * ];
	 */
	protected function requestApi($name)
	{
		$handle=curl_init();

		$name=$name.' ';
		$name=str_replace(' ', '+', $name);

		curl_setopt_array($handle, array(
			CURLOPT_URL => $this->apiUrl.'?q='.$name,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_SSL_VERIFYHOST=>2,
			CURLOPT_SSL_VERIFYPEER=>false,
			CURLOPT_CUSTOMREQUEST => "GET"
		));

		$response=curl_exec($handle);
		$error=curl_error($handle);
		$errorNumber=curl_errno($handle);
		curl_close($handle);

		if($error)
			throw new Exception('CURL error: '.$response.':'.$error.': '.$errorNumber.$this->apiUrl.'?q='.$name);

		return json_decode($response, true);
	}
}