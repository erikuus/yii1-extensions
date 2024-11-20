<?php
/**
 * XReorderColumn
 *
 * This class allows to add reordering links (up and down icons) to gridview.
 *
 * This class is designed to be used in connection with
 * - XReorderAction
 * - XReorderBehavior
 *
 * The following shows how to use XReorderColumn.
 *
 * <pre>
 * $this->widget('zii.widgets.grid.CGridView', array(
 *    'id'=>'person-grid',
 *    'dataProvider'=>$model->search(),
 *    'filter'=>$model,
 *    'columns'=>array(
 *        'first_name',
 *        'last_name',
 *        array(
 *            'class'=>'ext.widgets.grid.reordercolumn.XReorderColumn',
 *            'name'=>'sort',
 *            'callbackUrl'=>array('reorderPersons'),
 *        ),
 *    ),
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

class XReorderColumn extends CGridColumn
{
	/**
	 * @var string grid column name
	 */
	public $name;
	/**
	 * @var array callback url for reorder action. Defaults to array('reorder')
	 */
	public $callbackUrl=array('reorder');
	/**
	 * @var string css class name for reorder links. Defaults to 'reorder'
	 * Note that you have to define different class names when using multiple gridviews per one page.
	 */
	public $cssClass='reorder';
	/**
	 * @var array the HTML options for the data cell tags.
	 * Defaults to array('class'=>'button-column')
	 */
	public $htmlOptions=array('class'=>'button-column');
	/**
	 * @var mixed boolean or PHP expression that is evaluated for every data cell whether the reorder links are visible. Defaults to true.
	 */
	public $reorderVisible=true;

	private $_upIcon;
	private $_downIcon;

	/**
	 * Init column
	 * Publish necessary client script.
	 */
	public function init()
	{
		parent::init();

		if($this->visible)
		{
			$this->publishReorderColumnAssets();
			$this->registerReorderColumnClientScript();
		}
	}

	/**
	 * Publish assets and define up and down icons.
	 */
	protected function publishReorderColumnAssets()
	{
		$assets=dirname(__FILE__).'/assets';
		$baseUrl=Yii::app()->assetManager->publish($assets);
		$this->_upIcon=$baseUrl.'/up.png';
		$this->_downIcon=$baseUrl.'/down.png';
	}

	/**
	 * Register client script.
	 */
	protected function registerReorderColumnClientScript()
	{
		$gridId = $this->grid->getId();
		$script = <<<SCRIPT
		jQuery(".{$this->cssClass}").live("click", function(e){
			e.preventDefault();
			$.fn.yiiGridView.update("$gridId", {
				type:"POST",
				url:$(this).attr("href"),
				success:function() {
					$.fn.yiiGridView.update("$gridId");
				}
			});
		});
SCRIPT;
		Yii::app()->getClientScript()->registerScript(__CLASS__.$gridId.'#reorder_link', $script);
	}

	/**
	 * Render data cell (up and down links)
	 */
	protected function renderDataCellContent($row, $data)
	{
		if($this->reorderVisible===true || $this->evaluateExpression($this->reorderVisible, array('row'=>$row,'data'=>$data)))
		{
			$this->renderReorderLink($data->primaryKey, 'up', $this->_upIcon);
			$this->renderReorderLink($data->primaryKey, 'down', $this->_downIcon);
		}
	}

	/**
	 * Render header cell (can not be sortable)
	 */
	protected function renderHeaderCellContent()
	{
		if($this->name!==null && $this->header===null)
		{
			if($this->grid->dataProvider instanceof CActiveDataProvider)
				echo CHtml::encode($this->grid->dataProvider->model->getAttributeLabel($this->name));
			else
				echo CHtml::encode($this->name);
		}
		else
			parent::renderHeaderCellContent();
	}

	/**
	 * Render order link
	 * @param integer $id primary key
	 * @param string $move reorder direction ['up' or 'down']
	 * @param string $icon url for up or down icon
	 */
	protected function renderReorderLink($id, $move, $icon)
	{
		$this->callbackUrl['id'] = $id;
		$this->callbackUrl['move'] = $move;

		$url=CHtml::normalizeUrl($this->callbackUrl);

		echo CHtml::link(CHtml::image($icon), $url, array(
			'class'=>$this->cssClass,
			'title'=>$move
		));
	}
}