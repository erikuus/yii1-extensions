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
 * Note! Original name of this extension is "PcSimpleSlugBehavior"
 *
 * @link http://www.yiiframework.com/extension/pcsimpleslugbehavior
 * @license:
 * Copyright (c) 2012, Boaz Rymland
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 *      disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
 *      disclaimer in the documentation and/or other materials provided with the distribution.
 * - The names of the contributors may not be used to endorse or promote products derived from this software without
 *      specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class XSlugBehavior extends CActiveRecordBehavior {
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
	 * @var bool Supports avoiding prefixing the 'id' attribute of the model in the beginning of the slug. Use
	 * with caution(!) as this is tricky can can lead to two record with the same slug (consider carefully
	 * your requirements before setting this to true).
	 */
	public $avoidIdPrefixing = false;

	/**
	 * @var int maximum allowed slug length. slug will be crudely trimmed to this length if longer than it.
	 */
	public $maxChars = 100;

	/**
	 * @var bool whether to lowercase the resulted URLs or not. default = yes.
	 */
	public $lowercaseUrl = true;
	/**
	 * @var string the slug.
	 */
	private $slug;

	/**
	 * @return string: the prepared slug for 'this->owner' model object
	 * @throws CException
	 */
	public function generateUniqueSlug() {
		if (!$this->avoidIdPrefixing) {
			// check that the defined 'id attribute' exists for 'this' model. explode if not.
			if (!$this->owner->hasAttribute($this->sourceIdAttr)) {
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceIdAttr .
						" from which I'm supposed to create the slug. Don't know how to continue. Please help!"
				);
			}
		}
		// if we're supposed to get the slug raw material from a method, check it exists and if so run it.
		if ($this->sourceStringPrepareMethod) {
			if (method_exists($this->owner, $this->sourceStringPrepareMethod)) {
				$this->slug = $this->createBaseSlug($this->owner->{$this->sourceStringPrepareMethod}());
			}
			else {
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have the method that was supposed to return the string for the slug (method name={$this->sourceStringPrepareMethod})." .
						" Don't know how to continue. Please fix it!"
				);
			}

		}
		// no preparation method - check that the defined 'source string attribute' exists for 'this' model. explode if not.
		else {
			if (!$this->owner->hasAttribute($this->sourceStringAttr)) {
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceStringAttr .
						" from which I'm supposed to create the slug. Don't know how to continue. Please fix it!"
				);
			}
			// create the base slug out of this attribute:
			// convert all spaces to underscores:
			$this->slug = $this->createBaseSlug($this->owner->{$this->sourceStringAttr});
		}

		// prepend everything with the id of the model followed by a dash
		if (!$this->avoidIdPrefixing) {
			$id_attr = $this->sourceIdAttr;
			$this->slug = $this->owner->$id_attr . "-" . $this->slug;
		}

		// trim if necessary:
		if (mb_strlen($this->slug) > $this->maxChars) {
			$this->slug = mb_substr($this->slug, 0, $this->maxChars);
		}

		// lowercase url if needed to
		if ($this->lowercaseUrl) {
			$this->slug = mb_strtolower($this->slug, 'UTF-8');
		}

		// done
		return $this->slug;
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
	public function createBaseSlug($str) {
		// convert all spaces to underscores:
		$treated = strtr($str, " ", "_");
		// convert what's needed to convert to nothing (remove them...)
		$treated = preg_replace('/[\!\@\#\$\%\^\&\*\(\)\+\=\~\:\.\,\;\'\"\<\>\/\\\`]/', "", $treated);
		// convert underscores to dashes
		$treated = strtr($treated, "_", "-");

		if ($this->lowercaseUrl) {
			$treated = mb_strtolower($treated, 'UTF-8');
		}

		return $treated;
	}

	/**
	 * Returns the Id (=primary key) from a given slug
	 *
	 * @param string $slug
	 * @param bool $id_prefix if an id prefix is expected in the slug or not.
	 * @return int
	 */
	public function getIdFromSlug($slug, $id_prefix = true) {
		$parts = explode("-", $slug);
		if ($id_prefix) {
			return $parts[0];
		}
		else {
			// prepare the 'like' query condition
			$like_construct = implode("%", $parts);
			$like_construct = '%' . $like_construct . '%';
			$ids = Yii::app()->db->createCommand()
				->select('id')
				->from($this->owner->tableName())
				->where(array('like', $this->sourceStringAttr, $like_construct))
				->queryAll();
			switch (count($ids)) {
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
					return $ids[0]['id'];
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
					return $ids[0]['id'];
					}
			}
		}
	}
}
