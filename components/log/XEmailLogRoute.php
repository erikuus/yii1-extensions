<?php
/**
 * XEmailLogRoute class
 *
 * This email log route ignores 404 (Page Not Found) errors
 * when used together with XLogFilter
 *
 * Configuration example:
 * 'log' => array(
 *     'class' => 'CLogRouter',
 *     'routes' => array(
 *         array(
 *             'class' => 'ext.components.log.XEmailLogRoute',
 *             'filter' => array(
 *                 'class'=>'ext.components.log.XLogFilter',
 *                 'levels' => 'error',
 *                 'emails' => 'myemail@mydomain.com',
 *                 'ignoreCategories' => array(
 *                     'exception.CHttpException.404',
 *                 ),
 *             ),
 *         ),
 *     ),
 * )
 */
class XEmailLogRoute extends CEmailLogRoute
{
	protected function processLogs($logs)
	{
		if(empty($logs))
			return;

		parent::processLogs($logs);
	}
}