<?php

/**
 * XGenerateDocNrBehavior
 *
 * This behavior generates document number as combination of prefix, current year, [optionally some attribute] and count per current year.
 *
 * It can be  be attached to a model on its behaviors() method:
 * <pre>
 *    public function behaviors()
 *    {
 *        return array(
 *            'XGenerateDocNrBehavior' => array(
 *                'class' => 'ext.behaviors.XGenerateDocNrBehavior',
 *                'attributeName'=>'document_no',
 *                'groupByAttribute'=>'user_id',
 *                'yearExpression'=>'year(created)',
 *                'prefix'=>'INV',
 *                'format'=>'%06d',
 *                'separator'=>'_'
 *            ),
 *        );
 *    }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XGenerateDocNrBehavior extends CActiveRecordBehavior
{
	/**
	 * @var string the model attribute name that stores document number
	 */
	public $attributeName;
	/**
	 * @var string the model attribute name by what document numbers are grouped by
	 */
	public $groupByAttribute;
	/**
	 * @var string the sql expression needed to query documenst items of
	 * Examples
	 * MySql: year({attribute})
	 * PostgreSql: date_part('year', {attribute})
	 * PostgreSql (unix timestamp): date_part('year', {attribute}::abstime)
	 * NOTE! You have to change {attribute} for attribute name
	 * that contains time of creation of document
	 */
	public $yearExpression;
	/**
	 * @var string the prefix used as first part of document no
	 */
	public $prefix;
	/**
	 * @var string the sprintf format for number used as third part of document no. Defaults to '%05d'
	 */
	public $format = '%05d';
	/**
	 * @var string the separation mark between different parts. Defaults to '-'
	 */
	public $separator = '-';
	/**
	 * @var bool whether to set the attribute upon creation.
	 * Defaults to true.
	 */
	public $setOnCreate = true;

	public function events()
	{
		return array(
			'onBeforeValidate' => 'addDocNrValidators',
		);
	}

	/**
	 * Add token validators to owner model
	 */
	public function addDocNrValidators()
	{
		$owner = $this->getOwner();
		if ($owner->isNewRecord && $this->setOnCreate)
		{
			$list = $owner->getValidatorList();
			$list->add(CValidator::createValidator(
					'setDocNrAttribute',
					$this,
					$this->attributeName
				));
			$list->add(CValidator::createValidator(
					'validateUniqueDocNr',
					$this,
					$this->attributeName
				));
		}
	}

	/**
	 * Set token attribute to random ASCII string
	 */
	public function setDocNrAttribute()
	{
		$owner = $this->getOwner();
		$owner->setAttribute($this->attributeName, $this->getDocumentNumber());
	}

	/**
	 * Validate token uniqueness
	 */
	public function validateUniqueDocNr()
	{
		$owner = $this->getOwner();
		CValidator::createValidator('unique', $owner, $this->attributeName)
			->validate($owner, $this->attributeName);
	}

	/**
	 * @return string document number
	 */
	public function getDocumentNumber()
	{
		$owner=$this->getOwner();

		$docNo=array();
		$docNo[]=$this->prefix . date('Y');
		if($this->groupByAttribute)
			$docNo[]=$owner->{$this->groupByAttribute};
		$docNo[]=sprintf($this->format, $this->getNumber());

		return implode($this->separator, $docNo);
	}

	/**
	 * @return integer document number last part
	 */
	public function getNumber()
	{
		$owner=$this->getOwner();

		$criteria=new CDbCriteria();
		$criteria->select="MAX($this->attributeName)";
		$criteria->compare($this->yearExpression, date('Y'));
		if($this->groupByAttribute)
			$criteria->compare($this->groupByAttribute, $owner->{$this->groupByAttribute});

		$builder=$owner->getCommandBuilder();
		$max=$builder->createFindCommand($owner->tableName(), $criteria)->queryScalar();
		if($max)
			return (int)substr(strrchr($max, $this->separator), 1) + 1;
		else
			return 1;
	}
}