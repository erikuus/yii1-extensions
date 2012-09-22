<?php
/**
 * XTreeNBehavior behavior
 *
 * This is nestedset version of XTreeBehavior
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTreeNBehavior extends CActiveRecordBehavior
{
	/**
	 * @var string the attribute name of tree node id
	 */
	public $id='id';
	/**
	 * @var string the attribute name of tree node parent id
	 */
	public $parent_id='parent_id';
	/**
	 * @var string the attribute name of tree node label
	 */
	public $label='label';
	/**
	 * @var string the attribute name to order tree nodes by
	 */
	public $sort='id';
	/**
	 * @var mixed the with method parameter of owner model
	 */
	public $with=array();
	/**
	 * @var string the name of nested tree left attribute
	 */
	public $left='lft';
	/**
	 * @var string the name of nested tree right attribute
	 */
	public $right='rgt';
	/**
	 * @var string the name of owner model method to format path label
	 */
	public $pathLabelMethod=null;
	/**
	 * @var string the name of owner model method to format breadcrumbs label
	 */
	public $breadcrumbsLabelMethod=null;
	/**
	 * @var string the name of owner model method to format breadcrumbs url
	 */
	public $breadcrumbsUrlMethod=null;
	/**
	 * @var string the name of owner model method to format menu label
	 */
	public $menuLabelMethod=null;
	/**
	 * @var string the name of owner model method to format menu url
	 */
	public $menuUrlMethod=null;
	/**
	 * @var string the name of owner model method to format tree label
	 */
	public $treeLabelMethod=null;
	/**
	 * @var string the name of owner model method to format tree url
	 */
	public $treeUrlMethod=null;

	/**
	 * Recursively set right and left values to rebuild tree into nested set
	 * @param int $parent_id the id of the parent tree node
	 * @param int $left the nested set left value of tree node
	 */
	public function rebuildTree($parent_id=0, $left=0)
	{
		$owner=$this->getOwner();

		$right = $left+1;
		// get all children of this node
		$tableName=$owner->tableName();
		$rows=$owner->getDbConnection()->createCommand("SELECT \"{$this->id}\" FROM \"{$tableName}\" WHERE \"{$this->parent_id}\"={$parent_id}")->queryColumn();
		foreach ($rows as $id)
		{
			// recursive execution of this function for each child of this node
			// $right is the current right value, which is incremented by the function
			$right = $this->rebuildTree($id, $right);
		}
		// we've got the left value, and now that we've processed
		// the children of this node we also know the right value
		$owner->getDbConnection()->createCommand("UPDATE \"{$tableName}\" SET \"{$this->left}\"={$left},\"{$this->right}\"={$right} WHERE \"{$this->id}\"=$parent_id")->execute();
		// return the right value of this node + 1
		return $right + 1;
	}

	/**
	 * @return int number of rows with no left or right values
	 */
	public function countTreeErrors()
	{
		$owner=$this->getOwner();
		$tableName=$owner->tableName();
		$db=$owner->getDbConnection();
		return $db->createCommand("SELECT COUNT(*) FROM \"{$tableName}\" WHERE \"{$this->left}\" IS NULL OR \"{$this->right}\" IS NULL")->queryScalar();
	}

	/**
	 * @return int id of the absolute root node
	 */
	public function getRootId()
	{
		$owner=$this->getOwner();
		$root=$owner->find($this->parent_id.'=0');
		return $root->id;
	}

	/**
	 * @param bool $showRoot wether the absolute root node should be returned
	 * @return array of parent models
	 */
	public function getParents($showRoot=true)
	{
		$owner=$this->getOwner();
		$criteria=new CDbCriteria;
		$criteria->order=$this->left." ASC";
		$criteria->condition="$this->left < '{$owner->getAttribute($this->left)}' AND $this->right > '{$owner->getAttribute($this->right)}'";
		if ($showRoot===false)
			$criteria->addCondition("$this->parent_id <> 0");
		return $owner->findAll($criteria);
	}

	/**
	 * @param bool $showRoot wether the relative root node should be displayed
	 * @return array of all children models
	 */
	public function getAllChildren($showRoot=true)
	{
		$owner=$this->getOwner();
		$criteria=new CDbCriteria;
		$criteria->order=$this->left." ASC";
		if ($showRoot===true)
			$criteria->condition="$this->left >= '{$owner->getAttribute($this->left)}' AND $this->right <= '{$owner->getAttribute($this->right)}'";
		else
			$criteria->condition="$this->left > '{$owner->getAttribute($this->left)}' AND $this->right < '{$owner->getAttribute($this->right)}'";
		return $owner->findAll($criteria);
	}

	/**
	 * @return array ids of all cildren of the given node
	 */
	public function getAllChildrenIds($showRoot=true)
	{
		$owner=$this->getOwner();
		$tableName=$owner->tableName();
		if ($showRoot===true)
			$condition="$this->left >= '{$owner->getAttribute($this->left)}' AND $this->right <= '{$owner->getAttribute($this->right)}'";
		else
			$condition="$this->left > '{$owner->getAttribute($this->left)}' AND $this->right < '{$owner->getAttribute($this->right)}'";
		return $owner->getDbConnection()->createCommand("SELECT id FROM $tableName WHERE $condition")->queryColumn();
	}

	/**
	 * @param int $id the id of the tree node
	 * @param bool $showRoot wether the root node should be displayed
	 * @param bool $showRoot wether the current node should be displayed
	 * @return sting of path
	 */
	public function getPathText($id=null,$showRoot=true,$showNode=true)
	{
		$owner=$this->getOwner();
		$childId=($id===null) ? $owner->getAttribute($this->id) : $id;
		$model=$owner->findByPk($childId);
		if($model===null)
			return null;
		$items=array();
		foreach($model->getParents($showRoot) as $parent)
			$items[]=$this->formatPathText($parent);
		if($showNode===true)
			$items[]=$this->formatPathText($model);
		return implode(' / ', $items);
	}

	/**
	 * @param int $id the id of the tree node
	 * @param bool $showRoot wether the root node should be displayed
	 * @return string of breadcrumbs
	 */
	public function getBreadcrumbs($id=null,$showRoot=true)
	{
		$owner=$this->getOwner();
		$childId=($id===null) ? $owner->getAttribute($this->id) : $id;
		$model=$owner->findByPk($childId);
		if($model===null)
			return null;
		$items=array();
		foreach($model->getParents($showRoot) as $parent)
			$items[]=$this->formatBreadcrumbsLink($parent);
		if($items!==array())
			$items[]=$this->formatBreadcrumbsLabel($model);
		return implode(' &raquo; ', $items);
	}

	/**
	 * @param int $id the id of the relative root node
	 * @param bool $showRoot wether the relative root node should be displayed
	 * @return array of children nodes for CTreeView widget in Ajax mode
	 */
	public function fillTree($id=null, $showRoot=true)
	{
		$owner=$this->getOwner();
		$rootId=($id===null) ? $this->getRootId() : $id;
		$items=array();
		if ($showRoot===false)
		{
			$models=$owner->with($this->getWidth())->findAll(array(
				'condition'=>$this->parent_id.'=:id',
				'params'=>array(':id'=>$rootId),
				'order'=>$this->sort,
			));
			if($models===null)
				throw new CException('The requested tree does not exist.');
			foreach($models as $model)
				$items[]=$this->formatTreeItem($model);
		}
		else
		{
			$model=$owner->with($this->getWidth())->findByPk($rootId);
			if($model===null)
				throw new CException('The requested tree does not exist.');
			$items[]=$this->formatTreeItem($model);
		}
		return $items;
	}

	/**
	 * @param model the instance of ActiveRecord
	 * @return string label for path text
	 */
	protected function formatPathText($model)
	{
		if($this->pathLabelMethod!==null)
			$label=$model->{$this->pathLabelMethod}();
		else
			$label=$model->getAttribute($this->label);

		return $label;
	}

	/**
	 * @param model the instance of ActiveRecord
	 * @return string link for CBreadcrumbs widget
	 */
	protected function formatBreadcrumbsLink($model)
	{
		$label=$this->formatBreadcrumbsLabel($model);
		if($this->breadcrumbsUrlMethod!==null)
			$url=$model->{$this->breadcrumbsUrlMethod}();
		else
			$url=array('', 'id'=>$model->getAttribute($this->id));

		return CHtml::link(CHtml::encode($label), $url);
	}

	/**
	 * @param model the instance of ActiveRecord
	 * @return string label for CBreadcrumbs widget
	 */
	protected function formatBreadcrumbsLabel($model)
	{
		if($this->breadcrumbsLabelMethod!==null)
			$label=$model->{$this->breadcrumbsLabelMethod}();
		else
			$label=$model->getAttribute($this->label);

		return $label;
	}

	/**
	 * @param model the instance of ActiveRecord
	 * @return string url for nested list menu
	 */
	protected function formatMenuUrl($model)
	{
		if($this->menuUrlMethod!==null)
			$url=$model->{$this->menuUrlMethod}();
		else
			$url=array('', 'id'=>$model->getAttribute($this->id));

		return CHtml::normalizeUrl($url);
	}

	/**
	 * @param model the instance of ActiveRecord
	 * @return string label for nested list menu
	 */
	protected function formatMenuLabel($model)
	{
		if($this->menuLabelMethod!==null)
			return $model->{$this->menuLabelMethod}();
		else
			return $model->getAttribute($this->label);
	}

	/**
	 * @param model the instance of ActiveRecord
	 * @return array of tree item formatted for CTreeview widget
	 */
	protected function formatTreeItem($model)
	{
		if($this->treeLabelMethod!==null)
			$label=$model->{$this->treeLabelMethod}();
		else
			$label=$model->getAttribute($this->label);

		if($this->treeUrlMethod!==null)
			$url=$model->{$this->treeUrlMethod}();
		else
			$url='#';

		return array(
			'text'=>CHtml::link($label, $url, array('id'=>$model->getAttribute($this->id))),
			'id'=>$model->getAttribute($this->id),
			'hasChildren'=>$model->childCount==0 ? false : true,
		);
	}

	/**
	 * @return mixed with method parameters
	 */
	protected function getWidth()
	{
		return $this->with===array() ? 'childCount' : CMap::mergeArray(array('childCount'),$this->with);
	}
}