<?php
class User extends CActiveRecord
{
	/**
	 * The followings are the available columns in table:
	 * @var integer $id
	 * @var string $username
	 * @var string $password
	 * @var integer $role
	 * @var string $salt
	 * @var string $firstname
	 * @var string $lastname
	 * @var string $usergroup
	 */

	public $repeatPassword;

	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return isset(Yii::app()->getModule('user')->userTable)
			? Yii::app()->getModule('user')->userTable
			: Yii::app()->controller->module->userTable;
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('firstname, lastname', 'required'),
			array('username, password, repeatPassword', 'required', 'on'=>'create'),
			array('password, repeatPassword', 'required', 'on'=>'changePassword'),
			array('password', 'compare', 'compareAttribute'=>'repeatPassword','on'=>'create, changePassword'),
			array('username', 'unique','caseSensitive'=>false),
			array('username', 'match', 'pattern'=>'/^[A-Za-z0-9_]+$/u','message'=>Yii::t('UserModule.ui', '{attribute} can only contain alphanumeric symbols.')),
			array('username, password, salt, firstname, lastname, usergroup', 'length', 'max'=>64),
			array('role', 'length', 'max'=>16),
			array('repeatPassword', 'safe'),
			array('username, firstname, lastname, role, usergroup', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'username' => Yii::t('UserModule.ui', 'Username'),
			'password' => Yii::t('UserModule.ui', 'Password'),
			'role' => Yii::t('UserModule.ui', 'Role'),
			'firstname' => Yii::t('UserModule.ui', 'Firstname'),
			'lastname' => Yii::t('UserModule.ui', 'Lastname'),
			'usergroup' => Yii::t('UserModule.ui', 'User Group'),
			'repeatPassword' => Yii::t('UserModule.ui', 'Repeat password'),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria(array(
			'condition'=>"username!='admin'",
		));
		$criteria->compare('LOWER(username)',$this->formatSearch($this->username),true);
		$criteria->compare('LOWER(firstname)',$this->formatSearch($this->firstname),true);
		$criteria->compare('LOWER(lastname)',$this->formatSearch($this->lastname),true);
		$criteria->compare('role',$this->role);
		$criteria->compare('usergroup',$this->usergroup);

		return new CActiveDataProvider('User', array(
			'criteria'=>$criteria,
			'pagination'=>array(
				'pageSize'=>Yii::app()->params['pageSize'],
			),
			'sort'=>array(
				'defaultOrder'=>array('username'=>false),
			),
		));
	}

	/**
	 * Checks if the given password is correct.
	 * @param string the password to be validated
	 * @return boolean whether the password is valid
	 */
	public function validatePassword($password)
	{
		return $this->hashPassword($password,$this->salt)===$this->password;
	}

	/**
	 * Generates the password hash.
	 * @param string password
	 * @param string salt
	 * @return string hash
	 */
	public function hashPassword($password,$salt)
	{
		return md5($salt.$password);
	}

	/**
	 * Generates a salt that can be used to generate a password hash.
	 * @return string the salt
	 */
	protected function generateSalt()
	{
		return uniqid('',true);
	}

	/**
	 * @return array user options
	 */
	public function getOptions()
	{
		return CHtml::listData($this->findAll(array(
			'select'=>"id, firstname||' '||lastname as lastname",
			'order'=>'lastname, firstname'
		)),'id', 'lastname');
	}

	/**
	 * @return array group options
	 */
	public function getGroupOptions()
	{
		$options=array();
		$usergroups=Yii::app()->controller->module->userGroups;
		foreach($usergroups as $group)
			$options[$group]=Yii::t('ui',XHtml::labelize($group));
		return $options;
	}

	/**
	 * @return string the group name
	 */
	public function getGroupName()
	{
		return Yii::t('ui',XHtml::labelize($this->usergroup));
	}

	/**
	 * @return array role options
	 */
	public function getRoleOptions()
	{
		$options=array();
		$roles=array_keys(Yii::app()->authManager->getRoles());
		foreach($roles as $role)
			$options[$role]=Yii::t('ui',XHtml::labelize($role));
		return $options;
	}

	/**
	 * @return string the role name
	 */
	public function getRoleName()
	{
		return Yii::t('ui',XHtml::labelize($this->role));
	}

	/**
	 * @param string search from user input
	 * @param boolean whether to convert meta symbols
	 * @return string search for sql
	 */
	protected function formatSearch($str, $symbols=false)
	{
		if($symbols===true)
			$str=strtr($str, array('*'=>'%','?'=>'_'));
		return mb_strtolower(trim($str));
	}

	/**
	 * This is invoked before the record is saved.
	 * @return boolean whether the record should be saved.
	 */
	protected function beforeSave()
	{
		if(parent::beforeSave())
		{
			if($this->scenario!='update')
			{
				$this->salt=$this->generateSalt();
				$this->password=$this->hashPassword($this->password,$this->salt);
			}
			return true;
		}
		else
			return false;
	}

	/**
	 * This is invoked after the record is saved.
	 */
	protected function afterSave()
	{
		parent::afterSave();
		if(Yii::app()->controller->module->rbac!==false)
		{
			$auth = Yii::app()->authManager;

			// revoke all auth items assigned to the user
			$items = $auth->getRoles($this->id);
			foreach ($items as $item)
				$auth->revoke($item->name, $this->id);

			// assign new role to the user
			if($auth->assign($this->role,$this->id))
				$auth->save();
		}
	}

	/**
	 * This is invoked after the record is deleted.
	 */
	protected function afterDelete()
	{
		parent::afterDelete();
		if(Yii::app()->controller->module->rbac!==false)
		{
			// revoke all auth items assigned to the user
			$auth = Yii::app()->authManager;
			$items = $auth->getRoles($this->id);
			foreach ($items as $item)
				$auth->revoke($item->name, $this->id);
			$auth->save();
		}
	}
}