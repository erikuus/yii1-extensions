<?php
/**
 * XFindColumnBehavior class file.
 *
 * This behavior adds findColumn methods to a model
 *
 * This behavior can be attached to a model on its behaviors() method:
 * <pre>
 * public function behaviors()
 * {
 *     return array(
 *         'FindColumnBehavior' => array(
 *             'class' => 'ext.behaviors.XFindColumnBehavior',
 *         ),
 *     );
 * }
 * </pre>
 *
 * Example:
 *
 * $emails=User::model()->findColumn('email', 'active = 1');
 * foreach ($emails as $email) {
 *     echo $email;
 * }
 *
 * NOTE! beforeFind and afterFind functions will be ignored.
 *
 * @author Rajat Singhal <rajat.developer.singhal@gmail.com>
 * @link http://www.yiiframework.com/
 * @version 0.0.1
 */
class XFindColumnBehavior extends CActiveRecordBehavior
{
	/**
	 * Find column
	 * @param string $column column name
	 * @param mixed $condition
	 * @param array $params
	 * @return array column values
	 */
    public function findColumn($column, $condition='', $params=array())
    {
        $criteria = $this->owner->getCommandBuilder()->createCriteria($condition,$params);
        $criteria->select = $column;
        $this->owner->applyScopes($criteria);
        $command = $this->owner->getCommandBuilder()->createFindCommand($this->owner->getTableSchema(),$criteria);
        if(empty($criteria->with))
            return $command->queryColumn();
        else
        {
            $finder=new CActiveFinder($this->owner, $criteria->with);
            $results = $finder->query($criteria, true);
            $data = array();
            foreach($results as $result)
                $data[] = $result[$column];
            return $data;
        }
    }
}
