<?php
/**
 * XEveryPayWebhookAction class file.
 *
 * This action handles EveryPay webhook POST requests (form-encoded data),
 * validates the payment status using the specified EveryPay component,
 * and calls success or failure callbacks based on the result.
 *
 * Usage example in your controller:
 *
 * public function actions()
 * {
 *     return array(
 *         'everypayWebhook'=>array(
 *             'class'=>'ext.components.everypay.XEveryPayWebhookAction',
 *             'componentName'=>'creditcard', // name of the EveryPay component in Yii config
 *             'successCallback'=>'handleEveryPayPaymentSuccess', // method in controller to call on success
 *             'failureCallback'=>'handleEveryPayPaymentFailure', // method in controller to call on failure
 *         ),
 *     );
 * }
 *
 * @link https://support.every-pay.com/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XEveryPayWebhookAction extends CAction
{
	/**
	 * @var string The name of the EveryPay component as configured in Yii (e.g., 'everypay').
	 */
	public $componentName='everypay';

	/**
	 * @var string $successCallback The controller method called on successful validation.
	 * This method should accept one parameter: the array of parsed POST data.
	 */
	public $successCallback;

	/**
	 * @var string $failureCallback The controller method called on failure.
	 */
	public $failureCallback;

	/**
	 * @var bool $log Whether to log events and errors.
	 */
	public $log=false;

	/**
	 * @var string $logLevel The log level (e.g., 'trace', 'info', 'error').
	 */
	public $logLevel='error';

	/**
	 * @var string $logCategory The category for logging messages.
	 */
	public $logCategory = 'ext.components.everypay.XEveryPayWebhookAction';

	/**
	 * Main entry point: parse POST data, validate payment, call success/failure callbacks.
	 */
	public function run()
	{
		$webhookUid=uniqid('wh_', true);

		// Read the raw POST body
		$rawBody=@file_get_contents('php://input');

		// If there's no body at all, just ignore it (204 No Content)
		if(empty(trim($rawBody)))
		{
			http_response_code(204);
			return;
		}

		// Parse the form-encoded data
		$decoded=array();
		parse_str($rawBody, $decoded);

		// Check for required parameters
		if(empty($decoded['payment_reference']))
		{
			$logMessage=
				"Posted data is missing payment_reference: ".PHP_EOL.
				"rawBody: ".$rawBody;

			$this->log($logMessage);
			$this->handleFailure($logMessage, $webhookUid);
			http_response_code(400);
			return;
		}

		// Set request parameter so validatePayment() can find it
		if(isset($decoded['payment_reference']))
			$_REQUEST['payment_reference'] = $decoded['payment_reference'];

		// Get the configured EveryPay component
		$everyPay=Yii::app()->getComponent($this->componentName);
		if(!$everyPay)
		{
			$this->log('No EveryPay component found with name '.$this->componentName);
			http_response_code(500);
			return;
		}

		// Validate payment using the component
		if($everyPay->validatePayment())
		{
			// Payment is settled
			if($this->successCallback && method_exists($this->controller, $this->successCallback))
				$this->controller->{$this->successCallback}($everyPay->statusResponse, $webhookUid);
			else
				$this->log('No successCallback defined or not callable');

			http_response_code(200);
		}
		else
		{
			// Payment validation failed or not settled
			$logMessage=
				"EveryPay payment validation failed: ".PHP_EOL.
				"errorMessage: ".$everyPay->errorMessage;

			$this->handleFailure($logMessage, $webhookUid);
			http_response_code(200);
		}
	}

	/**
	 * Calls the failure callback if defined.
	 * @param string $message
	 * @param string $webhookUid
	 */
	protected function handleFailure($message, $webhookUid)
	{
		if($this->failureCallback && method_exists($this->controller, $this->failureCallback))
			$this->controller->{$this->failureCallback}($message, $webhookUid);
	}

	/**
	 * Logs a message if logging is enabled.
	 * @param string $message
	 */
	protected function log($message)
	{
		if($this->log===true)
			Yii::log(__CLASS__.' '.$message, $this->logLevel, $this->logCategory);
	}
}