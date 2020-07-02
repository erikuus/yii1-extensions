<?php

/**
 * XDokobitUserIdentity class file.
 *
 * XDokobitUserIdentity class authenticates application user on the data of authenticated
 * user returned by Dokobit Identity Gateway API
 *
 * Note that you can set synchronisation options {@link authenticate()} so that the data of
 * authenticated user returned by Dokobit Identity Gateway API will be saved to application
 * database through active record.
 *
 * Please refer to {@link XDokobitLoginWidget} for complete usage information.
 *
 * @link https://id-sandbox.dokobit.com/api/doc Documentation
 * @link https://support.dokobit.com/category/537-developer-guide Developer guide
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XDokobitUserIdentity extends CBaseUserIdentity
{
	const ERROR_INVALID_DATA=1;
	const ERROR_INVALID_STATUS=2;
	const ERROR_EXPIRED_CERTIFICATE=3;
	const ERROR_SYNC_DATA=4;
	const ERROR_UNAUTHORIZED=5;

	/**
	 * @var array $userData the data of authenticated user returned
	 * by Dokobit Identity Gateway API call /api/authentication/{token}/status
	 * For example:
	 * {
	 *    "status":"ok",
	 *    "certificate":{
	 *        "name":"/C=LT/SN=SMART-ID/GN=DEMO/serialNumber=PNOLT-10101010005/CN=SMART-ID,DEMO,PNOLT-10101010005/OU=AUTHENTICATION",
	 *        "subject":{
	 *            "country":"LT",
	 *            "surname":"SMART-ID",
	 *            "name":"DEMO",
	 *            "serial_number":"PNOLT-10101010005",
	 *            "common_name":"SMART-ID,DEMO,PNOLT-10101010005",
	 *            "organisation_unit":"AUTHENTICATION"
	 *        },
	 *        "issuer":{
	 *            "country":"EE",
	 *            "organisation":"AS Sertifitseerimiskeskus",
	 *            "common_name":"TEST of EID-SK 2016"
	 *        },
	 *        "valid_from":"2017-08-30T15:08:15+03:00",
	 *        "valid_to":"2020-08-30T15:08:15+03:00",
	 *        "value":"LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSUd6RENDQkxTZ0F3SUJBZ0lRTnIrZS9 ..."
	 *    },
	 *    "code":"10101010005",
	 *    "country_code":"lt",
	 *    "name":"DEMO",
	 *    "surname":"SMART-ID",
	 *    "authentication_method":"smartid",
	 *    "date_authenticated":"2019-05-06T12:15:34+03:00"
	 * }
	 */
	protected $userData;
	/**
	 * @var string $id the unique identifier for the identity.
	 */
	protected $id;
	/**
	 * @var string $name the display name for the identity.
	 */
	protected $name;

	/**
	 * Constructor
	 * @param string json $userData the data of authenticated user returned by
	 * Dokobit Identity Gateway API call /api/authentication/{token}/status
	 */
	public function __construct($userData=null)
	{
		$this->userData=$userData;
	}

	/**
	 * Returns the unique identifier for the identity.
	 * This method is required by {@link IUserIdentity}.
	 * @return string the unique identifier for the identity.
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Returns the display name for the identity.
	 * This method is required by {@link IUserIdentity}.
	 * @return string the display name for the identity.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Authenticates user.
	 * @param array $options the synchronisation options that enabled to save the data of
	 * authenticated user returned by Dokobit Identity Gateway API to application
	 * database through active record.
	 *
	 * Possible options include the following:
	 * - modelName: the name of the model that stores user data in the application
	 * - scenarioName: the name of the scenario that is used to save user data
	 * - codeAttributeName: the name of the model attribute that must match api user code
	 * - countryCodeAttributeName: the name of the model attribute that must match api user country code
	 * - usernameAttributeName: the name of the model attribute that stores username in the application
	 * - birthdayAttributeName: the name of the model attribute that stores user birthday in application;
	 *   if set, birthday is extracted form the value of code parameter and assigned to model attribute;
	 *   note that this works only if country code is "ee"
	 * - enableCreate: whether new user should be created in application from the data of authenticated
	 *   user returned by Dokobit Identity Gateway API
	 * - enableUpdate: whether user data in application database should be overwritten with the data of
	 *   authenticated user returned by Dokobit Identity Gateway API
	 * - syncAttributes: the list of mapping the data of authenticated user returned by Dokobit Identity
	 *   Gateway API onto user model attributes in the application
	 *
	 * For example:
	 * <pre>
	 * array(
	 *     'modelName'=>'Kasutaja',
	 *     'scenarioName'=>'dokobit',
	 *     'codeAttributeName'=>'isikukood',
	 *     'countryCodeAttributeName'=>'riigikood',
	 *     'usernameAttributeName'=>'kasutajanimi',
	 *     'birthdayAttributeName'=>'birthday',
	 *     'enableCreate'=>true,
	 *     'enableUpdate'=>true,
	 *     'syncAttributes'=>array(
	 *         'name'=>'eesnimi',
	 *         'surname'=>'perekonnanimi',
	 *         'authentication_method'=>'autentimise_meetod',
	 *         'phone'=>'telefon'
	 *     ),
	 * )
	 * </pre>
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate($options=array())
	{
		// decode json into array
		$userData=CJSON::decode($this->userData);

		// validate json
		if(json_last_error()==JSON_ERROR_NONE)
		{
			// validate that dokobit user data status is ok
			if($userData['status']=='ok')
			{
				// validate that certificate is not expired
				if($this->validateCerificate($userData))
				{
					// authenticate user against application database and
					// sync dokobit and application user data if required
					if($options!==array())
					{
						// set option variables for convenience
						$modelName=$this->getValue($options,'modelName');
						$scenarioName=$this->getValue($options,'scenarioName');
						$codeAttributeName=$this->getValue($options,'codeAttributeName');
						$countryCodeAttributeName=$this->getValue($options,'countryCodeAttributeName');
						$usernameAttributeName=$this->getValue($options,'usernameAttributeName');
						$birthdayAttributeName=$this->getValue($options,'birthdayAttributeName');
						$enableCreate=$this->getValue($options,'enableCreate');
						$enableUpdate=$this->getValue($options,'enableUpdate');
						$syncAttributes=$this->getValue($options,'syncAttributes');

						// check required
						if(!$modelName || !$codeAttributeName || !$countryCodeAttributeName)
							throw new CException('Model name, code and country code attribute names have to be set in options!');

						// try to find user in application database
						$user=CActiveRecord::model($modelName)->findByAttributes(array(
							$codeAttributeName=>$userData['code'],
							$countryCodeAttributeName=>$userData['country_code']
						));

						// if user was not found and create is enabled
						// then create new user
						if($user===null)
						{
							if($enableCreate)
							{
								$user=$scenarioName ? new $modelName($scenarioName) : new $modelName();

								$user->{$codeAttributeName}=$userData['code'];
								$user->{$countryCodeAttributeName}=$userData['country_code'];

								if($usernameAttributeName)
									$user->{$usernameAttributeName}=$userData['code'].'@'.$userData['country_code'];

								if($birthdayAttributeName && $userData['country_code']=='ee')
									$user->{$birthdayAttributeName}=$this->getBirthdayFromCode($userData['code']);

								foreach($syncAttributes as $key=>$attribute)
								{
									$value=$this->getValue($userData,$key);
									if($value)
										$user->{$attribute}=$value;
								}

								if(!$user->save())
									$this->errorCode=self::ERROR_SYNC_DATA;
							}
							else
								$this->errorCode=self::ERROR_UNAUTHORIZED;
						}
						// if user was found and update is enabled
						// then update user information
						elseif($enableUpdate)
						{
							if($scenarioName)
								$user->scenario=$scenarioName;

							if($birthdayAttributeName && $userData['country_code']=='ee')
								$user->{$birthdayAttributeName}=$this->getBirthdayFromCode($userData['code']);

							foreach($syncAttributes as $key=>$attribute)
							{
								$value=$this->getValue($userData,$key);
								if($value)
									$user->{$attribute}=$value;
							}

							if(!$user->save())
								$this->errorCode=self::ERROR_SYNC_DATA;
						}

						// if there are no errors assign identity attributes
						if(!in_array($this->errorCode, array(self::ERROR_UNAUTHORIZED, self::ERROR_SYNC_DATA)))
						{
							$this->id=$user->primaryKey;

							if($usernameAttributeName)
								$this->name=$user->{$usernameAttributeName};
							else
								$this->name=$userData['name'].' '.$userData['surname'];

							$this->errorCode=self::ERROR_NONE;
						}
					}
					else
					{
						// if synchronisation is not set in options
						// just assign identity attributes for user session
						$this->id=$userData['code'].'@'.$userData['country_code'];
						$this->name=$userData['name'].' '.$userData['surname'];
						$this->errorCode=self::ERROR_NONE;
					}
				}
				else
					$this->errorCode=self::ERROR_EXPIRED_CERTIFICATE;
			}
			else
				$this->errorCode=self::ERROR_INVALID_STATUS;
		}
		else
			$this->errorCode=self::ERROR_INVALID_DATA;

		return !$this->errorCode;
	}

	/**
	 * Validate that certificate is not expired
	 * @param array $userData the data of authenticated user returned by Dokobit Identity Gateway API
	 * @return boolean whether certificate is valid
	 */
	protected function validateCerificate($userData)
	{
		$validFrom=$this->getValue($userData,'certificate.valid_from');
		$validTo=$this->getValue($userData,'certificate.valid_to');
		$time=time();

		if($validFrom && $validTo)
			return $time>=strtotime($validFrom) && $time<=strtotime($validTo) ? true : false;
		else
			return true;
	}

	/**
	 * Get birthday form the national identification number of Estonia
	 * @param string $code the national identification number of Estonia
	 * @return birthday in the format 1973-07-30
	 */
	protected function getBirthdayFromCode($code)
	{
		if(in_array($code[0], array(1,2)))
			$year='18'.$code[1].$code[2];
		elseif(in_array($code[0], array(3,4)))
			$year='19'.$code[1].$code[2];
		elseif(in_array($code[0], array(5,6)))
			$year='20'.$code[1].$code[2];
		elseif(in_array($code[0], array(7,8)))
			$year='21'.$code[1].$code[2];
		else
			return null;

		return $year.'-'.$code[3].$code[4].'-'.$code[5].$code[6];
	}

	/**
	 * Retrieves the value of an array element or object property with the given key or property name.
	 * If the key does not exist in the array, the default value will be returned instead.
	 * Not used when getting value from an object.
	 *
	 * The key may be specified in a dot format to retrieve the value of a sub-array or the property
	 * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
	 * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
	 * or `$array->x` is neither an array nor an object, the default value will be returned.
	 * Note that if the array already has an element `x.y.z`, then its value will be returned
	 * instead of going through the sub-arrays. So it is better to be done specifying an array of key names
	 * like `['x', 'y', 'z']`.
	 *
	 * @param array|object $array array or object to extract value from
	 * @param string|\Closure|array $key key name of the array element, an array of keys or property name of the object,
	 * or an anonymous function returning the value. The anonymous function signature should be:
	 * `function($array, $defaultValue)`.
	 * The possibility to pass an array of keys is available since version 2.0.4.
	 * @param mixed $default the default value to be returned if the specified array key does not exist. Not used when
	 * getting value from an object.
	 * @return mixed the value of the element if found, default value otherwise
	 */
	protected function getValue($array, $key, $default=null)
	{
		if($key instanceof \Closure)
			return $key($array,$default);

		if(is_array($key))
		{
			$lastKey=array_pop($key);
			foreach($key as $keyPart)
				$array=$this->getValue($array,$keyPart);

			$key=$lastKey;
		}

		if(is_array($array) && (isset($array[$key]) || array_key_exists($key,$array)))
			return $array[$key];

		if(($pos=strrpos($key,'.'))!==false)
		{
			$array=$this->getValue($array,substr($key,0,$pos),$default);
			$key=substr($key,$pos+1);
		}

		if(is_object($array))
			return $array->$key;
		elseif(is_array($array))
			return (isset($array[$key]) || array_key_exists($key,$array)) ? $array[$key] : $default;

		return $default;
	}
}