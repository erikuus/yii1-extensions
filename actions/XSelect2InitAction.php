<?php
/**
 * XSelect2InitAction class
 *
 * This action initalizes selection for Select2 widget
 *
 * The following shows how to use XSelect2InitAction action.
 *
 * First set up the action on RequestController actions() method:
 * <pre>
 * return array(
 *     'initPerson'=>array(
 *         'class'=>'ext.actions.XSelect2InitAction',
 *         'modelName'=>'Person',
 *         'textField'=>'fullname',
 *     ),
 * );
 * </pre>
 *
 * And then Select2 widget can be initalized as follows:
 * <pre>
 * $this->widget('ext.widgets.select2.XSelect2', array(
 *     'model'=>$model,
 *     'attribute'=>'id',
 *     'options'=>array(
 *         ...
 *         'initSelection' => "js:function (element, callback) {
 *             var id=$(element).val();
 *             if (id!=='') {
 *                 $.ajax('".$this->createUrl('/request/initPerson')."', {
 *                     dataType: 'json',
 *                     data: {
 *                         id: id
 *                     }
 *                 }).done(function(data) {callback(data);});
 *             }
 *         }",
 *     ),
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XSelect2InitAction extends CAction
{
	/**
	 * @var string name of the model class.
	 */
	public $modelName;
	/**
	 * @var string name of the model primary key attribute.
	 */
	public $idField='id';
	/**
	 * @var string name of the model attribute that is used as text value for Select2 widget.
	 */
	public $textField;
	/**
	 * @var string name of the model method that returns array of options [id=>text].
	 */
	public $getOptionsMethod;
	/**
	 * @var string name of the model method that returns text by id.
	 */
	public $getTextByIdMethod;
	/**
	 * @var boolean whether id and text are same.
	 */
	public $idTextSame=false;

	/**
	 * Runs the action.
	 */
	public function run()
	{
		if(isset($_GET['id']))
		{
			$id=$_GET['id'];
			if(strstr($id, ','))
				$this->getMultiple($id);
			else
				$this->getSingle($id);
		}
	}

	/**
	 * @param mixed id
	 * @return json encoded single selection
	 */
	protected  function getSingle($id)
	{
		$data=array();

		if($this->textField)
		{
			$model=$this->getModel()->findByAttributes(array($this->idField=>$id));
			if($model!==null)
				$data=array('id'=>$model->{$this->idField},'text'=>$model->{$this->textField});
		}
		elseif($this->getOptionsMethod)
		{
			$options=$this->getModel()->{$this->getOptionsMethod}();
			$text=isset($options[$id]) ? $options[$id] : null;
			$data=array('id'=>$id,'text'=>$text);
		}
		elseif($this->getTextByIdMethod)
		{
			$text=$this->getModel()->{$this->getTextByIdMethod}($id);
			$data=array('id'=>$id,'text'=>$text);
		}
		elseif($this->idTextSame)
			$data=array('id'=>$id,'text'=>$id);

		echo CJSON::encode($data);
	}

	/**
	 * @param string comma separated list of ids
	 * @return json encoded multiple selections
	 */
	public function getMultiple($id)
	{
		$data=array();

		if($this->textField)
		{
			$criteria=new CDbCriteria();
			$criteria->addInCondition($this->idField, explode(',',$id));
			$models=$this->getModel()->findAll($criteria);
			foreach($models as $model)
		    	$data[]=array('id'=>$model->{$this->idField},'text'=>$model->{$this->textField});
		}
		elseif($this->getOptionsMethod)
		{
			$options=$this->getModel()->{$this->getOptionsMethod}();
			$ids=explode(',',$id);
			foreach($ids as $id)
			{
		    	$text=isset($options[$id]) ? $options[$id] : null;
				$data[]=array('id'=>$id,'text'=>$text);
			}
		}
		elseif($this->getTextByIdMethod)
		{
			$ids=explode(',',$id);
			foreach($ids as $id)
			{
		    	$text=$this->getModel()->{$this->getTextByIdMethod}($id);
				$data[]=array('id'=>$id,'text'=>$text);
			}
		}
		elseif($this->idTextSame)
		{
			$ids=explode(',',$id);
			foreach($ids as $id)
		    	$data[]=array('id'=>$id,'text'=>$id);
		}

		echo CJSON::encode($data);
	}

	/**
	 * @return CActiveRecord
	 */
	protected function getModel()
	{
		return CActiveRecord::model($this->modelName);
	}
}