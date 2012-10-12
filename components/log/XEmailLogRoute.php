<?php
/**
 * XEmailLogRoute class
 *
 * This log route ignores 404 (Page Not Found) errors
 *
 * 'log' => array(
 *     'class' => 'CLogRouter',
 *     'routes' => array(
 *         array(
 *             'class' => 'ext.components.log.XEmailLogRoute',
 *             'filter' => array(
 *                 'class'=>'ext.components.log.XLogFilter',
 *                     'ignoreCategories' => array(
 *                         'exception.CHttpException.404',
 *                     ),
 *                 ),
 *                 'levels' => 'error',
 *                 'emails' => 'myemail@mydomain.com',
 *              ),
 *          ),
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