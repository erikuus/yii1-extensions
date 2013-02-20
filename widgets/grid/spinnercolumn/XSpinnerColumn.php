<?php
/**
 * XSpinnerColumn
 *
 * XSpinnerColumn allows to display +/- links (that enable to increase and decrease cell value) in CGridView column.
 *
 * The following shows how to use XSpinnerColumn.
 *
 * In view:
 * $this->widget('zii.widgets.grid.CGridView', array(
 *    'id'=>'person-grid',
 *    'dataProvider'=>$model->search(),
 *    'filter'=>$model,
 *    'columns'=>array(
 *        array(
 *            'class'=>'ext.widgets.grid.spinnercolumn.XSpinnerColumn',
 *            'name'=>'amount',
 *        ),
 *    ),
 * ));
 *
 * In controller:
 * public function actionSpinner($id, $name, $value)
 * {
 *    $model = $this->loadModel($id);
 *    $model->{$name} = $value;
 *    $model->save(false);
 *    if(!Yii::app()->request->isAjaxRequest)
 *        $this->redirect('admin');
 * }
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

class XSpinnerColumn extends CGridColumn
{
	public $name;
	public $sortable=true;
	public $linkHtmlOptions=array();
	public $callbackUrl=array('spinner');

	private $_spinnerClass = "spinner-link";

	public function init()
	{
		parent::init();
		$cs=Yii::app()->getClientScript();
		$gridId = $this->grid->getId();
		$script = <<<SCRIPT
		jQuery(".{$this->_spinnerClass}").live("click", function(e){
			e.preventDefault();
			var link = this;
			$.ajax({
				dataType: "json",
				cache: false,
				url: link.href,
				success: function(data){
					$('#$gridId').yiiGridView.update('$gridId');
				}
			});
		});
SCRIPT;
		$cs->registerScript(__CLASS__.$gridId.'#spinner_link', $script);
	}

	protected function renderDataCellContent($row, $data)
	{
		$value=CHtml::value($data,$this->name);

		$spinUpValue=$value+1;
		$spinDownValue=$value-1;

		$this->callbackUrl['id']=$data->primaryKey;
		$this->callbackUrl['name']=urlencode($this->name);

		$this->callbackUrl['value']=$spinUpValue;
		$spinUpUrl = CHtml::normalizeUrl($this->callbackUrl);

		$this->callbackUrl['value']=$spinDownValue;
		$spinDownUrl = CHtml::normalizeUrl($this->callbackUrl);

		//To account for if the user has provided the class value for linkHtmlOptions
		if(!isset($this->linkHtmlOptions['class']))
			$this->linkHtmlOptions = array_merge($this->linkHtmlOptions, array('class'=>$this->_spinnerClass));
		else
			$this->linkHtmlOptions['class'].=" {$this->_spinnerClass}";

		$spinUpLink=CHtml::link('+', $spinUpUrl, $this->linkHtmlOptions);
		$spinDownLink=CHtml::link('&#8722;', $spinDownUrl, $this->linkHtmlOptions);

		echo $spinUpLink.' '.$value.' '.$spinDownLink;
	}

	protected function renderHeaderCellContent()
	{
		if($this->grid->enableSorting && $this->sortable && $this->name!==null)
			echo $this->grid->dataProvider->getSort()->link($this->name,$this->header);
		else if($this->name!==null && $this->header===null)
		{
			if($this->grid->dataProvider instanceof CActiveDataProvider)
				echo CHtml::encode($this->grid->dataProvider->model->getAttributeLabel($this->name));
			else
				echo CHtml::encode($this->name);
		}
		else
			parent::renderHeaderCellContent();
	}
}
