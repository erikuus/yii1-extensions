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
 *             'class' => 'ext.behaviors.sluggable.XSluggableBehavior',
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
 * Essentially rewritten version
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0.0
 */

include_once __DIR__ . '/Doctrine_Inflector.php';

class XSluggableBehavior extends CActiveRecordBehavior
{
	/**
	 * @var string the attribute that contains the 'main string' that is used when building the slug.
	 * Defaults to 'title'
	 */
	public $sourceStringAttr = 'title';
	/**
	 * @var string the attribute/column name that holds the Id/primary-key for this model.
	 * Defaults to 'id'
	 */
	public $sourceIdAttr = 'id';
	/**
	 * @var boolean whether prefixing the 'id' attribute of the model in the beginning of the slug.
	 * Use with caution as setting this to false can lead to two record with the same slug (consider carefully
	 * your requirements).
	 * Defaults to true
	 */
	public $slugIdPrefix = true;
	/**
	 * @var boolean whether to use Doctrine_Inflector class to build the slug.
	 * If Doctrine Inflector is set true, all special chars are
	 * replaced by standard a-z 0-9 chars.
	 * Defaults to false
	 */
	public $slugInflector = false;
	/**
	 * @var integer maximum allowed slug length. Slug will be crudely trimmed to this length if longer than it.
	 * Defaults to 100
	 */
	public $maxChars = 100;
	/**
	 * @var array CDbCriteria scope criteria that is merged with find slug criteria (if slugIdPrefix is set to false).
	 * For example, if you use soft delete and multilingual solution, you may want to
	 * define scope as follows:
	 * 'scope'=>array(
	 *     'condition'=>'t.lang=:lang AND deleted IS FALSE',
	 *     'params'=>array(
	 *         ':lang'=>yii::app()->language,
	 *     ),
	 * ),
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
		if ($this->slugIdPrefix)
		{
			// check that the defined 'id attribute' exists for 'this' model. explode if not.
			if (!$this->owner->hasAttribute($this->sourceIdAttr))
			{
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceIdAttr
				);
			}
		}
		else
		{
			// inflector can not be used when id prefix is not used
			if ($this->slugInflector)
			{
				throw new CException ("requested inlector to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but inflector can not be used when id prefix is not used"
				);
			}
		}

		if (!$this->owner->hasAttribute($this->sourceStringAttr))
		{
			throw new CException ("requested to prepare a slug for " .
					get_class($this->owner) .
					" (id=" . $this->owner->getPrimaryKey() .
					") but this model doesn't have an attribute named " . $this->sourceStringAttr
			);
		}

		// create the base slug out of this attribute:
		if($this->slugInflector)
			$this->_slug = Doctrine_Inflector::urlize($this->owner->{$this->sourceStringAttr});
		else
			$this->_slug = $this->createSimpleSlug($this->owner->{$this->sourceStringAttr});

		// prepend everything with the id of the model followed by a dash
		if ($this->slugIdPrefix)
		{
			$id_attr = $this->sourceIdAttr;
			$this->_slug = $this->owner->$id_attr . "-" . $this->_slug;
		}

		// trim if necessary:
		if (mb_strlen($this->_slug) > $this->maxChars)
			$this->_slug = mb_substr($this->_slug, 0, $this->maxChars);

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
	public function createSimpleSlug($str)
	{
		// convert all spaces to underscores:
		$str = strtr($str, array(' '=>'_'));
		// convert what's needed to convert to nothing (remove them...)
		$str = preg_replace('/[\?\!\@\#\$\%\^\&\*\(\)\+\=\~\:\.\,\;\'\"\<\>\/\\\`]/', "", $str);
		// convert underscores to dashes
		$str = strtr($str, "_", "-");
		// lowercase url
		$str = mb_strtolower($str, 'UTF-8');

		return $str;
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
					':slug' => implode('%', $slugParts).'%'
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
