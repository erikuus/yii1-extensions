<?php
/**
 * XLogFilter class
 *
 * This log filter ignores 404 (Page Not Found) errors.
 * It is meant to be used together with XEmailLogRoute
 *
 * Configuration example:
 *
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
class XLogFilter extends CLogFilter
{
	public $ignoreCategories; // =array('category','category.*','some.category.tree.*');
	public $ignorePatterns; // =array('SQLSTATE[23505]', '/regex pattern/');

	public function filter(&$logs)
	{
		// unset categories marked as "ignored"
		if($logs)
		{
			foreach($logs as $logKey=>$log)
			{
				$logCategory=$log[2]; //log category
				$logMessage=$log[0]; //log message
				
				// Check if category should be ignored
				if($this->ignoreCategories)
				{
					foreach($this->ignoreCategories as $nocat)
					{
						// Exact match
						if($logCategory===$nocat)
						{
							unset($logs[$logKey]);
							continue 2; // Skip to next log entry
						}
						// Wildcard match
						else if(strpos($nocat,'.*')!==false)
						{
							$nocat=str_replace('.*','',$nocat).'.'; // remove asterix item from array and add dot at the and
							if(strpos($logCategory.'.',$nocat)!==false)
							{
								unset($logs[$logKey]);
								continue 2; // Skip to next log entry
							}
						}
					}
				}
				
				// Check if message matches ignore patterns
				if($this->ignorePatterns)
				{
					foreach($this->ignorePatterns as $pattern)
					{
						// Regex pattern (starts and ends with /)
						if(preg_match('/^\/.*\/$/', $pattern))
						{
							if(preg_match($pattern, $logMessage))
							{
								unset($logs[$logKey]);
								continue 2; // Skip to next log entry
							}
						}
						// Simple substring match
						else if(strpos($logMessage, $pattern) !== false)
						{
							unset($logs[$logKey]);
							continue 2; // Skip to next log entry
						}
					}
				}
			}
		}
		$this->format($logs);
		return $logs;
	}
}