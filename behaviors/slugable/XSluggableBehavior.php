<?php
/**
 * Sluggable Behavior for Yii Framework.
 *
 * With this behavior, you can generate an URI for a single or combination of
 * columns in your table. Some call it permalink, others call it slug or
 * human readable url
 *
 * Demo configuration of this behavior for your model:
 * <pre>
 *     public function behaviors(){
 *         return array(
 *             'sluggable' => array(
 *                 'class'=>'ext.behaviors.sluggable.XSluggableBehavior',
 *                 'columns' => array('category', 'title', 'author.name'),
 *                 'unique' => true,
 *                 'update' => true,
 *             ),
 *         );
 *     }
 * </pre>
 *
 * @author Florian Fackler <florian.fackler@mintao.com>
 * @link http://mintao.com/
 * @copyright Copyright &copy; 2009 Mintao GmbH & Co. KG
 * @license MIT
 * @version $Id: SluggableBehavior.php 530 2011-04-30 23:31:12Z florian.fackler $
 * @package components
 */
include __DIR__ . '/Doctrine_Inflector.php';

class XSluggableBehavior extends CActiveRecordBehavior
{
	/**
	 * @var array Column name(s) to build a slug
	 */
	public $columns = array();

	/**
	 * Wether the slug should be unique or not.
	 * If set to true, a number is added
	 *
	 * @var bool
	 */
	public $unique = true;

	/**
	 * Update the slug every time the row is updated?
	 *
	 * @var bool $update
	 */
	public $update = true;

	/**
	 * Default columns to build slug if none given
	 *
	 * @var array Columns
	 */
	protected $_defaultColumnsToCheck = array('name', 'title');

	public function beforeSave($event)
	{
		// Slug already created and no updated needed
		if (true !== $this->update && ! empty($this->owner->slug)) {
			Yii::trace(
				'Slug found - no update needed.',
				__CLASS__ . '::' . __FUNCTION__
			);
			return parent::beforeSave($event);
		}

		if (! is_array($this->columns)) {
			Yii::trace(
				'Columns are not defined as array',
				__CLASS__ . '::' . __FUNCTION__
			);
			throw new CException('Columns have to be in array format.');
		}

		$availableColumns = array_keys($this->owner->tableSchema->columns);

		// Try to guess the right columns
		if (0 === count($this->columns)) {
			$this->columns = array_intersect(
				$this->_defaultColumnsToCheck,
				$availableColumns
			);
		} else {
			// Unknown columns on board?
			foreach ($this->columns as $col) {
				if (! in_array($col, $availableColumns)) {
					if (false !== strpos($col, '.')) {
						Yii::trace(
							'Dependencies to related models found',
							__CLASS__.'::'.__FUNCTION__
						);
						list($model, $attribute) = explode('.', $col);
						$externalColumns = array_keys(
							$this->getOwner()->$model->tableSchema->columns
						);
						if (! in_array($attribute, $externalColumns)) {
							throw new CException(
								"Model $model does not haz $attribute"
							);
						}
					} else {
						throw new CException(
							'Unable to build slug, column '.$col.' not found.'
						);
					}
				}
			}
		}

		// No columns to build a slug?
		if (0 === count($this->columns)) {
			throw new CException(
				'You must define "columns" to your sluggable behavior.'
			);
		}

		// Fetch values
		$values = array();
		foreach ($this->columns as $col) {
			if (false === strpos($col, '.')) {
			   $values[] = $this->owner->$col;
			} else {
				list($model, $attribute) = explode('.', $col);
				$values[] = $this->getOwner()->$model->$attribute;
			}
		}

		// First version of slug
		$slug = $checkslug = Doctrine_Inflector::urlize(
			implode('-', $values)
		);

		// Check if slug has to be unique
		if (false === $this->unique
			|| (! $this->owner->isNewRecord && $this->owner->slug === $slug)
		)
		{
			$this->owner->slug = $slug;
		} else {
			$counter = 0;
			while ($this->owner->findByAttributes(
				array('slug' => $checkslug))
			) {
				$checkslug = sprintf('%s-%d', $slug, ++$counter);
			}
			$this->owner->slug = $counter > 0 ? $checkslug : $slug;
		}
		return parent::beforeSave($event);
	}
}

