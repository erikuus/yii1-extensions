<?php
/**
 * XVauUserIdentity class authenticates user based on VauID 2.0 protocol
 *
 * @link http://www.ra.ee/apps/vauid/
 * @link https://github.com/erikuus/yii1-extensions/tree/master/components/vauid#readme
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

class XVauUserIdentity extends CBaseUserIdentity
{
	const ERROR_INVALID_DATA=1;
	const ERROR_EXPIRED_DATA=2;
	const ERROR_SYNC_DATA=3;
	const ERROR_UNAUTHORIZED=4;

	/**
	 * @see https://www.ra.ee/apps/vauid/index.php/site/version2
	 * @var string JSON posted back by VAU after successful login.
	 */
	protected $jsonData;
	/**
	 * @var string the unique identifier for the identity.
	 */
	protected $id;
	/**
	 * @var string the display name for the identity.
	 */
	protected $name;

	/**
	 * @param string json data posted back by VAU after successful login.
	 */
	public function __construct($jsonData=null)
	{
		$this->jsonData=$jsonData;
	}

	/**
	 * @return string the unique identifier for the identity.
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string the display name for the identity.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Authenticates VAU user.
	 * @see https://github.com/erikuus/yii1-extensions/tree/master/components/vauid#readme
	 * @param array $options the authentication options. The array keys are
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate($options=array())
	{
		$vauUserData=CJSON::decode($this->jsonData);

		// validate json
		if(json_last_error()==JSON_ERROR_NONE)
		{
			// validate that data was posted within one minute
			if((time()-strtotime($vauUserData['timestamp']))<60)
			{
				// validate access rules
				if($this->checkAccess($vauUserData, $options))
				{
					// authenticate user in application database and
					// sync VAU and application user data if required
					if($this->getValue($options,'dataMapping'))
					{
						$this->checkRequiredDataMapping($options);
						$user=$this->findUser($vauUserData, $options);

						if($user===null)
						{
							if($this->getValue($options,'dataMapping.create'))
								$user=$this->createUser($vauUserData, $options);
							else
								$this->errorCode=self::ERROR_UNAUTHORIZED;
						}
						elseif($this->getValue($options,'dataMapping.update'))
							$user=$this->updateUser($user, $vauUserData, $options);

						if(!in_array($this->errorCode, array(self::ERROR_UNAUTHORIZED, self::ERROR_SYNC_DATA)))
						{
							// assign identity attributes
							$this->id=$user->primaryKey;
							$this->name=$this->getValue($options,'dataMapping.name') ?
								$user->{$this->getValue($options,'dataMapping.name')} :
								$vauUserData['fullname'];
							$this->errorCode=self::ERROR_NONE;
						}
					}
					else
					{
						// assign identity attributes
						$this->id=$vauUserData['id'];
						$this->name=$vauUserData['fullname'];
						$this->errorCode=self::ERROR_NONE;
					}
				}
				else
					$this->errorCode=self::ERROR_UNAUTHORIZED;
			}
			else
				$this->errorCode=self::ERROR_EXPIRED_DATA;
		}
		else
			$this->errorCode=self::ERROR_INVALID_DATA;

		return !$this->errorCode;
	}

	/**
	 * Check whether user can be authenticated by access rules
	 * @param array $vauUserData the user data based on VauID 2.0 protocol
	 * @param array $authOptions the authentication options
	 * @return boolean whether access is granted
	 * @see authenticate()
	 */
	protected function checkAccess($vauUserData, $authOptions)
	{
		if($this->getValue($authOptions,'accessRules.safelogin')===true && $vauUserData['safelogin']!==true)
			return false;

		if($this->getValue($authOptions,'accessRules.safehost')===true && $vauUserData['safehost']!==true)
			return false;

		if($this->getValue($authOptions,'accessRules.safe')===true && $vauUserData['safelogin']!==true && $vauUserData['safehost']!==true)
			return false;

		if($this->getValue($authOptions,'accessRules.employee')===true && $vauUserData['type']!=1)
			return false;

		$accessRulesRoles=$this->getValue($authOptions,'accessRules.roles',array());
		$vauUserDataRoles=$this->getValue($vauUserData,'roles',array());

		if($accessRulesRoles!==array() && array_intersect($accessRulesRoles,$vauUserDataRoles)===array())
			return false;

		return true;
	}

	/**
	 * Check whether required data mapping parameters are set
	 * @param array $authOptions the authentication options
	 * @throws CException
	 * @see authenticate()
	 */
	protected function checkRequiredDataMapping($authOptions)
	{
		if(!$this->getValue($authOptions,'dataMapping.model') || !$this->getValue($authOptions,'dataMapping.id'))
			throw new CException('Model name and vauid have to be set in data mapping!');
	}

	/**
	 * Find user
	 * @param array $vauUserData the user data based on VauID 2.0 protocol
	 * @param array $authOptions the authentication options
	 * @return CActiveRecord | null
	 * @see authenticate()
	 */
	protected function findUser($vauUserData, $authOptions)
	{
		$model=CActiveRecord::model($this->getValue($authOptions,'dataMapping.model'));

		return $model->findByAttributes(array(
			$this->getValue($authOptions,'dataMapping.id')=>(int)$vauUserData['id']
		));
	}

	/**
	 * Create new user
	 * @param array $vauUserData the user data based on VauID 2.0 protocol
	 * @param array $authOptions the authentication options
	 * @return CActiveRecord
	 * @see authenticate()
	 */
	protected function createUser($vauUserData, $authOptions)
	{
		$modelName=$this->getValue($authOptions,'dataMapping.model');

		$user=$this->getValue($authOptions,'dataMapping.scenario') ?
			new $modelName($this->getValue($authOptions,'dataMapping.scenario')) :
			new $modelName();

		$user->{$this->getValue($authOptions,'dataMapping.id')}=$vauUserData['id'];

		foreach($this->getValue($authOptions,'dataMapping.attributes') as $key=>$attribute)
			$user->{$attribute}=$this->getValue($vauUserData, $key);

		if(!$user->save())
			$this->errorCode=self::ERROR_SYNC_DATA;

		return $user;
	}

	/**
	 * Update user
	 * @param CActiveRecord the user object
	 * @param array $vauUserData the user data based on VauID 2.0 protocol
	 * @param array $authOptions the authentication options
	 * @return CActiveRecord
	 * @see authenticate()
	 */
	protected function updateUser($user, $vauUserData, $authOptions)
	{
		if($this->getValue($authOptions,'dataMapping.scenario'))
			$user->scenario=$this->getValue($authOptions,'dataMapping.scenario');

		foreach($this->getValue($authOptions,'dataMapping.attributes') as $key=>$attribute)
			$user->{$attribute}=$this->getValue($vauUserData,$key);

		if(!$user->save())
			$this->errorCode=self::ERROR_SYNC_DATA;

		return $user;
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