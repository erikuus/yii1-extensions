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
 *            'class'=>'ext.components.foundation.widgets.XTButtonColumn',
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

class XTButtonColumn extends CButtonColumn
{
	/**
	 * @var string the view button icon.
	 */
	public $buttonCssClass='float-right flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full hover:bg-green-100';
	/**
	 * @var string the view button icon.
	 */
	public $viewButtonIcon='fi-magnifying-glass';
	/**
	 * @var string the update button icon.
	 */
	public $updateButtonIcon='<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M3 17.25V21H6.75L17.81 9.94L14.06 6.19L3 17.25ZM5.92 19H5V18.08L14.06 9.02L14.98 9.94L5.92 19ZM20.71 5.63L18.37 3.29C18.17 3.09 17.92 3 17.66 3C17.4 3 17.15 3.1 16.96 3.29L15.13 5.12L18.88 8.87L20.71 7.04C21.1 6.65 21.1 6.02 20.71 5.63Z"></path></svg>';
	/**
	 * @var string the delete button icon.
	 */
	public $deleteButtonIcon='<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z"></path></svg>';

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
		if(isset($button['visible']) && !$this->evaluateExpression($button['visible'],array('row'=>$row,'data'=>$data)))
			return;
		$label=isset($button['label']) ? $button['label'] : $id;
		$url=isset($button['url']) ? $this->evaluateExpression($button['url'],array('data'=>$data,'row'=>$row)) : '#';
		$options=isset($button['options']) ? $button['options'] : array();
		if(!isset($options['title']))
			$options['title']=$label;
		if(isset($button['icon'])&&$button['icon'])
		{
			if(isset($options['class']))
				$options['class'].=' '.$this->buttonCssClass;
			else
				$options['class']=$this->buttonCssClass;

			echo CHtml::link($button['icon'],$url,$options);
		}
		elseif(isset($button['imageUrl']) && is_string($button['imageUrl']))
			echo CHtml::link(CHtml::image($button['imageUrl'],$label),$url,$options);
		else
			echo CHtml::link($label,$url,$options);
	}
}