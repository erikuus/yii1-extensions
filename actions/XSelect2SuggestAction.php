<?php
/**
 * XSelect2SuggestAction action
 *
 * This action returns options for XSelect2 widget
 *
 * The following shows how to use XSelect2SuggestAction action
 *
 * First set up suggestCountry action on RequestController actions() method:
 *
 * ~~~
 * public function actions()
 * {
 *     return array(
 *         'suggestCountry'=>array(
 *             'class'=>'ext.actions.XSelect2SuggestAction',
 *             'modelName'=>'Country',
 *             'methodName'=>'suggest',
 *         ),
 *     );
 * }
 * ~~~
 *
 * And then set up widget:
 *
 * ~~~
 * $this->widget('ext.widgets.select2.XSelect2', array(
 *     'model'=>$model,
 *     'attribute'=>'id',
 *     'options'=>array(
 *         'minimumInputLength'=>2,
 *         'ajax' => array(
 *             'url'=>$this->createUrl('/request/suggestPerson'),
 *             'dataType'=>'json',
 *             'data' => "js: function(term,page) {
 *                 return {q: term};
 *             }",
 *             'results' => "js: function(data,page){
 *                 return {results: data};
 *             }",
 *         ),
 *         ...
 *     ),
 * ));
 * ~~~
 *
 * Note, you also have to write model method that makes suggest query. For example:
 *
 * ~~~
 * public function suggest($keyword, $limit=20)
 * {
 *     $models=$this->findAll(array(
 *         'condition'=>'name LIKE :keyword',
 *         'params'=>array(':keyword'=>$keyword.'%'),
 *         'order'=>'name',
 *         'limit'=>$limit,
 *     ));
 *     $suggest=array();
 *     foreach($models as $model)
 *     {
 *         $suggest[] = array(
 *             'id'=>$model->id,
 *             'text'=>$model->name,
 *         );
 *     }
 *     return $suggest;
 * }
 * ~~~
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XSelect2SuggestAction extends CAction
{
	/**
	 * @var string name of the model class.
	 */
	public $modelName;
	/**
	 * @var string name of the method of model class that returns data.
	 */
	public $methodName;
	/**
	 * @var integer maximum number of rows to be returned
	 */
	public $limit=20;

	/**
	 * Suggests models based based on the current user input.
	 */
	public function run()
	{
		if(isset($_GET['q'])&&($keyword=trim($_GET['q']))!=='')
		{
			$suggest=$this->getModel()->{$this->methodName}($keyword,$this->limit,$_GET);
			echo CJSON::encode($suggest);
		}
	}

	/**
	 * @return CActiveRecord
	 */
	protected function getModel()
	{
		return CActiveRecord::model($this->modelName);
	}
}