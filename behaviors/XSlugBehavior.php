<?php
/**
 * XSlugBehavior class
 *
 * This behavior enables to create slugs and parse URLs that expect those slugs to get the unique id for model
 *
 * This behavior can be attached to a model on its behaviors() method:
 * <pre>
 * public function behaviors()
 * {
 *     return array(
 *         'slug' => array(
 *             'class' => 'ext.behaviors.XSlugBehavior',
 *             'sourceStringAttr' => 'title',
 *         ),
 *     );
 * }
 * </pre>
 *
 * The following will generat the slug for you:
 * <pre>
 * $model->generateUniqueSlug()
 * </pre>
 *
 * Get id of article from slug
 * <pre>
 * $dummy = MyModel::model();
 * $id = $dummy->getIdFromSlug($slug_fetched_from_url);
 * $my_model_obj = MyModel::model()->findByPk($id);
 * </pre>
 *
 * Original version
 * @link http://www.yiiframework.com/extension/pcsimpleslugbehavior
 * @author Boaz Rymland
 * @version 1.0.0
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */
class XSlugBehavior extends CActiveRecordBehavior
{
	/**
	 * @var string the attribute that contains the 'main string' that is used when building the slug
	 */
	public $sourceStringAttr = "title";
	/**
	 * @var string the attribute that contains the 'main string' that is used when building the slug
	 */
	public $sourceStringPrepareMethod = false;
	/**
	 * @var string the attribute/column name that holds the Id/primary-key for this model.
	 */
	public $sourceIdAttr = 'id';
	/**
	 * @var boolean Supports avoiding prefixing the 'id' attribute of the model in the beginning of the slug. Use
	 * with caution(!) as this is tricky can can lead to two record with the same slug (consider carefully
	 * your requirements before setting this to true).
	 */
	public $avoidIdPrefixing = false;
	/**
	 * @var integer maximum allowed slug length. slug will be crudely trimmed to this length if longer than it.
	 */
	public $maxChars = 100;
	/**
	 * @var boolean whether to lowercase the resulted URLs or not. default = yes.
	 */
	public $lowercaseUrl = true;
	/**
	 * @var array CDbCriteria default scope criteria.
	 */
	public $scope = array();

	private $_slug;
	private $_ids;

	/**
	 * @return string: the prepared slug for 'this->owner' model object
	 * @throws CException
	 */
	public function generateUniqueSlug()
	{
		if (!$this->avoidIdPrefixing)
		{
			// check that the defined 'id attribute' exists for 'this' model. explode if not.
			if (!$this->owner->hasAttribute($this->sourceIdAttr))
			{
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceIdAttr .
						" from which I'm supposed to create the slug. Don't know how to continue. Please help!"
				);
			}
		}
		// if we're supposed to get the slug raw material from a method, check it exists and if so run it.
		if ($this->sourceStringPrepareMethod)
		{
			if (method_exists($this->owner, $this->sourceStringPrepareMethod))
				$this->_slug = $this->createBaseSlug($this->owner->{$this->sourceStringPrepareMethod}());
			else
			{
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have the method that was supposed to return the string for the slug (method name={$this->sourceStringPrepareMethod})." .
						" Don't know how to continue. Please fix it!"
				);
			}

		}
		// no preparation method - check that the defined 'source string attribute' exists for 'this' model. explode if not.
		else
		{
			if (!$this->owner->hasAttribute($this->sourceStringAttr))
			{
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceStringAttr .
						" from which I'm supposed to create the slug. Don't know how to continue. Please fix it!"
				);
			}
			// create the base slug out of this attribute:
			// convert all spaces to underscores:
			$this->_slug = $this->createBaseSlug($this->owner->{$this->sourceStringAttr});
		}

		// prepend everything with the id of the model followed by a dash
		if (!$this->avoidIdPrefixing)
		{
			$id_attr = $this->sourceIdAttr;
			$this->_slug = $this->owner->$id_attr . "-" . $this->_slug;
		}

		// trim if necessary:
		if (mb_strlen($this->_slug) > $this->maxChars)
			$this->_slug = mb_substr($this->_slug, 0, $this->maxChars);

		// lowercase url if needed to
		if ($this->lowercaseUrl)
			$this->_slug = mb_strtolower($this->_slug, 'UTF-8');

		// done
		return $this->_slug;
	}

	/**
	 * Returns 'treated' string with special characters stripped off of it, spaces turned to dashes. It serves as a 'base
	 * slug' that can be further treated (and used internally by generateUniqueSlug()).
	 *
	 * It is useful when you need to add misc parameters to URLs and want them 'treated' (as 'treated' is performed here)
	 * but those string are irrelevant to Id of a model etc. E.g.: Create a URL in the format of "/.../<city-name>/..."
	 * - the city-name parameter was required to be 'treated' before applying to URL.
	 *
	 * @param string $str source string
	 * @return string resulted string after manipulation.
	 */
	public function createBaseSlug($str)
	{
		// convert all spaces to underscores:
		$treated = strtr($str, " ", "_");
		// convert what's needed to convert to nothing (remove them...)
		$treated = preg_replace('/[\!\@\#\$\%\^\&\*\(\)\+\=\~\:\.\,\;\'\"\<\>\/\\\`]/', "", $treated);
		// convert underscores to dashes
		$treated = strtr($treated, "_", "-");

		if ($this->lowercaseUrl)
			$treated = mb_strtolower($treated, 'UTF-8');

		return $treated;
	}

	/**
	 * Returns the Id (=primary key) from a given slug
	 *
	 * @param string $slug
	 * @param bool $id_prefix if an id prefix is expected in the slug or not.
	 * @return int
	 */
	public function getIdFromSlug($slug, $id_prefix = true)
	{
		$parts = explode("-", $slug);

		if ($id_prefix)
			return $parts[0];
		else
		{
			$ids = $this->getIds($parts);
			switch (count($ids))
			{
				case 0:
				{
					// no such record found! Wierd and probably indicate a problem with selecting id-less slugs in the first
					// place. log this incident
					Yii::log("Error: parsing slug has resulted in NO record found. Model class: " . get_class($this->owner)
							. ", slug: $slug, id_prefix? " . ($id_prefix) ? " => yes, id prefixed." : " => no, no id prefixing.",
						CLogger::LEVEL_ERROR, __METHOD__);
					return false;
					break;
				}
				case 1:
				{
					return $ids[0];
					break;
				}
				default:
				{
					/*
					 * more than 1? This means you have more than 1 record with a title like the one we got a slug for.
					 * this probably means that choosing id-less slug wasn't a good design decision... .
					 */
					$ids_concat = '';
					foreach ($ids as $record_id_arr) {
						$ids_concat .= $record_id_arr['id'] . ",";
					}
					$ids_concat = trim($ids_concat, ",");
					Yii::log("Error: more than matched record for given slug! Returning just one (no particular order). Model" .
						" class: " . get_class($this->owner) . ", slug: '" . $slug . "', (id prefixing=false). Got these IDs: " .
						$ids_concat, CLogger::LEVEL_ERROR, __METHOD__);
					return $ids[0];
				}
			}
		}
	}

	/**
	 * @param array $slugParts the exploded parts of slug
	 * @return array ids for a given slug
	 */
	protected function getIds($slugParts)
	{
		if($this->_ids===null)
		{
			$builder = $this->getOwner()->dbConnection->getCommandBuilder();

			$findCriteria = new CDbCriteria(array(
				'select' => 't.'.$this->sourceIdAttr,
				'params' => array(
					':slug' => '%'.implode('%', $slugParts).'%'
				),
			));

			$findCriteria->addCondition("LOWER(t.$this->sourceStringAttr) LIKE :slug");

			if(is_array($this->scope) && !empty($this->scope))
			{
				$scopeCriteria = new CDbCriteria($this->scope);
				$findCriteria->mergeWith($scopeCriteria);
			}

			$this->_ids = $builder->createFindCommand(
				$this->owner->tableName(),
				$findCriteria
			)->queryColumn();
		}
		return $this->_ids;
	}
}
