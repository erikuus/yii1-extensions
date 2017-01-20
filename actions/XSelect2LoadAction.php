<?php
/**
 * XSelect2LoadAction action
 *
 * This action returns options for XSelect2 widget
 *
 * The following shows how to use XSelect2LoadAction action
 *
 * First set up loadCountryOptions action on RequestController actions() method:
 *
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'loadCountryOptions'=>array(
 *             'class'=>'ext.actions.XSelect2LoadAction',
 *             'modelName'=>'Country',
 *             'methodName'=>'loadOptions',
 *         ),
 *     );
 * }
 * </pre>
 *
 * And then set up widget:
 *
 * </pre>
 * $this->widget('ext.widgets.select2.XSelect2', array(
 *     'model'=>$model,
 *     'attribute'=>'id',
 *     'options'=>array(
 *         'minimumInputLength'=>2,
 *         'ajax' => array(
 *             'url'=>$this->createUrl('/request/loadCountryOptions'),
 *             'dataType'=>'json',
 *             'results' => "js: function(data,page){
 *                 return {results: data};
 *             }",
 *         ),
 *         ...
 *     ),
 * ));
 * </pre>
 *
 * Note, you also have to write model method that loads options. For example:
 *
 * <pre>
 * public function loadOptions()
 * {
 *     $options=array();
 *     $models=$this->findAll();
 *     foreach($models as $model)
 *     {
 *         $options[] = array(
 *             'id'=>$model->id,
 *             'text'=>$model->name,
 *         );
 *     }
 *     return $options;
 * }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XSelect2LoadAction extends CAction
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
	 * Suggests models based based on the current user input.
	 */
	public function run()
	{
		$options=$this->getModel()->{$this->methodName}($_GET);
		echo CJSON::encode($options);
	}

	/**
	 * @return CActiveRecord
	 */
	protected function getModel()
	{
		return CActiveRecord::model($this->modelName);
	}
}