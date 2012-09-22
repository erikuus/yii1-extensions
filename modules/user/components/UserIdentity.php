<?php
/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
	private $_id;

	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$username=mb_strtolower($this->username);
		$user=User::model()->find('LOWER(username)=?',array($username));
		if($user===null)
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		else if(!$user->validatePassword($this->password))
			$this->errorCode=self::ERROR_PASSWORD_INVALID;
		else
		{
			$this->_id=$user->id;
			$this->username=$user->username;

			if($user->usergroup)
				$this->setState('usergroup', $user->usergroup);

			if($user->role)
				$this->assignRole($user->role);

			$this->errorCode=self::ERROR_NONE;
		}
		return !$this->errorCode;
	}

	/**
	 * Assign role to a user
	 * @param string $role the role name
	 */
	protected function assignRole($role)
	{
		$auth=Yii::app()->authManager;
		if(!$auth->isAssigned($role,$this->_id))
		{
			if($auth->assign($role,$this->_id))
				$auth->save();
		}
	}
}