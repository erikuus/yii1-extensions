<?php
/**
 * XTReorderColumn
 *
 * Inserts reorder column based on Tailwind CSS Framework.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

class XTReorderColumn extends CGridColumn
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
	public $triggerCssClass='reorder';
	/**
	 * @var string button css class
	 */
	public $buttonCssClass='inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-gray-500 hover:bg-green-100 hover:text-gray-800';	
	/**
	 * @var string the up icon.
	 */
	public $upIcon='<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M4 12L5.41 13.41L11 7.83V20H13V7.83L18.58 13.42L20 12L12 4L4 12Z" fill="currentColor"></path></svg>';
	/**
	 * @var string the down icon.
	 */
	public $downIcon='<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M20 12L18.59 10.59L13 16.17V4H11V16.17L5.42 10.58L4 12L12 20L20 12Z" fill="currentColor"></path></svg>';
	/**
	 * @var array the HTML options for the data cell tags.
	 */
	public $htmlOptions=array();
	/**
	 * @var mixed boolean or PHP expression that is evaluated for every data cell whether the reorder links are visible. Defaults to true.
	 */
	public $reorderVisible=true;

	/**
	 * Init column
	 * Publish necessary client script.
	 */
	public function init()
	{
		parent::init();

		if($this->visible)
			$this->registerReorderColumnClientScript();
	}

	/**
	 * Register client script.
	 */
	protected function registerReorderColumnClientScript()
	{
		$gridId = $this->grid->getId();
		$script = <<<SCRIPT
		jQuery(".{$this->triggerCssClass}").live("click", function(e){
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
			$this->renderReorderLink($data->primaryKey, 'up', $this->upIcon);
			$this->renderReorderLink($data->primaryKey, 'down', $this->downIcon);
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

		echo CHtml::link($icon, $url, array(
			'class'=>$this->triggerCssClass.' '.$this->buttonCssClass
		));
	}
}