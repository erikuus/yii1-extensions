<?php

/**
 * XVauUserIdentity class file.
 *
 * XVauUserIdentity class authenticates user based on VauID 2.0 protocol
 *
 * You can use XVauUserIdentity as a stand-alone extension.
 *
 * First set up 'vauLogin' action in application SiteController:
 * <pre>
 * public function actionVauLogin()
 * {
 *     if(isset($_POST['postedData']))
 *     {
 *         Yii::import('ext.components.vauid.XVauUserIdentity');
 *         $identity=new XVauUserIdentity($_POST['postedData']);
 *         $identity->authenticate();
 *         if($identity->errorCode==XVauUserIdentity::ERROR_NONE)
 *         {
 *             Yii::app()->user->login($identity);
 *             $this->redirect(Yii::app()->user->returnUrl));
 *         }
 *         else
 *             throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
 *     }
 *     else
 *         throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
 * }
 * </pre>
 *
 * Note that you can set options in {@see authenticate()} to restrict access to authentication
 * and/or integrate VAU user data with user data in application database!
 *
 * Then insert VAU login/logout links into application:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>'http://www.ra.ee/vau/index.php/site/login?v=2&s=user&remoteUrl='.$this->createAbsoluteUrl('/site/vauLogin'),
 *             'visible'=>Yii::app()->user->isGuest,
 *         )
 *         array(
 *             'label'=>Yii::t('ui', 'Logout'),
 *             'url'=>'http://www.ra.ee/vau/index.php/site/logout?remoteUrl='.$this->createAbsoluteUrl('/site/logout'),
 *             'visible'=>!Yii::app()->user->isGuest,
 *         ),
 * ));
 * </pre>
 *
 * Finally point your 'login' action to VAU login page:
 * <pre>
 * public function actionLogin()
 * {
 *     $this->redirect('http://www.ra.ee/vau/index.php/site/login?v=2&s=user&remoteUrl='.$this->createAbsoluteUrl('/site/vauLogin'));
 * }
 * </pre>
 *
 * However you are strongly encouraged to configure alternative setup using:
 * <ul>
 * <li>{@link XVauLoginAction} for setting up login action</li>
 * <li>{@link XVauRedirectLoginAction} for setting up login action</li>
 * <li>{@link XVauHelper} for VAU login and logout links</li>
 * <ul>
 *
 * So, first import XVauHelper in main/config:
 * <pre>
 * 'import'=>array(
 *     'ext.components.vauid.XVauHelper',
 * ),
 * </pre>
 *
 * Then set up SiteController actions() as follows:
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'vauLogin'=>array(
 *             'class'=>'ext.components.vauid.XVauLoginAction'
 *         ),
 *         'login'=>array(
 *             'class'=>'ext.components.vauid.XVauRedirectLoginAction'
 *         ),
 *     );
 * }
 * </pre>
 *
 * And finally insert VAU login and logout links as follows:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>XVauHelper::loginUrl(),
 *             'visible'=>Yii::app()->user->isGuest,
 *         )
 *         array(
 *             'label'=>Yii::t('ui', 'Logout'),
 *             'url'=>XVauHelper::logoutUrl(),
 *             'visible'=>!Yii::app()->user->isGuest,
 *         ),
 * ));
 * </pre>
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
	const ERROR_ACCESS_DENIED=4;

	/**
	 * @var string encrypted JSON posted back by VAU after successful login.
	 * When decrypted this string can be for example:
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
	protected $postedData;
	/**
	 * @var string the unique identifier for the identity.
	 */
	protected $id;
	/**
	 * @var string the display name for the identity.
	 */
	protected $name;
	/**
	 * @var string the encryption key for VAU remote login
	 */
	private $_key='##########';

	/**
	 * Constructor
	 * @param string encrypted data posted back by VAU after successful login.
	 */
	public function __construct($postedData=null)
	{
		$this->postedData=$postedData;
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
	 *         <li>safelogin: whether authentication is allowed only if user logged into
	 *         VAU using ID-card or Mobile-ID.</li>
	 *         <li>safehost: whether authentication is allowed only if user logged into
	 *         VAU from host that is recognized as safe in VauID 2.0 protocol.</li>
	 *         <li>safe: whether authentication is allowed only if at least one of the above conditions
	 *         are met, i.e. user logged into VAU using ID-card or Mobile-ID or from the safe host.</li>
	 *         <li>employee: whether authentication is allowed only if VAU user type is employee.</li>
	 *         <li>roles: the list of VAU role names; authentication is allowed only if user has at
	 *         least one role in VAU that is present in this list.</li>
	 *     </ul>
	 *     <li>dataMapping</li>
	 *     <ul>
	 *         <li>model: the name of the model that stores user data in the application.</li>
	 *         <li>scenario: the name of the scenario that is used to save VAU user data.</li>
	 *         <li>id: the name of the column that stores VAU user id in the application model.</li>
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
		// decrypt posted data
		$jsonData=$this->lindecrypt(@$this->hex2bin($this->postedData));

		// validate json
		if(json_last_error()==JSON_ERROR_NONE)
		{
			// decode json into array
			$vauUserData=CJSON::decode($jsonData);

			// validate that data was posted within one minute
			if((time() - strtotime($vauUserData['timestamp'])) < 60)
			{
				// validate access rules
				if($this->checkAccess($vauUserData, $options))
				{
					// authenticate user in application database and
					// sync VAU and application user data if required
					if($this->getValue($options, 'dataMapping'))
					{
						// set variables for convenience
						$modelName=$this->getValue($options, 'dataMapping.modelName');
						$scenario=$this->getValue($options, 'dataMapping.scenario');
						$vauIdAttribute=$this->getValue($options, 'dataMapping.vauid');
						$enableCreate=$this->getValue($options, 'dataMapping.enableCreate');
						$enableUpdate=$this->getValue($options, 'dataMapping.enableUpdate');
						$syncAttributes=$this->getValue($options, 'dataMapping.syncAttributes');

						// check required
						if(!$modelName || !$vauIdAttribute)
							throw new CException('Model name and vauid have to be set in data mapping!');

						// check if user with given id exists
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
								$user=new User($scenario);
								$user->{$vauIdAttribute}=$vauUserData['id'];
							}
							else
								$this->errorCode=self::ERROR_ACCESS_DENIED;
						}

						// sync user data
						if(($enableCreate && $user->isNewRecord) || $enableUpdate)
						{
							$user->scenario=$scenario;

							foreach ($syncAttributes as $key=>$attribute)
								$user->{$attribute}=$this->getValue($vauUserData, $key);

							if($user->save())
							{
								// assign identity attributes
								$this->id=$user->id;
								$this->name=$vauUserData['fullname'];
								$this->errorCode=self::ERROR_NONE;
							}
							else
								$this->errorCode=self::ERROR_SYNC_DATA;
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
					{
						// assign identity attributes
						$this->id=$vauUserData['id'];
						$this->name=$vauUserData['fullname'];
						$this->errorCode=self::ERROR_NONE;
					}
				}
				else
					$this->errorCode=self::ERROR_ACCESS_DENIED;
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
		if($this->getValue($authOptions, 'accessRules.safelogin')===true && $vauUserData['safelogin']!==true)
			return false;

		if($this->getValue($authOptions, 'accessRules.safehost')===true && $vauUserData['safehost']!==true)
			return false;

		if($this->getValue($authOptions, 'accessRules.safe')===true && $vauUserData['safelogin']!==true && $vauUserData['safehost']!==true)
			return false;

		if($this->getValue($authOptions, 'accessRules.employee')===true && $vauUserData['type']!=1)
			return false;

		if(array_intersect($this->getValue($authOptions, 'accessRules.roles', array()), $vauUserData['roles'])===array())
			return false;

		return true;
	}

	/**
	 * Decryptes data posted back by VAU after successful login.
	 * @param string $encrypted the encrypted data
	 * @return string decrypted data
	 */
	protected function lindecrypt($encrypted)
	{
		$iv_size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv=mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypted=mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->_key, $encrypted, MCRYPT_MODE_ECB, $iv);
		return rtrim($decrypted);
	}

	/**
	 * Decodes a hexadecimally encoded binary string.
	 * Note that generic hex2bin function is available since PHP 5.4.0
	 * @param hexadecimal representation of data.
	 * @return the binary representation of the given data.
	 */
	protected function hex2bin($h)
	{
		if(!is_string($h))
			return null;
		$r='';
		for($a=0;$a<strlen($h);$a+=2)
			$r.=chr(hexdec($h{$a}.$h{($a+1)}));
		return $r;
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
	protected function getValue($array, $key, $default = null)
	{
		if ($key instanceof \Closure)
			return $key($array, $default);

		if (is_array($key))
		{
			$lastKey = array_pop($key);
			foreach ($key as $keyPart)
				$array = $this->getValue($array, $keyPart);

			$key = $lastKey;
		}

		if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array)))
			return $array[$key];

		if (($pos = strrpos($key, '.')) !== false)
		{
			$array = $this->getValue($array, substr($key, 0, $pos), $default);
			$key = substr($key, $pos + 1);
		}

		if (is_object($array))
			return $array->$key;
		elseif (is_array($array))
			return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;

		return $default;
	}
}