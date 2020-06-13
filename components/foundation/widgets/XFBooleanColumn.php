<?php
/**
 * XFBooleanColumn
 *
 * XFBooleanColumn allows to display Y/N links (based on boolean value) in CGridView column.
 *
 * The following shows how to use XFBooleanColumn.
 *
 * In view:
 * <pre>
 * $this->widget('zii.widgets.grid.CGridView', array(
 *    'id'=>'person-grid',
 *    'dataProvider'=>$model->search(),
 *    'filter'=>$model,
 *    'columns'=>array(
 *        array(
 *            'class' => 'ext.components.foundation.widgets.XFBooleanColumn',
 *            'name' => 'active',
 *        ),
 *    ),
 * ));
 * </pre>
 *
 * In controller:
 * <pre>
 * public function actionToogleBoolean($id, $name, $value)
 * {
 *    $model = $this->loadModel($id);
 *    $model->{$name} = $value;
 *    $model->save(false);
 *    if(!Yii::app()->request->isAjaxRequest)
 *        $this->redirect('admin');
 * }
 * </pre>
 *
 * @author Alexander Makarov
 * @version 1.1
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0
 */

class XFBooleanColumn extends CGridColumn
{
	/**
	 * @var string the column name
	 */
	public $name;
	/**
	 * @var boolean whether to enable sorting
	 */
	public $sortable=true;
	/**
	 * @var string callback url to action that toggles boolean value. Defaults to array('toggleBoolean')
	 */
	public $callbackUrl = array('toggleBoolean');
	/**
	 * @var string the true icon (defaults to 'fi-check').
	 */
	public $trueIconCssClass='fi-check';
	/**
	 * @var string the false icon (defaults to 'fi-minus').
	 */
	public $falseIconCssClass='fi-minus';
	/**
	 * @var array the HTML options for the cell tags.
	 */
	public $htmlOptions=array('class'=>'boolean-column');

	private $_booleanClass = "boolean-link";

	public function init()
	{
		parent::init();
		$cs=Yii::app()->getClientScript();
		$gridId = $this->grid->getId();
		$script = <<<SCRIPT
		jQuery(".{$this->_booleanClass}").live("click", function(e){
			e.preventDefault();
			var link = this;
			$.ajax({
				type: 'POST',
				dataType: 'json',
				cache: false,
				url: link.href,
				success: function(data){
					$('#$gridId').yiiGridView('update');
				}
			});
		});
SCRIPT;
		$cs->registerScript(__CLASS__.$gridId.'#flag_link', $script);
	}

	protected function renderDataCellContent($row, $data)
	{
		$value=CHtml::value($data,$this->name);

		$this->callbackUrl['id'] = $data->primaryKey;
		$this->callbackUrl['name'] = urlencode($this->name);
		$this->callbackUrl['value'] = (int)empty($value);

		$link = CHtml::normalizeUrl($this->callbackUrl);
		$icon = !empty($value) ?
			'<i class="true '.$this->trueIconCssClass.'"></i>' :
			'<i class="false '.$this->falseIconCssClass.'"></i>';

		echo CHtml::link($icon, $link, array('class'=>$this->_booleanClass));
	}

	protected function renderHeaderCellContent()
	{
		if($this->grid->enableSorting && $this->sortable && $this->name!==null)
			echo $this->grid->dataProvider->getSort()->link($this->name,$this->header);
		elseif($this->name!==null && $this->header===null)
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
