<?php
class XDbCriteria extends CDbCriteria
{
	/**
	 * Appends search condition with meta symbols to the existing condition.
	 * @param string $column the column name (or a valid SQL expression)
	 * @param string $keyword the search keyword. This interpretation of the keyword is affected by the next parameter.
	 * @param boolean $escape whether the keyword should be escaped if it contains characters % or _.
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * @param string $like the LIKE operator. Defaults to 'LIKE'. You may also set this to be 'NOT LIKE'.
	 * @param boolean whether to force contain wildcards
	 * @return CDbCriteria the criteria object itself
	 */
	public function mcompare($column, $keyword, $operator='AND', $like='LIKE', $forceContain=false)
	{
		return $this->addSearchCondition("LOWER($column)",$this->formatSearch($keyword,true,$forceContain),false,$operator,$like);
	}

	/**
	 * Adds a case insensitive comparison expression to the condition property.
	 *
	 * This method is a helper that appends to the condition property
	 * with a new comparison expression. The comparison is done by comparing a column
	 * with the given value using some comparison operator.
	 *
	 * Note that any surrounding white spaces will be removed from the value before comparison.
	 * When the value is empty, no comparison expression will be added to the search condition.
	 *
	 * @param string $column the name of the column to be searched
	 * @param string value to be compared with.
	 * @param boolean $partialMatch whether the value should consider partial text match (using LIKE and NOT LIKE operators).
	 * Defaults to false, meaning exact comparison.
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * Defaults to 'AND'.
	 * @return CDbCriteria the criteria object itself
	 */
	public function icompare($column, $value, $partialMatch=false, $operator='AND')
	{
		$value="$value";

		if(preg_match('/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/',$value,$matches))
		{
			$value=$matches[2];
			$op=$matches[1];
		}
		else
			$op='';

		if($value==='')
			return $this;

		if($partialMatch)
		{
			if($op==='')
				return $this->addSearchCondition("LOWER($column)",$this->formatSearch($value),true,$operator);
			if($op==='<>')
				return $this->addSearchCondition("LOWER($column)",$this->formatSearch($value),true,$operator,'NOT LIKE');
		}
		else if($op==='')
			$op='=';

		$this->addCondition("LOWER($column)".$op.self::PARAM_PREFIX.self::$paramCount,$operator);
		$this->params[self::PARAM_PREFIX.self::$paramCount++]=$this->formatSearch($value);

		return $this;
	}

	/**
	 * Adds numeric comparison expression to the condition property.
	 *
	 * This method is a helper that appends to the condition property
	 * with a new comparison expression. The comparison is done by comparing a column
	 * with the given value using some comparison operator.
	 *
	 * Note that any surrounding white spaces will be removed from the value before comparison.
	 * When the value is empty or is not numeric (or integer), no comparison expression will be added to the search condition.
	 *
	 * @param string $column the name of the column to be searched
	 * @param string value to be compared with.
	 * @param boolean whether the value can only be an integer.
	 * Defaults to false.
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * Defaults to 'AND'.
	 * @return CDbCriteria the criteria object itself
	 */
	public function ncompare($column, $value, $integerOnly=false, $operator='AND')
	{
		if(preg_match('/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/',$value,$matches))
		{
			$value=$matches[2];
			$op=$matches[1];
		}
		else
			$op='';

		if($value==='')
			return $this;

		if($integerOnly)
		{
			if(!preg_match('/^\s*[+-]?\d+\s*$/',$value))
				return $this;
		}
		else
		{
			if(!preg_match('/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/',$value))
				return $this;
		}

		if($op==='')
			$op='=';

		$this->addCondition($column.$op.self::PARAM_PREFIX.self::$paramCount,$operator);
		$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;

		return $this;
	}

	/**
	 * Adds date comparison expression to the condition property.
	 *
	 * This method is a helper that appends to the condition property
	 * with a new comparison expression. The comparison is done by comparing a column
	 * with the given value using some comparison operator.
	 *
	 * Note that any surrounding white spaces will be removed from the value before comparison.
	 * When the value is empty or is not date, no comparison expression will be added to the search condition.
	 *
	 * @param string $column the name of the column to be searched
	 * @param string $value to be compared with.
	 * @param boolean $convertToTime whether to convert date to unix timestamp for comparison.
	 * @param array $dateFormat expected format for date value.
	 * Defaults to array('d','m','y').
	 * @param string $dateSeparator character that separates date parts.
	 * Defaults to '.'.
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * Defaults to 'AND'.
	 * @return CDbCriteria the criteria object itself
	 */
	public function dcompare($column, $value, $convertToTime=false, $dateFormat=array('d','m','y'), $dateSeparator='.', $operator='AND')
	{
		if(preg_match('/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/',$value,$matches))
		{
			$value=$matches[2];
			$op=$matches[1];
		}
		else
			$op='';

		if($value==='')
			return $this;

		if(!$this->isValidDate($value,$dateFormat,$dateSeparator))
			return $this;

		if($op==='')
			$op='=';

		if($convertToTime)
			$value=strtotime($value);

		$this->addCondition($column.$op.self::PARAM_PREFIX.self::$paramCount,$operator);
		$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;

		return $this;
	}

	/**
	 * Checks if date is valid
	 * @param string date value
	 * @param array date format [ex. array("d","m","y")]
	 * @param string separator
	 * @return boolean
	 */
	public function isValidDate($value,$format,$sep)
	{
		$valid=false;

		// strip time data
		// get from left until there is a space
		$arr=explode(' ',$value,2);
		$value= array_shift($arr);

		// check format
		if(is_array($format) && count($format)==3 && count(explode($sep,$value))==3)
		{
			$date=array_combine($format,explode($sep,$value));
			if(intval($date['m'])&&intval($date['d'])&&intval($date['y']))
			{
				$m=$date['m'];
				$d=$date['d'];
				$y=$date['y'];
				$valid=checkdate($m,$d,$y);
			}
		}
		return $valid;
	}

	/**
	 * Formats search string
	 * @param string search from user input
	 * @param boolean whether to convert meta symbols
	 * @param boolean whether to force contain wildcards
	 * @return string search for sql
	 */
	public function formatSearch($str, $symbols=false, $forceContain=false)
	{
		if($symbols===true)
			$str=strtr($str, array('*'=>'%','?'=>'_'));
		return $forceContain===true ? '%'.mb_strtolower($str).'%' : mb_strtolower($str);
	}
}