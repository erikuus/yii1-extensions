<?php

/**
 * XVauUserIdentity class file.
 *
 * XVauUserIdentity class authenticates user based on VauID 2.0 protocol
 *
 * For usage refer to {@link XVauLoginAction}
 *
 * @link http://www.ra.ee/apps/vauid/
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
	 * @var string JSON posted back by VAU after successful login.
	 * {
	 *   "id":3,
	 *   "type":1,
	 *   "firstname":"Erik",
	 *   "lastname":"Uus",
	 *   "fullname":"Erik Uus",
	 *   "birthday":"1973-07-30",
	 *   "email":"erik.uus@ra.ee",
	 *   "phone":"53225399",
	 *   "lang":"et",
	 *   "country":"EE",
	 *   "warning":false,
	 *   "safelogin":false,
	 *   "safehost":true,
	 *   "timestamp":"2020-01-27T14:42:31+02:00",
	 *   "roles":["ClientManager","EnquiryManager"]
	 * }
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
	 * Constructor
	 * @param string json data posted back by VAU after successful login.
	 */
	public function __construct($jsonData=null)
	{
		$this->jsonData=$jsonData;
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
	 * Authenticates VAU user.
	 * @param array $options the authentication options. The array keys are
	 * 'accessRules' and 'dataMapping' and the array values are subarrays
	 * with keys as follows:
	 * <ul>
	 *     <li>accessRules</li>
	 *     <ul>
	 *         <li>safelogin: whether access is allowed only if user logged into
	 *         VAU using ID-card or Mobile-ID.</li>
	 *         <li>safehost: whether access is allowed only if user logged into
	 *         VAU from host that is recognized as safe in VauID 2.0 protocol.</li>
	 *         <li>safe: whether access is allowed only if at least one of the above conditions
	 *         are met, i.e. user logged into VAU using ID-card or Mobile-ID or from the safe host.</li>
	 *         <li>employee: whether access is allowed only if VAU user type is employee.</li>
	 *         <li>roles: the list of VAU role names; access is allowed only if user has at
	 *         least one role in VAU that is present in this list.</li>
	 *     </ul>
	 *     <li>dataMapping</li>
	 *     <ul>
	 *         <li>model: the name of the model that stores user data in the application.</li>
	 *         <li>scenario: the name of the scenario that is used to save VAU user data.</li>
	 *         <li>id: the name of the model attribute that stores VAU user id in the application.</li>
	 *         <li>name: the name of the model attribute that stores user name in the application.
	 *         In user session a value of this attribute will be assigned to Yii::app()->user->name.</li>
	 *         <li>create: whether new user should be created in application based on VAU user data
	 *         if there is no user with given VAU user id.</li>
	 *         <li>update: whether user data in application database should be overwritten with
	 *         VAU user data every time user is authenticated.</li>
	 *         <li>attributes: the list of mapping VauID 2.0 user data elements onto user model
	 *         attributes in the application.</li>
	 *     </ul>
	 * </ul>
	 * <pre>
	 * array(
	 *     'accessRules' => array(
	 *         'safelogin'=>true,
	 *         'safehost'=>true,
	 *         'safe'=>true,
	 *         'employee'=>true,
	 *         'roles'=>array(
	 *             'ClientManager',
	 *             'EnquiryManager'
	 *          )
	 *     ),
	 *     'dataMapping'=>array(
	 *         'model'=>'User',
	 *         'scenario'=>'vauid',
	 *         'id'=>'vau_id',
	 *         'name'=>'username',
	 *         'create'=>false,
	 *         'update'=>false,
	 *         'attributes'=>array(
	 *             'firstname'=>'first_name',
	 *             'lastname'=>'last_name'
	 *         )
	 *     )
	 * )
	 * </pre>
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate($options=array())
	{
		// decode json into array
		$vauUserData=CJSON::decode($this->jsonData);

		// validate json
		if(json_last_error()==JSON_ERROR_NONE)
		{
			// validate that data was posted within one minute
			if((time()-strtotime($vauUserData['timestamp']))<60)
			{
				// validate access rules
				if($this->checkAccess($vauUserData,$options))
				{
					// authenticate user in application database and
					// sync VAU and application user data if required
					if($this->getValue($options,'dataMapping'))
					{
						// set variables for convenience
						$modelName=$this->getValue($options,'dataMapping.model');
						$scenario=$this->getValue($options,'dataMapping.scenario');
						$vauIdAttribute=$this->getValue($options,'dataMapping.id');
						$userNameAttribute=$this->getValue($options,'dataMapping.name');
						$enableCreate=$this->getValue($options,'dataMapping.create');
						$enableUpdate=$this->getValue($options,'dataMapping.update');
						$syncAttributes=$this->getValue($options,'dataMapping.attributes');

						// check required
						if(!$modelName || !$vauIdAttribute)
							throw new CException('Model name and vauid have to be set in data mapping!');

						$user=CActiveRecord::model($modelName)->findByAttributes(array(
							$vauIdAttribute=>(int)$vauUserData['id']
						));

						// if there is no user with given vau id
						// create new user if $enableCreate is true
						// otherwise access is denied
						if($user===null)
						{
							if($enableCreate)
							{
								$user=new $modelName();
								$user->{$vauIdAttribute}=$vauUserData['id'];

								foreach($syncAttributes as $key=>$attribute)
									$user->{$attribute}=$this->getValue($vauUserData,$key);

								if(!$user->save())
									$this->errorCode=self::ERROR_SYNC_DATA;
							}
							else
								$this->errorCode=self::ERROR_UNAUTHORIZED;
						}
						elseif($enableUpdate)
						{
							foreach($syncAttributes as $key=>$attribute)
								$user->{$attribute}=$this->getValue($vauUserData,$key);

							if(!$user->save())
								$this->errorCode=self::ERROR_SYNC_DATA;
						}

						if(!in_array($this->errorCode, array(self::ERROR_UNAUTHORIZED, self::ERROR_SYNC_DATA)))
						{
							// assign identity attributes
							$this->id=$user->primaryKey;
							$this->name=$userNameAttribute ? $user->{$userNameAttribute} : $vauUserData['fullname'];
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

		if($this->getValue($authOptions,'accessRules.safe')===true && $vauUserData['safelogin']!==true&&$vauUserData['safehost']!==true)
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