<?php
/**
 * XDateTimeParser checks if a given date is valid according to the specified pattern.
 *
 * The following pattern characters are recognized:
 * <pre>
 * Pattern |      Description
 * ----------------------------------------------------
 * d       | Day of month 1 to 31, no padding
 * dd      | Day of month 01 to 31, zero leading
 * M       | Month digit 1 to 12, no padding
 * MM      | Month digit 01 to 12, zero leading
 * yy      | 2 year digit, e.g., 96, 05
 * yyyy    | 4 year digit, e.g., 2005
 * ----------------------------------------------------
 * </pre>
 * All other characters must appear in the date string at the corresponding positions.
 *
 * For example, to parse a date string '21/10/2008', use the following:
 * <pre>
 * $boolDateValid=CDateTimeParser::parse('21/10/2008','dd/MM/yyyy');
 * </pre>
 */
class XDateTimeParser {
	/**
	 * Checks if the given date string is a valid date.
	 * @param string $value the date string to be parsed
	 * @param string $pattern the pattern that the date string is following
	 * @param array $defaults the default values for year, month, day.
	 * The default values will be used in case when the pattern doesn't specify the
	 * corresponding fields. For example, if the pattern is 'MM/yyyy' and this
	 * parameter is array('day'=>1), then the actual day
	 * for the parsing result will take value 1.
	 * This parameter has been available since version 1.1.5.
	 * @return boolean false if parsing fails.
	 */
	public static function parse($value, $pattern, $defaults = array()) {
		$tokens = self::tokenize($pattern);
		$i = 0;
		$n = strlen($value);
		foreach($tokens as $token) {
			switch ($token) {
				case 'yyyy' :
					{
						if (($year = self::parseInteger($value, $i, 4, 4)) === false)
							return false;
						$i += 4;
						break;
					}
				case 'yy' :
					{
						if (($year = self::parseInteger($value, $i, 1, 2)) === false)
							return false;
						$i += strlen($year);
						break;
					}
				case 'MM' :
					{
						if (($month = self::parseInteger($value, $i, 2, 2)) === false)
							return false;
						$i += 2;
						break;
					}
				case 'M' :
					{
						if (($month = self::parseInteger($value, $i, 1, 2)) === false)
							return false;
						$i += strlen($month);
						break;
					}
				case 'dd' :
					{
						if (($day = self::parseInteger($value, $i, 2, 2)) === false)
							return false;
						$i += 2;
						break;
					}
				case 'd' :
					{
						if (($day = self::parseInteger($value, $i, 1, 2)) === false)
							return false;
						$i += strlen($day);
						break;
					}

				default :
					{
						$tn = strlen($token);
						if ($i >= $n || substr($value, $i, $tn) !== $token)
							return false;
						$i += $tn;
						break;
					}
			}
		}
		if ($i < $n)
			return false;

		if (! isset($year))
			$year = isset($defaults['year']) ? $defaults['year'] : date('Y');
		if (! isset($month))
			$month = isset($defaults['month']) ? $defaults['month'] : date('n');
		if (! isset($day))
			$day = isset($defaults['day']) ? $defaults['day'] : date('j');

		$year = (int)$year;
		$month = (int)$month;
		$day = (int)$day;

		return CTimestamp::isValidDate($year, $month, $day);
	}

	/*
	 * @param string $pattern the pattern that the date string is following
	 */
	private static function tokenize($pattern) {
		if (! ($n = strlen($pattern)))
			return array();
		$tokens = array();
		for($c0 = $pattern[0],$start = 0,$i = 1;$i < $n;++ $i) {
			if (($c = $pattern[$i]) !== $c0) {
				$tokens[] = substr($pattern, $start, $i - $start);
				$c0 = $c;
				$start = $i;
			}
		}
		$tokens[] = substr($pattern, $start, $n - $start);
		return $tokens;
	}

	/*
	 * @param string $value the date string to be parsed
	 * @param integer $offset starting offset
	 * @param integer $minLength minimum length
	 * @param integer $maxLength maximum length
	 */
	protected static function parseInteger($value, $offset, $minLength, $maxLength) {
		for($len = $maxLength;$len >= $minLength;-- $len) {
			$v = substr($value, $offset, $len);
			if (ctype_digit($v) && strlen($v) >= $minLength)
				return $v;
		}
		return false;
	}
}
?>