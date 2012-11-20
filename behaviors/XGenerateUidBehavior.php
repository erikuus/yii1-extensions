<?php

/**
 * XGenerateUidBehavior
 *
 * This behavior generates unique id as combination of timedata and random number
 *
 * It can be  be attached to a model on its behaviors() method:
 * <pre>
 *    public function behaviors()
 *    {
 *        return array(
 *            'GenerateUidBehavior' => array(
 *                'class' => 'ext.behaviors.XGenerateUidBehavior',
 *                'attributeName'=>'order_id',
 *            ),
 *        );
 *    }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XGenerateUidBehavior extends CActiveRecordBehavior
{
	/**
	 * @var string the model attribute name that stores unique id
	 */
	public $attributeName;
	/**
	 * @var string the prefix used as first part of id
	 */
	public $prefix;
	/**
	 * @var string the time format used as second part of id
	 */
	public $timeFormat='YmdHis';

	/**
	 * This is invoked before the record is saved.
	 */
	public function beforeValidate($event)
	{
		$owner=$this->getOwner();
		if($owner->isNewRecord)
			$owner->setAttribute($this->attributeName,$this->prefix.date($this->timeFormat).mt_rand(1000,9999));
	}
}