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
 *             'paramValidators'=>array(
 *                 'scope'=>'checkScope',
 *             ),
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
	 * @var array model validation method names indexed by request parameter name.
	 * Validation is optional; existing action configurations remain unchanged.
	 */
	public $paramValidators=array();

	/**
	 * Suggests models based based on the current user input.
	 */
	public function run()
	{
		$model=$this->getModel();
		$this->validateParams($model, $_GET);
		$options=$model->{$this->methodName}($_GET);
		echo CJSON::encode($options);
	}

	/**
	 * Validates configured request parameters before invoking the loader method.
	 * Missing or non-scalar parameters are malformed requests; scalar values
	 * rejected by the model validator are treated as unknown resources.
	 *
	 * @param CActiveRecord $model
	 * @param array $params
	 * @throws CHttpException when a configured parameter is invalid
	 */
	protected function validateParams($model, $params)
	{
		foreach($this->paramValidators as $paramName=>$validatorMethod)
		{
			if(!isset($params[$paramName]) || !is_scalar($params[$paramName]))
				throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');

			if(!$model->{$validatorMethod}($params[$paramName]))
				throw new CHttpException(404,'The requested page does not exist.');
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
