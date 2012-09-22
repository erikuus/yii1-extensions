<?php
/**
 * XSearchForm class
 *
 * This is base class for search form models
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XSearchForm extends CFormModel
{
	/**
	 * @return array of params for search url
	 */
	public function buildParams()
	{
		$params=array();
		foreach ($this->attributes as $name=>$value)
		{
			if ($value)
				$params[$name]=trim($value);
		}
		if($params!==array())
			$params['q']= 1;

		return $params;
	}

	/**
	 * @return array get params that match form attributes
	 */
	public function getParams()
	{
		$params=array();
		foreach ($_GET as $name=>$value)
		{
			if (in_array($name, $this->safeAttributeNames))
				$params[$name]=mb_detect_encoding($value, 'UTF-8', true) ? $value : utf8_encode($value);
		}
		return $params;
	}
}