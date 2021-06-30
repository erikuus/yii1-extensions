<?php
/**
 * XVauUserIdentity class authenticates user based on VauID 2.0 protocol
 *
 * @link http://www.ra.ee/apps/vauid/
 * @link https://github.com/erikuus/yii1-extensions/tree/master/components/vauid#readme
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

Yii::import('ext.components.vauid.XVauAccessDeniedException');

class XVauUserIdentity extends CBaseUserIdentity
{
	/**
	 * @see https://www.ra.ee/apps/vauid/index.php/site/version2
	 * @var string JSON posted back by VAU after successful login
	 */
	protected $jsonData;
	/**
	 * @var string the unique identifier for the identity
	 */
	protected $id;
	/**
	 * @var string the display name for the identity
	 */
	protected $name;

	/**
	 * @param string json data posted back by VAU after successful login
	 */
	public function __construct($jsonData=null)
	{
		$this->jsonData=$jsonData;
	}

	/**
	 * @return string the unique identifier for the identity
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string the display name for the identity
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Authenticates VAU user
	 * @see https://github.com/erikuus/yii1-extensions/tree/master/components/vauid#readme
	 * @param array $options the authentication options
	 * @param integer $requestLifetime the number of seconds VAU postback is valid
	 * @throws CException or XVauAccessDeniedException if authentication fails
	 */
	public function authenticate($options=array(), $requestLifetime=60)
	{
		$vauUserData=$this->decodeVauUserData();

		$this->checkVauRequestTimestamp($vauUserData['timestamp'], $requestLifetime);
		$this->checkAccess($vauUserData, $options);

		if($this->getValue($options,'dataMapping'))
		{
			$this->checkRequiredDataMapping($options);
			$user=$this->findUser($vauUserData, $options);

			if($user===null)
			{
				if($this->getValue($options,'dataMapping.create'))
					$user=$this->createUser($vauUserData, $options);
				else
					throw new XVauAccessDeniedException('Access denied because user not found and "create" not enabled!');
			}
			elseif($this->getValue($options,'dataMapping.update'))
				$user=$this->updateUser($user, $vauUserData, $options);

			$this->id=$user->primaryKey;
			$this->name=$this->getValue($options,'dataMapping.name') ?
				$user->{$this->getValue($options,'dataMapping.name')} :
				$vauUserData['fullname'];
		}
		else
		{
			$this->id=$vauUserData['id'];
			$this->name=$vauUserData['fullname'];
		}
	}

	/**
	 * Decode JSON posted back by VAU after successful login
	 * @return array VAU user data
	 * @throws CException if decoding fails
	 */
	protected function decodeVauUserData()
	{
		$vauUserData=CJSON::decode($this->jsonData);
		if(json_last_error()==JSON_ERROR_NONE)
			return $vauUserData;
		else
			throw new CException('Failed to decode json posted back by VAU!');
	}

	/**
	 * Check whether VAU request timestamp is valid
	 * @param integer $vauRequestTimestamp the unix time when VAU postback was created
	 * @param integer $requestLifetime the number of seconds VAU postback is valid
	 * @throws CException if VAU request timestamp is not valid
	 */
	protected function checkVauRequestTimestamp($vauRequestTimestamp, $requestLifetime)
	{
		if((time()-strtotime($vauRequestTimestamp)) > $requestLifetime)
			throw new CException('Request timestamp posted back by VAU is not valid!');
	}

	/**
	 * Check whether user can be authenticated by access rules
	 * @param array $vauUserData the user data based on VauID 2.0 protocol
	 * @param array $authOptions the authentication options
	 * @throws XVauAccessDeniedException if access is denied
	 */
	protected function checkAccess($vauUserData, $authOptions)
	{
		$this->checkAccessBySafeloginRule($this->getValue($authOptions,'accessRules.safelogin'), $vauUserData['safelogin']);
		$this->checkAccessBySafehostRule($this->getValue($authOptions,'accessRules.safehost'), $vauUserData['safehost']);
		$this->checkAccessBySafeRule($this->getValue($authOptions,'accessRules.safe'), $vauUserData['safelogin'], $vauUserData['safehost']);
		$this->checkAccessByEmployeeRule($this->getValue($authOptions,'accessRules.employee'), $vauUserData['type']);

		$accessRulesRoles=$this->getValue($authOptions,'accessRules.roles',array());
		$vauUserDataRoles=$this->getValue($vauUserData,'roles',array());
		$this->checkAccessByRolesRule($accessRulesRoles, $vauUserDataRoles);
	}

	/**
	 * Check whether user can be authenticated by safelogin access rules
	 * @param boolean $accessRulesSafelogin the safelogin flag in access rules
	 * @param boolean $vauUserDataSafelogin the safelogin flag in VAU postback
	 * @throws XVauAccessDeniedException if access is denied
	 */
	protected function checkAccessBySafeloginRule($accessRulesSafelogin, $vauUserDataSafelogin)
	{
		if($accessRulesSafelogin===true && $vauUserDataSafelogin!==true)
			throw new XVauAccessDeniedException('Access denied by safelogin rule!');
	}

	/**
	 * Check whether user can be authenticated by safehost access rules
	 * @param boolean $accessRulesSafehost the safehost flag in access rules
	 * @param boolean $vauUserDataSafehost the safehost flag in VAU postback
	 * @throws XVauAccessDeniedException if access is denied
	 */
	protected function checkAccessBySafehostRule($accessRulesSafehost, $vauUserDataSafehost)
	{
		if($accessRulesSafehost===true && $vauUserDataSafehost!==true)
			throw new XVauAccessDeniedException('Access denied by safehost rule!');
	}

	/**
	 * Check whether user can be authenticated by safe access rules
	 * @param boolean $accessRulesSafe the safe flag in access rules
	 * @param boolean $vauUserDataSafelogin the safelogin flag in VAU postback
	 * @param boolean $vauUserDataSafehost the safehost flag in VAU postback
	 * @throws XVauAccessDeniedException if access is denied
	 */
	protected function checkAccessBySafeRule($accessRulesSafe, $vauUserDataSafelogin, $vauUserDataSafehost)
	{
		if($accessRulesSafe===true && $vauUserDataSafelogin!==true && $vauUserDataSafehost!==true)
			throw new XVauAccessDeniedException('Access denied by safe rule!');
	}

	/**
	 * Check whether user can be authenticated by employee access rules
	 * @param boolean $accessRulesEmployee the access rule whether
	 * @param integer $vauUserDataType the type of user in VAU
	 * @throws XVauAccessDeniedException if access is denied
	 */
	protected function checkAccessByEmployeeRule($accessRulesEmployee, $vauUserDataType)
	{
		if($accessRulesEmployee===true && $vauUserDataType!=1)
			throw new XVauAccessDeniedException('Access denied by employee rule!');
	}

	/**
	 * Check whether user can be authenticated by roles access rules
	 * @param array $accessRulesRoles the list of role names in access rules
	 * @param array $vauUserDataRoles the list of role names assigned to user in VAU
	 * @throws XVauAccessDeniedException if access is denied
	 */
	protected function checkAccessByRolesRule($accessRulesRoles, $vauUserDataRoles)
	{
		if($accessRulesRoles!==array() && array_intersect($accessRulesRoles,$vauUserDataRoles)===array())
			throw new XVauAccessDeniedException('Access denied by roles rule!');
	}

	/**
	 * Check whether required data mapping parameters are set
	 * @param array $authOptions the authentication options
	 * @throws CException if data mapping is incomplete
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
	 * @return CActiveRecord the user data | null
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
	 * @return CActiveRecord the user data
	 * @throws CException if save fails
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
			throw new CException('Failed to save VAU user data into application database!');

		return $user;
	}

	/**
	 * Update user
	 * @param CActiveRecord the user object
	 * @param array $vauUserData the user data based on VauID 2.0 protocol
	 * @param array $authOptions the authentication options
	 * @return CActiveRecord the user data
	 * @throws CException if save fails
	 */
	protected function updateUser($user, $vauUserData, $authOptions)
	{
		if($this->getValue($authOptions,'dataMapping.scenario'))
			$user->scenario=$this->getValue($authOptions,'dataMapping.scenario');

		foreach($this->getValue($authOptions,'dataMapping.attributes') as $key=>$attribute)
			$user->{$attribute}=$this->getValue($vauUserData,$key);

		if(!$user->save())
			throw new CException('Failed to save VAU user data into application database!');

		return $user;
	}

	/**
	 * Retrieves the value of an array element or object property with the given key or property name.
	 * If the key does not exist in the array, the default value will be returned instead.
	 *
	 * @param array $array array or object to extract value from
	 * @param string $key key name of the array element.
	 * @param mixed $default the default value to be returned if the specified array key does not exist.
	 * @return mixed the value of the element if found, default value otherwise
	 */
	protected function getValue($array, $key, $default=null)
	{
		if(is_array($array) && (isset($array[$key]) || array_key_exists($key,$array)))
			return $array[$key];

		if(($pos=strrpos($key,'.'))!==false)
		{
			$array=$this->getValue($array,substr($key,0,$pos),$default);
			$key=substr($key,$pos+1);
		}

		if(is_array($array))
			return (isset($array[$key]) || array_key_exists($key,$array)) ? $array[$key] : $default;

		return $default;
	}
}