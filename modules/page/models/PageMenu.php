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
	 * @property string $refcode
	 * @property boolean $deleted
	 */

	const TYPE_LABEL=0;
	const TYPE_CONTENT=1;
	const TYPE_HIDDEN_CONTENT=2;
	const TYPE_URL=3;

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
				'condition'=>'deleted IS FALSE AND type IN ('.self::TYPE_CONTENT.','.self::TYPE_HIDDEN_CONTENT.')',
			),
			'visible'=>array(
				'condition'=>'deleted IS FALSE AND type!='.self::TYPE_HIDDEN_CONTENT
			),
			'visibleItem'=>array(
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
			'SlugBehavior' => array(
				'class'=>'ext.behaviors.sluggable.XSluggableBehavior',
				'sourceStringAttr'=>'title',
				'slugIdPrefix' => Yii::app()->getModule('page')->slugIdPrefix,
				'slugInflector' => Yii::app()->getModule('page')->slugInflector,
				'scope'=>array(
					'condition'=>'t.lang=:lang AND deleted IS FALSE',
					'params'=>array(
						':lang'=>yii::app()->language,
					),
				),
			),
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
			array('lang', 'length', 'max'=>5),
			array('title, url', 'length', 'max'=>256),
			array('refcode', 'length', 'max'=>128),
			array('url', 'url'),
			array('position, type', 'numerical', 'integerOnly'=>true),
			array('deleted', 'boolean'),
			array('content', 'safe'),
			array('url', 'ext.validators.XCompareRequiredValidator', 'compareAttribute'=>'type', 'compareValue'=>self::TYPE_URL, 'allowEmpty'=>false),
			array('type', 'in', 'range'=>array(
				self::TYPE_LABEL,
				self::TYPE_CONTENT,
				self::TYPE_HIDDEN_CONTENT,
				self::TYPE_URL
			)),
			array('title', 'unique','on'=>'noSlugIdPrefix',
				'criteria'=>array(
					'condition'=>'t.lang=:lang AND t.deleted IS FALSE',
					'params'=>array(
						':lang'=>yii::app()->language,
					),
				),
			),
			// defaults
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
			'articles' => array(self::HAS_MANY, 'PageArticle', 'menu_id','order'=>'articles.position'),
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
		$model=$this->visibleItem()->find(array(
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
			self::TYPE_HIDDEN_CONTENT => Yii::t('PageModule.md', 'Hidden Content'),
			self::TYPE_URL => Yii::t('PageModule.md', 'Url'),
		);
	}


	/**
	 * @return array status options
	 */
	public function getTypeCssClassOptions()
	{
		return array(
			self::TYPE_LABEL =>'type-label',
			self::TYPE_CONTENT =>'type-content',
			self::TYPE_HIDDEN_CONTENT =>'type-hidden-content',
			self::TYPE_URL =>'type-url',
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
			case self::TYPE_HIDDEN_CONTENT:
				return CHtml::link(CHtml::encode($this->title), array('/page/article/index', 'topic'=>$this->SlugBehavior->generateUniqueSlug()));
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
	 * Prepares attributes before performing validation.
	 */
	protected function beforeValidate()
	{
		parent::beforeValidate();

		if($this->type!=self::TYPE_CONTENT && $this->type!=self::TYPE_HIDDEN_CONTENT)
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