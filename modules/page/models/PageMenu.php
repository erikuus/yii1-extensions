<?php

/**
 * This is the model class for table "vau.tbl_page_menu".
 */
class PageMenu extends CActiveRecord
{
	/**
	 * The followings are the available columns in table 'vau.tbl_page_menu':
	 * @property integer $id
	 * @property integer $position
	 * @property integer $type
	 * @property string $lang
	 * @property string $title
	 * @property string $content
	 * @property string $url
	 * @property boolean $deleted
	 */

	const TYPE_LABEL=0;
	const TYPE_CONTENT=1;
	const TYPE_URL=2;

	/**
	 * Returns the database connection used by active record.
	 * @return CDbConnection the database connection used by active record.
	 */
	public function getDbConnection()
	{
		return isset(Yii::app()->pagedb) ? Yii::app()->pagedb : Yii::app()->db;
	}

	/**
	 * Returns the static model of the specified AR class.
	 * @return PageMenu the static model class
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
		return Yii::app()->getModule('page')->menuTableName;
	}

	/**
	 * @return default scope
	 */
	public function defaultScope()
	{
		return array(
			'condition'=>'t.lang=:lang',
			'params'=>array(
				':lang'=>Yii::app()->language,
			),
		);
	}

	/**
	 * @return scopes
	 */
	public function scopes()
	{
		return array(
			'active'=>array(
				'condition'=>'deleted IS FALSE'
			),
			'activeItem'=>array(
				'condition'=>'deleted IS FALSE AND type='.self::TYPE_CONTENT
			),
		);
	}

	/**
	 * @return behaviors
	 */
	public function behaviors()
	{
		return array(
			'ReorderBehavior' => array(
				'class'=>'ext.behaviors.XReorderBehavior',
				'sort'=>'position',
			),
		);
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('title, type', 'required'),
			array('url', 'ext.validators.XCompareRequiredValidator', 'compareAttribute'=>'type', 'compareValue'=>self::TYPE_URL),
			array('url', 'url'),
			array('position, type', 'numerical', 'integerOnly'=>true),
			array('type', 'in', 'range'=>array(self::TYPE_LABEL, self::TYPE_CONTENT, self::TYPE_URL)),
			array('lang', 'length', 'max'=>5),
			array('title, url', 'length', 'max'=>256),
			array('deleted', 'boolean'),
			array('content', 'safe'),
			// defaults
			array('type', 'default', 'value'=>self::TYPE_LABEL),
			array('deleted', 'default', 'value'=>0),
			// filters
			array('title', 'filter', 'filter'=>'strip_tags'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'articles' => array(self::HAS_MANY, 'PageArticle', 'menu_id','order'=>'"articles".position'),
			'articleCount' => array(self::STAT, 'PageArticle', 'menu_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'type' => Yii::t('PageModule.md', 'Type'),
			'title' => Yii::t('PageModule.md', 'Title'),
			'content' => Yii::t('PageModule.md', 'Content'),
			'url' => Yii::t('PageModule.md', 'Url'),
		);
	}

	/**
	 * @return CActiveDataProvider the data provider
	 */
	public function getDataProvider()
	{
		$criteria=new CDbCriteria(array(
			'scopes'=>'active',
			'with'=>'articleCount'
		));

		return new CActiveDataProvider(get_class($this), array(
			'criteria'=>$criteria,
			'pagination'=>false,
			'sort'=>array(
				'defaultOrder'=>'position',
			),
		));
	}

	/**
	 * @return integer first menu item id
	 */
	public function getFirstItemId()
	{
		$model=$this->activeItem()->find(array(
			'order'=>'position',
			'limit'=>1
		));
		return $model!==null ? $model->id : null;
	}

	/**
	 * @return array menu type options
	 */
	public function getTypeOptions()
	{
		return array(
			self::TYPE_LABEL => Yii::t('PageModule.md', 'Label'),
			self::TYPE_CONTENT => Yii::t('PageModule.md', 'Content'),
			self::TYPE_URL => Yii::t('PageModule.md', 'Url'),
		);
	}


	/**
	 * @return array status options
	 */
	public function getTypeCssClassOptions()
	{
		return array(
			self::TYPE_LABEL =>'type-header',
			self::TYPE_CONTENT =>'type-item',
			self::TYPE_URL =>'type-link',
		);
	}

	/**
	 * @return string status based css class
	 */
	public function getTypeCssClassName()
	{
		$options=$this->typeCssClassOptions;
		return isset($options[$this->type]) ? $options[$this->type] : null;
	}

	/**
	 * @return array list of active menu items for dropdown (attr1 => attr2)
	 */
	public function getActiveItemOptions()
	{
		return CHtml::listData($this->activeItem()->findAll(array(
			'order'=>'position'
		)),'id','title');
	}

	/**
	 * @return string html formatted menu items
	 */
	public function getFormattedItem()
	{
		switch ($this->type) {
			case self::TYPE_CONTENT:
				return CHtml::link(CHtml::encode($this->title), array('/page/article/index','menuId'=>$this->id));
			break;
			case self::TYPE_URL:
				return CHtml::link(CHtml::encode($this->title), $this->url);
			break;
			default:
				return CHtml::encode($this->title);
			break;
		}
	}

	/**
	 * @return string product couters (active / visible)
	 */
	public function getArticleCounter()
	{
		return $this->type==self::TYPE_CONTENT ? $this->articleCount : null;
	}

	/**
	 * Prepares attributes before performing validation.
	 */
	protected function beforeValidate()
	{
		parent::beforeValidate();

		if($this->type!=self::TYPE_CONTENT)
			$this->content=null;

		if($this->type!=self::TYPE_URL)
			$this->url=null;

		return true;
	}

	/**
	 * This is invoked before the record is saved.
	 * @return boolean whether the record should be saved.
	 */
	protected function beforeSave()
	{
		if(parent::beforeSave())
		{
			if($this->isNewRecord)
				$this->lang=Yii::app()->language;

			$purifier = new CHtmlPurifier();
			$this->content=$purifier->purify($this->content);

			return true;
		}
		else
			return false;
	}
}