<?php

/**
 * XFoundationFilter class file
 *
 * XFoundationFilter enables to load foundation component on specific actions.
 *
 * The following shows how to use XFoundationFilter:
 * <pre>
 * public function filters()
 * {
 *     return array(
 *         'accessControl',
 *         'postOnly + delete',
 *         array('ext.components.foundation.XFoundationFilter - delete')
 *     );
 * }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */

class XFoundationFilter extends CFilter
{

	protected function preFilter($filterChain)
	{
		Yii::app()->getComponent("foundation");
		return true;
	}
}