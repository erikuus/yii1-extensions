<?php

/**
 * This is the model class for table "vau.tbl_page_article".
 */
class PageArticle extends CActiveRecord
{
	/**
	 * The followings are the available columns in table 'vau.tbl_page_article':
	 * @property integer $id
	 * @property integer $menu_id
	 * @property integer $position
	 * @property string $lang
	 * @property string $title
	 * @property string $content
	 */

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
	 * @return PageArticle the static model class
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
		return Yii::app()->getModule('page')->articleTableName;
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
				'with'=>'menu',
				'condition'=>'menu.deleted IS FALSE'
			)
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
				'groupId'=>'menu_id',
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
			array('menu_id, title, content','required'),
			array('menu_id, position', 'numerical', 'integerOnly'=>true),
			array('lang', 'length', 'max'=>5),
			array('title', 'length', 'max'=>256),
			array('menu_id, title', 'safe', 'on'=>'search'),
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
			'menu' => array(self::BELONGS_TO, 'PageMenu', 'menu_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'menu_id' => Yii::t('PageModule.md', 'Menu'),
			'title' => Yii::t('PageModule.md', 'Title'),
			'content' => Yii::t('PageModule.md', 'Content'),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria(array(
			'scopes'=>'active',
		));

		if($this->menu_id)
			$criteria->addColumnCondition(array('t.menu_id'=>$this->menu_id));

		if($this->title)
			$criteria->addSearchCondition('LOWER(t.title)', mb_strtolower($this->title));

		return new CActiveDataProvider(get_class($this), array(
			'criteria'=>$criteria,
			'pagination'=>false,
			'sort'=>array(
				'defaultOrder'=>'menu.position, t.position',
			),
		));
	}

	/**
	 * Check if reorder column can be visible
	 * @return boolean reorder column visibility
	 */
	public function getReorderVisibility()
	{
		return $this->title==null ? true : false;
	}

	/**
	 * @return string title html link
	 */
	public function getTitleLink()
	{
		$url=Yii::app()->controller->createUrl('article/index',array('menuId'=>$this->menu_id,'#'=>'article'.$this->id));
		return CHtml::link(CHtml::encode($this->title), $url);
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