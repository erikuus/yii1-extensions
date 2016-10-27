<?php
/**
 * XTabularInputAction action
 *
 * This action (partial)renders tabular input for XTabularInput widget
 *
 * The following shows how to use XTabularInputAction action.
 *
 * First set up the action on RequestController actions() method:
 * <pre>
 * return array(
 *     'addFields'=>array(
 *         'class'=>'ext.actions.XTabularInputAction',
 *         'modelName'=>'Person',
 *         'viewName'=>'/person/_inputFields',
 *     ),
 * );
 * </pre>
 *
 * And then XTabularInput widget can be configured as follows:
 * <pre>
 * $this->widget('ext.widgets.tabularinput.XTabularInput',array(
 *     'models'=>$persons,
 *     'inputView'=>'_inputFields',
 *     'inputUrl'=>$this->createUrl('request/addFields'),
 * ));
 * </pre>
 *
 * Example of _inputFields partial view:
 * <pre>
 * echo CHtml::activeLabel($model,"[$index]firstname");
 * echo CHtml::activeTextField($model,"[$index]firstname");
 * echo CHtml::error($model,"[$index]firstname");
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTabularInputAction extends CAction
{
	/**
	 * @var mixed the name of the model class that will be passed to
	 * view as variable $model or array (variable name => model name)
	 */
	public $modelName;
	/**
	 * @var array additional $_GET params to be passed to view when rendering each data item.
	 */
	public $viewParams=array();
	/**
	 * @var string name of the partial view.
	 */
	public $viewName;

	/**
	 * Runs the action.
	 */
	public function run()
	{
		if(Yii::app()->request->isAjaxRequest && isset($_GET['index']))
		{
			$params=array();

			foreach($this->viewParams as $paramName)
			{
				if(isset($_GET[$paramName]))
					$params[$paramName]=$_GET[$paramName];
			}

			$params['index']=$_GET['index'];

			if(is_array($this->modelName))
			{
				foreach ($this->modelName as $varName => $modelName)
					$params[$varName]=CActiveRecord::model($modelName);
			}
			else
				$params['model']=CActiveRecord::model($this->modelName);

			$this->getController()->renderPartial($this->viewName, $params);
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}
}