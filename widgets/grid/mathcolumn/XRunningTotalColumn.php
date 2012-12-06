<?php
/**
* RunningTotalColumn class file
*
* @copyright	Copyright © 2011 PBM Web Development - All Rights Reserved
* @version		1.0.0
* @license		BSD 3-Clause
*/
Yii::import('zii.widgets.grid.CDataColumn');

/**
* RunningTotalColumn class.
* A RunningTotalColumn renders the running total of the an attributes value.
* The $value property is a PHP expression that will be evaluated for every data
* cell and whose result will be added to the total; it should return a numeric
* value.  The expression can contain the variables <code>$row</code> - the row
* number (zero-based); <code>$data</code> - the data model for the row; and
* <code>$this</code> - the column object.
*/
class XRunningTotalColumn extends CDataColumn {
	/**
	* @property string A PHP expression that will be evaluated for every data cell
	* and whose result will be rendered as the content of the data cells. The
	* expression can contain the variables <code>$total</code> - the current
	* total; <code>$row</code> - the row number (zero-based); <code>$data</code> -
	* the data model for the row; and <code>$this</code> - the column object.
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
	* @property string The attribute used to determine the sort direction.
	*/
	public $sort;
	/**
	* @var float The total.
	*/
	private $_total=0;
	/**
	* @var float The previous row's value; used to calculate the total when the
	* grid is sorted in descending order.
	*/
	private $_prev = 0;
	/**
	* @var boolean Whether the grid is sorted in descending order.
	*/
	private $_sortDesc;
	/**
	* @var integer The row number that has been calculated.
	*/
	private $_row;

	/**
	* Initialises the column.
	* Sets the initial value for the total.
	*/
	public function init() {
		parent::init();
		if(is_string($this->init))
			$this->init=$this->evaluateExpression($this->init);
		$this->_total=$this->init;
		$this->_sortDesc = !empty($this->sort)&&$this->grid->dataProvider->getSort()->getDirection($this->sort);
	}

	/**
	* Returns a value indicating if the grid this column belongs to is being
	* sorted in descending order.
	* @return boolean true if the grid this column belongs to is being
	* sorted in descending order; false if not.
	*/
	public function getSortDesc() {
		return $this->_sortDesc;
	}

	/**
	* Returns the cell value
	* @param integer the row number (zero-based)
	* @param mixed the data associated with the row
	* @return float The cell value (the current total)
	*/
	public function cellValue($row,$data) {
		if ($this->_row!==$row) {
			if($this->value!==null)
				$val=$this->evaluateExpression($this->value,compact('data','row'));
			else if($this->name!==null)
				$val=CHtml::value($data,$this->name);
			else
				$val=0;

			if ($this->_sortDesc) {
				$this->_total -= $this->_prev;
				$this->_prev = $val;
			}
			else
				$this->_total+=$val;
			$this->_row=$row;
		}
		return $this->_total;
	}

	/**
	* Renders the data cell content and adjusts the running total.
	* This method evaluates value or name to obtain the data cell value.
	* Depending on whether sorting is enabled and if so, the sort direction, the
	* value is either added to the current total before the cell is rendered, or
	* subtracted from the total after the cell is renderd. If set, output is
	* evaluated to determine the rendering result; if not the cell value is
	* rendered.
	* @param integer the row number (zero-based)
	* @param mixed the data associated with the row
	*/
	protected function renderDataCellContent($row, $data) {
		$total = $this->cellValue($row,$data);
		if($this->output!==null)
			$total=$this->evaluateExpression($this->output,compact('data','row','total'));
		echo $this->grid->getFormatter()->format($total,$this->type);
	}
}
