<?php
/**
 * XFButtonColumn class file.
 *
 * Inserts button column based on Foundation CSS Framework icon set.
 *
 * The following shows how to use XFBooleanColumn:
 *
 * In view:
 * <pre>
 * $this->widget('zii.widgets.grid.CGridView', array(
 *    'id'=>'person-grid',
 *    'dataProvider'=>$model->search(),
 *    'filter'=>$model,
 *    'columns'=>array(
 *        array(
 *            'class'=>'ext.components.foundation.widgets.XFButtonColumn',
 *            'template'=>'{view} {update} {settings}',
 *            'buttons'=>array(
 *                'settings'=>array(
 *                    'label'=>Yii::t('ui','Settings'),
 *                    'url'=>'array("setting","id"=>$data->id)',
 *                    'icon'=>'fi-widget',
 *                ),
 *            ),
 *        ),
 *    ),
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

Yii::import('zii.widgets.grid.CButtonColumn');

class XFButtonColumn extends CButtonColumn
{
	/**
	 * @var string the view button icon (defaults to 'fi-magnifying-glass').
	 */
	public $viewButtonIcon='fi-magnifying-glass';
	/**
	 * @var string the update button icon (defaults to 'fi-pencil').
	 */
	public $updateButtonIcon='fi-pencil';
	/**
	 * @var string the delete button icon (defaults to 'fi-x').
	 */
	public $deleteButtonIcon='fi-x';

	/**
	 * Initializes the default buttons (view, update and delete).
	 */
	protected function initDefaultButtons()
	{
		parent::initDefaultButtons();

		if($this->viewButtonIcon!==false&&!isset($this->buttons['view']['icon']))
			$this->buttons['view']['icon']=$this->viewButtonIcon;

		if($this->updateButtonIcon!==false&&!isset($this->buttons['update']['icon']))
			$this->buttons['update']['icon']=$this->updateButtonIcon;

		if($this->deleteButtonIcon!==false&&!isset($this->buttons['delete']['icon']))
			$this->buttons['delete']['icon']=$this->deleteButtonIcon;
	}

	/**
	 * Renders a link button.
	 * @param string $id the ID of the button
	 * @param array $button the button configuration which may contain 'label', 'url', 'imageUrl' and 'options' elements.
	 * See {@link buttons} for more details.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data object associated with the row
	 */
	protected function renderButton($id,$button,$row,$data)
	{
		if (isset($button['visible']) && !$this->evaluateExpression($button['visible'],array('row'=>$row,'data'=>$data)))
  			return;
		$label=isset($button['label']) ? $button['label'] : $id;
		$url=isset($button['url']) ? $this->evaluateExpression($button['url'],array('data'=>$data,'row'=>$row)) : '#';
		$options=isset($button['options']) ? $button['options'] : array();
		if(!isset($options['title']))
			$options['title']=$label;
		if(isset($button['icon'])&&$button['icon'])
			echo CHtml::link('<i class="'.$button['icon'].'"></i>',$url,$options);
		elseif(isset($button['imageUrl']) && is_string($button['imageUrl']))
			echo CHtml::link(CHtml::image($button['imageUrl'],$label),$url,$options);
		else
			echo CHtml::link($label,$url,$options);
	}
}