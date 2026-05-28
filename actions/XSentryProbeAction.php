<?php
/**
 * XSentryProbeAction
 *
 * This action triggers predictable probe failures so Sentry integration can be verified
 * without adding one-off controller code.
 *
 * The following shows how to use XSentryProbeAction action.
 *
 * First set up the action on SiteController actions() method:
 * <pre>
 * return array(
 *     'sentryProbe'=>array(
 *         'class'=>'ext.actions.XSentryProbeAction',
 *         // Required shared secret. Requests without this token receive 404.
 *         'token'=>'replace-with-a-long-random-string',
 *         // Optional default used when "scenario" is omitted from the query string.
 *         'defaultScenario'=>'type_mismatch',
 *     ),
 * );
 * </pre>
 *
 * Example calls:
 * <pre>
 * // Triggers a validation exception because "value" is not an integer.
 * /site/sentryProbe?token=replace-with-a-long-random-string&scenario=type_mismatch&value=abc
 *
 * // Triggers a validation exception because negative integers are rejected.
 * /site/sentryProbe?token=replace-with-a-long-random-string&scenario=type_mismatch&value=-1
 *
 * // Always throws the same exception, useful for a simple smoke test.
 * /site/sentryProbe?token=replace-with-a-long-random-string&scenario=constant_exception
 *
 * // Returns HTTP 200 with an empty body because the probe input is valid.
 * /site/sentryProbe?token=replace-with-a-long-random-string&scenario=type_mismatch&value=0
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XSentryProbeAction extends CAction
{
	/**
	 * @var string shared secret that must match the "token" query parameter.
	 */
	public $token;
	/**
	 * @var string scenario used when the request does not provide one.
	 */
	public $defaultScenario='type_mismatch';

	/**
	 * Runs the action.
	 */
	public function run()
	{
		if(empty($this->token))
			throw new CHttpException(404,'The requested page does not exist.');

		$token=Yii::app()->request->getQuery('token','');
		if(!hash_equals($this->token,$token))
			throw new CHttpException(404,'The requested page does not exist.');

		$scenario=Yii::app()->request->getQuery('scenario',$this->defaultScenario);
		$value=Yii::app()->request->getQuery('value','');

		switch($scenario)
		{
			case 'type_mismatch':
				$this->probeTypeMismatch($value);
				return;

			case 'constant_exception':
				throw new CException('Sentry probe constant exception');

			default:
				throw new CHttpException(400,'Invalid probe scenario.');
		}
	}

	/**
	 * Validates the probe input and throws exceptions for known bad values.
	 * @param string $value raw query parameter value
	 */
	protected function probeTypeMismatch($value)
	{
		if(!preg_match('/^-?\d+$/',$value))
			throw new CException('Probe failure: expected integer parameter "value".');

		$intValue=(int)$value;

		if($intValue < 0)
			throw new CException('Probe failure: integer parameter "value" must be non-negative.');
	}
}
