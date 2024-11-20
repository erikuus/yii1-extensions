<?php
/**
 * XFlagColumn
 *
 * XFlagColumn allows to display Y/N links (based on boolean value) in CGridView column.
 *
 * The following shows how to use XFlagColumn.
 *
 * In view:
 * $this->widget('zii.widgets.grid.CGridView', array(
 *    'id'=>'person-grid',
 *    'dataProvider'=>$model->search(),
 *    'filter'=>$model,
 *    'columns'=>array(
 *        array(
 *            'class' => 'ext.widgets.grid.flagcolumn.XFlagColumn',
 *            'name' => 'active',
 *        ),
 *    ),
 * ));
 *
 * In controller:
 * public function actionFlag($pk, $name, $value){
 *    $model = $this->loadModel($pk);
 *    $model->{$name} = $value;
 *    $model->save(false);
 *    if(!Yii::app()->request->isAjaxRequest)
 *        $this->redirect('admin');
 * }
 *
 * @author Alexander Makarov
 * @version 1.1
 *
 * Added icons
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.2
 */

class XFlagColumn extends CGridColumn
{
	public $name;
	public $icons=false;
	public $yesFlag = '+';
	public $noFlag = '-';
	public $sortable=true;
	public $callbackUrl = array('flag');
	public $htmlOptions = array('class'=>'flag-column');
	public $flagClass = "flag-link";
	public $flagClasses;

	private $_assets;

	public function init()
	{
		parent::init();

		if($this->icons)
			$this->_assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		$gridId = $this->grid->getId();
		$script = <<<SCRIPT
		jQuery(".{$this->flagClass}").live("click", function(e){
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
		Yii::app()->getClientScript()->registerScript(__CLASS__.$gridId.'#flag_link', $script);
	}

	protected function renderDataCellContent($row, $data)
	{
		$value=CHtml::value($data,$this->name);

		$this->callbackUrl['pk'] = $data->primaryKey;
		$this->callbackUrl['name'] = urlencode($this->name);
		$this->callbackUrl['value'] = (int)empty($value);

		$link = CHtml::normalizeUrl($this->callbackUrl);

		$yes = $this->icons ? CHtml::image($this->_assets . '/checkbox-checked.png') : $this->yesFlag;
		$no = $this->icons ? CHtml::image($this->_assets . '/checkbox-unchecked.png') : $this->noFlag;

		echo CHtml::link(!empty($value) ? $yes : $no, $link, array(
			'class' => $this->flagClass.$this->flagClasses,
			'title' => !empty($value) ? 'Yes' : 'No'
		));
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
