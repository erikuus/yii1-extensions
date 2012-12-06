<?php
/**
* CalcColumn class file
*
* @copyright	Copyright © 2011 PBM Web Development - All Rights Reserved
* @version		1.0.0
* @license		BSD 3-Clause
*/
Yii::import('zii.widgets.grid.CDataColumn');

/**
* CalcColumn class.
* A CalcColumn calculates it's value based on other columns in the grid.
* In addition to the normal variables available in the $value expression, you
* can reference other grid columns by $cn, where n is the zero-based column
* number, eaxample: $c0+$c1+$c2 sums the first three columns in the grid.
* A CalcColumn can be used in the calcuation of another CalcColumn; it can not
* reference itself and circular references are not allowed.
*
* $footer determines the footer cell value. (See $footerOutput for rendering)
* If $footer===NULL the expression in value will be evaluated and the result
* used as the value of the footer cell.
* If $footer===TRUE the footer cell value is the column total.
* If $footer is a string it is a PHP expression that will be evaluated and whose
* result is the value of the footer cell; the expression can contain the
* variables <code>$total</code> - the column total, references to other grid
* columns by $cn (see above; referenced columns must have the "footerCellValue"
* property), and <code>$this</code> - the column object.
* For other value the footer cell value is calculated using the expression in
* $value; the expression can not use the varialbes $data or $row in this case.
* Note: the footer cell has a value even if it is not rendered - this allows the
* value to be used by other CalcColumns.
*/
class XCalcColumn extends CDataColumn {
	/**
	* @property boolean Whether to NULL data cells and display the grid's
	* nullDisplay property if the value is zero.
	* Note: Does not apply to the footer
	*/
	public $nullOnZero=false;
	/**
	* @property string A PHP expression that will be evaluated for every data cell
	* and whose result will be rendered as the content of the cells. The
	* expression can contain the variables <code>$value</code> - the cell value,
	* and <code>$this</code> - the column object.
	*/
	public $output;
	/**
	* @property mixed If $footerOutput===NULL the column does not have a footer.
	* If $footerOutput===TRUE the footer cell is rendered using the expression in
	* $output.
	* If $footerOutput is a string it is a PHP expression that will be evaluated
	* and whose result will be rendered as the content of the footer cell. The
	* expression can contain the variables <code>$value</code> - the footer cell
	* value, and <code>$this</code> - the column object.
	*/
	public $footerOutput;
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
	* @var float The footer value.
	*/
	private $_footerValue;

	/**
	* Initialises the column.
	* Checks that the value property is set
	*/
	public function init() {
		if($this->value===null)
			throw new CException(Yii::t('cols','"value" property must be specified for CalcColumn.'));
	}

	/**
	* Renders the data cell content.
	* This method evaluates the expression in the value property to obtain the
	* data cell value. If set, output is evaluated to determine the rendering
	* result; if not the cell value is not rendered.
	* @param integer the row number (zero-based)
	* @param mixed the data associated with the row
	*/
	protected function renderDataCellContent($row,$data) {
		$value=$this->evaluateExpression($this->value,array_merge(
			compact('data','row'),
			$this->cols($this,$row,$data)
		));

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
	* Renders the footer cell.
	* If $footerOutput is emtpy the footer cell is blank.
	* If $footerOutput===TRUE the footer cell is rendered using the expression in
	* $output.
	* If $footerOutput is a string it is a PHP expression that will be evaluated
	* and whose result will be rendered as the content of the footer cell. The
	* expression can contain the variables <code>$value</code> - the footer cell
	* value, and <code>$this</code> - the column object.
	*/
	protected function renderFooterCellContent() {
		if (empty($this->footerOutput))
			$footer = '';
		else {
			$expression = ($this->footerOutput===true?$this->output:$this->footerOutput);
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
	* Returns the footer cell value for this column.
	* If $footer===TRUE the footer cell value is the column total.
	* If $footer is a string it is a PHP expression that will be evaluated and
	* whose result is the value of the footer cell; the expression can contain the
	* variables <code>$total</code> - the column total, references to other grid
	* columns by $cn (see above; referenced columns must have the
	* "footerCellValue" property), and <code>$this</code> - the column object.
	* For other value the footer cell value is calculated using the expression in
	* $value; the expression can not use the varialbes $data or $row in this case.
	* Note: the footer cell has a value even if it is not rendered - this allows the
	* value to be used by other CalcColumns.
	* @return float The footer cell value
	*/
	public function getFooterValue() {
		if(empty($this->_footerValue)) {
			if ($this->footer===true)
				$this->_footerValue = $this->_total;
			else {
				$expression = (is_string($this->footer)?$this->footer:$this->value);
				preg_match_all('/\$(c(\d+))/',$expression,$matches,PREG_SET_ORDER);
				$cols=array();
				if(!empty($matches))
					foreach ($matches as $match) {
						$col=$this->grid->columns[$match[2]];
						if(!$col->canGetProperty('footerValue'))
							throw new CException(Yii::t('ziiCols','{class} must have a readable footerValue property',array('{class}'=>get_class($col))));
						$cols[$match[1]]=$col->footerValue;
					}
				$this->_footerValue = $this->evaluateExpression($expression,array_merge(
					array('total'=>$this->_total),$cols
				));
			}
		}
		return $this->_footerValue;
	}

	/**
	* Returns whether this column has a footer cell.
	* This is determined based on whether footerOutput is set.
	* @return boolean TRUE if this column has a footer cell, FALSE if not.
	*/
	public function getHasFooter() {
		return !is_null($this->footerOutput);
	}

	private function cols($obj,$row,$data) {
		$cols=array();
		preg_match_all('/\$(c(\d+))/',$obj->value,$matches,PREG_SET_ORDER);
		if(!empty($matches))
			foreach ($matches as $match) {
				$col=$this->grid->columns[$match[2]];
				if ($col===$this)
					throw new CException(Yii::t('cols','Self-referencing CalcColumn at column {col}.',array('{col}'=>$match[2])));
				if (method_exists($col,'cellValue'))
					$value=$col->cellValue($row,$data);
				elseif ($col->value!==null)
					$value=$this->evaluateExpression($col->value,array_merge(
						compact('data','row'),
						$this->cols($col,$row,$data)
					));
				else
					$value=CHtml::value($data,$col->name);

				$cols[$match[1]]=$value;
			}
		return $cols;
	}
}
