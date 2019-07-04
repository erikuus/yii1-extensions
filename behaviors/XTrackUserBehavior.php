<?php
/**
 * XTrackUserBehavior
 *
 * This behavior adds methods to an ActiveRecord model that enable to save
 * and display information about when and who created or changed a record.
 *
 * It can be attached to a model on its behaviors() method:
 * <pre>
 * public function behaviors()
 * {
 *     return array(
 *         'TrackUserBehavior' => array(
 *             'class' => 'ext.behaviors.XTrackUserBehavior',
 *         ),
 *     );
 * }
 * </pre>
 *
 * NOTE! This behavior requires that the model you want attach this behavior to
 * has following realtions on its relations() method:
 * <pre>
 * return array(
 *     'createUser' => array(self::BELONGS_TO, 'User', 'create_user_id'),
 *     'updateUser' => array(self::BELONGS_TO, 'User', 'update_user_id'),
 * );
 * </pre>
 *
 * Methods that this behavior provides can be grouped as follows:
 * 1) method for saving information (beforeSave)
 * 2) methods for displaying information (follow example below)
 *
 * The following shows how to display create and update information using CDetailView
 * <pre>
 * $this->widget('zii.widgets.CDetailView', array(
 *     'data'=>$model,
 *     'attributes'=>array(
 *         array(
 *             'name'=>'create_time',
 *             'value'=>$model->create_time ? date("d.m.Y H:i:s", $model->create_time):null,
 *         ),
 *         array(
 *             'name'=>'create_user_id',
 *             'value'=>$model->createUserFullname,
 *         ),
 *         array(
 *             'name'=>'update_time',
 *             'value'=>$model->update_time ? date("d.m.Y H:i:s", $model->update_time):null,
 *         ),
 *         array(
 *             'name'=>'update_user_id',
 *             'value'=>$model->updateUserFullname,
 *         ),
 *     ),
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTrackUserBehavior extends CActiveRecordBehavior
{
	/**
	 * @var string the model attribute name for the record creation time
	 */
	public $create_time='create_time';
	/**
	 * @var string the model attribute name for the id of user that created record
	 */
	public $create_user_id='create_user_id';
	/**
	 * @var string the model attribute name for the record modification time
	 */
	public $update_time='update_time';
	/**
	 * @var string the model attribute name for the id of user that modified record
	 */
	public $update_user_id='update_user_id';
	/**
	 * @var string the name of the relation that relates user model to record model by create_user_id
	 */
	public $createRelation='createUser';
	/**
	 * @var string the name of the relation that relates user model to record model by update_user_id
	 */
	public $updateRelation='updateUser';
	/**
	 * @var string the attribute name of the lastname of the related user model
	 */
	public $lastname='lastname';
	/**
	 * @var string the attribute name of the firstname of the related user model
	 */
	public $firstname='firstname';
	/**
	 * @var string the format for current date() function. Defaults to null, meaning the time() function is used instead.
	 */
	public $dateFormat=null;
	/**
	 * @var boolean whether to set update_time and update_user_id on create scenario. Defaults to false.
	 */
	public $updateOnCreate=false;

	/**
	 * @return string fullname of user who created this record
	 */
	public function getCreateUserFullname()
	{
		$owner=$this->getOwner();
		return $owner->getAttribute($this->create_user_id) ? $this->getUserFullname($this->createRelation) : null;
	}

	/**
	 * @return string fullname of user who modified this record
	 */
	public function getUpdateUserFullname()
	{
		$owner=$this->getOwner();
		return $owner->getAttribute($this->update_user_id) ? $this->getUserFullname($this->updateRelation) : null;
	}

	/**
	 * @param string $format the date format
	 * @return string create date or time
	 */
	protected function getCreateTime($format)
	{
		$owner=$this->getOwner();
		if($this->dateFormat)
			return date_format(date_create($owner->getAttribute($this->create_time)),$format);
		else
			return date($format, $owner->getAttribute($this->create_time));
	}

	/**
	 * @param string $format the date format
	 * @return string update date or time
	 */
	protected function getUpdateTime($format)
	{
		$owner=$this->getOwner();
		if($this->dateFormat)
			return date_format(date_create($owner->getAttribute($this->update_time)),$format);
		else
			return date($format, $owner->getAttribute($this->update_time));
	}

	/**
	 * @param string $dateFormat the date format, defaults to 'd.m.Y'
	 * @return string fullname of user who created this record and date created
	 */
	public function getCreateDetail($dateFormat='d.m.Y')
	{
		$owner=$this->getOwner();
		return $this->createUserFullname ? $this->createUserFullname.' '.$this->getCreateTime($dateFormat) : null;
	}

	/**
	 * @param string $dateFormat the date format, defaults to 'd.m.Y'
	 * @return string fullname of user who updated this record and date modified
	 */
	public function getUpdateDetail($dateFormat='d.m.Y')
	{
		$owner=$this->getOwner();
		return $this->updateUserFullname ? $this->updateUserFullname.' '.$this->getUpdateTime($dateFormat) : null;
	}

	/**
	 * This is invoked before the record is saved.
	 */
	public function beforeSave($event)
	{
		$owner=$this->getOwner();
		if($owner->isNewRecord)
		{
			$owner->setAttribute($this->create_time,$this->getCurrentTime());
			$owner->setAttribute($this->create_user_id,Yii::app()->user->id);

			if($this->updateOnCreate)
			{
				$owner->setAttribute($this->update_time,$currentTime);
				$owner->setAttribute($this->update_user_id,Yii::app()->user->id);
			}
		}
		else
		{
			$currentTime=$this->getCurrentTime();
			$owner->setAttribute($this->update_time,$currentTime);
			$owner->setAttribute($this->update_user_id,Yii::app()->user->id);
		}
	}

	/**
	 * @param string the relation name
	 * @return string fullname of related user
	 */
	protected function getUserFullname($relation)
	{
		$owner=$this->getOwner();
		$firstname=$owner->getRelated($relation)->getAttribute($this->firstname);
		$lastname=$owner->getRelated($relation)->getAttribute($this->lastname);
		return $firstname.' '.$lastname;
	}

	/**
	 * @return string current date or time
	 */
	protected function getCurrentTime()
	{
		if($this->dateFormat)
			return date($this->dateFormat);
		else
			return time();
	}
}