<?php
/**
 * XTCheckBoxColumn class file.
 *
 * Inserts checkbox column based on Tailwind CSS Framework.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

Yii::import('zii.widgets.grid.CCheckBoxColumn');

class XTCheckBoxColumn extends CCheckBoxColumn
{
	public $htmlOptions=array('class'=>'w-4');

	public $headerCheckBoxHtmlOptions=array(
		'class'=>'ml-2 h-4 w-4 rounded-sm text-green-500 focus:ring-offset-0 focus:ring-2 border-gray-200 hover:border-gray-300 focus:border-gray-500 focus:ring-green-300 focus:ring-green-300',
		'aria-label'=>'Select all'
	);

	public $checkBoxHtmlOptions=array(
		'class'=>'ml-2 h-4 w-4 rounded-sm text-green-500 focus:ring-offset-0 focus:ring-2 border-gray-200 hover:border-gray-300 focus:border-gray-500 focus:ring-green-300 focus:ring-green-300',
		'aria-label'=>'Select row'
	);


	protected function renderHeaderCellContent()
	{
		if($this->selectableRows===null && $this->grid->selectableRows>1)
		{
			if(isset($this->headerCheckBoxHtmlOptions['class']))
				$this->headerCheckBoxHtmlOptions['class'].=' select-on-check-all';
			else
				$this->headerCheckBoxHtmlOptions['class']='select-on-check-all';
			return;

			echo CHtml::checkBox($this->id.'_all',false,$this->headerCheckBoxHtmlOptions);
		}
		else if($this->selectableRows>1)
			echo CHtml::checkBox($this->id.'_all',false,$this->headerCheckBoxHtmlOptions);
		else
			parent::renderHeaderCellContent();
	}
}