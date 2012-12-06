<?php
/**
* TotalColumn class file
*
* @copyright	Copyright © 2011 PBM Web Development - All Rights Reserved
* @version		1.0.0
* @license		BSD 3-Clause
*/
Yii::import('zii.widgets.grid.CDataColumn');

/**
* TotalColumn class.
* A TotalColumn renders the total of the column's data cells in the footer cell;
* like the data cells, the content of the footer cell is rendered according to
* the column's $type property.
*
* The value of a data cell is either the value of the attribute given by the
* $name property or the value returned by the expression given the $value
* property; the expression can contain the variables <code>$row</code> - the row
* number (zero-based); <code>$data</code> - the data model for the row; and
* <code>$this</code> - the column object.
*
* If the $output property is empty the value of the data cell is rendered. If
* $ouput is a string it is a PHP expression that is evaluated for each cell and
* whose result is rendered; ; the expression can contain the variables
* <code>$value</code> - the value of the data cell, <code>$row</code> - the row
* number (zero-based), <code>$data</code> - the data model for the row, and
* <code>$this</code> - the column object.
*
* $footer determines how the footer cell is rendered.
* If $footer===NULL the footer cell is not rendered and the column does not have
* a footer.
* If $footer===TRUE the footer cell is rendered with the column total.
* If $footer is a string it is a PHP expression that will be evaluated and
* whose result will be rendered as the content of the footer cell; the
* expression can contain the variables <code>$total</code> - the column total,
* and <code>$this</code> - the column object.
* Note: The value of the footer cell is always the column total, whether or not
* the cell is rendered.
*/
class XTotalColumn extends CDataColumn {
	/**
	* @property boolean Whether to NULL data cells and display the grid's
	* nullDisplay property if the value is zero.
	* Note: Does not apply to the total
	*/
	public $nullOnZero=false;
	/**
	* @property string A PHP expression that will be evaluated for every data cell
	* and whose result will be rendered as the content of the data cells; the
	* expression can contain the variables <code>$total</code> - the current
	* total, <code>$row</code> - the row number (zero-based), <code>$data</code> -
	* the data model for the row, and <code>$this</code> - the column object.
	*/
	public $output;
	/**
	* @property mixed Either, float: the initial value of the total, or string: A
	* PHP expression that will be evaluated when the grid initialises and whose
	* result becomes the initial value of the total. The expression can contain
	* the variable <code>$this</code> - the column object.
	*/
	public $init;
	/**
	* @var float The total.
	*/
	private $_total=0;

	/**
	* Initialises the column.
	* Sets the initial value for the total.
	*/
	public function init() {
		if($this->name===null && $this->value===null)
			throw new CException(Yii::t('cols','Either "name" or "value" must be specified for TotalColumn.'));
		if(is_numeric($this->init))
			$this->l=$this->init;
		elseif(is_string($this->init))
			$this->_total=$this->evaluateExpression($this->init);
	}

	/**
	* Renders the data cell content and adds the cell value to the total.
	* This method evaluates value or name to obtain the data cell value and adds
	* it to the current total. If set, output is evaluated to determine the
	* rendering result; if not the cell value is rendered.
	* @param integer the row number (zero-based)
	* @param mixed the data associated with the row
	*/
	protected function renderDataCellContent($row, $data) {
		if($this->value!==null)
			$value=$this->evaluateExpression($this->value,compact('data','row'));
		else if($this->name!==null)
			$value=CHtml::value($data,$this->name);
		else
			$value=0;

		$this->_total+=$value;

		if($value==0&&$this->nullOnZero)
			$value=null;

		if($this->output!==null&&$value!==null)
			$value=$this->evaluateExpression($this->output,compact('data','row','value'));
		echo $value===null
			?$this->grid->nullDisplay
			:$this->grid->getFormatter()->format($value,$this->type);
	}

	/**
	* Renders the total in the footer cell.
	* If $footer===TRUE the footer cell is rendered with the column total.
	* If $footer is a string it is a PHP expression that will be evaluated and
	* whose result will be rendered as the content of the footer cell; the
	* expression can contain the variables <code>$total</code> - the column total,
	* and <code>$this</code> - the column object.
	* Note: The value of the footer cell is always the column total, whether or
	* not the cell is rendered.
	*/
	protected function renderFooterCellContent() {
		if (empty($this->footer))
			$footer = '';
		else {
			$expression = ($this->footer===true?$this->output:$this->footer);
			$footer = ($expression
				?$this->evaluateExpression($expression,array(
				'value'=>$this->getFooterValue()
				))
				:$this->getFooterValue()
			);
		}
		echo trim($footer)!==''
			?$this->grid->getFormatter()->format($footer,$this->type)
			:$this->grid->blankDisplay;
	}

	/**
	* Returns the value of the footer cell - the total - for this column.
	* @return float The total of the column
	*/
	public function getFooterValue() {
		return $this->_total;
	}
}
