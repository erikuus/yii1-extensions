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
 * public function handleEveryPayPaymentSuccess($data)
 * {
 *     // Handle successful payment logic here.
 * }
 *
 * public function handleEveryPayPaymentFailure()
 * {
 *     // Handle failure logic here.
 * }
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
		// Read the raw POST body
		$rawBody=@file_get_contents('php://input');
		$this->log('EveryPay webhook raw body: ' . $rawBody, 'trace');

		// Parse the form-encoded data
		$decoded=array();
		parse_str($rawBody, $decoded);
		$this->log('EveryPay webhook parsed form data: ' . var_export($decoded, true), 'trace');

		// Check for required parameters
		if(empty($decoded['payment_reference']) && empty($decoded['order_reference']))
		{
			$this->log('EveryPay webhook missing payment_reference/order_reference', 'error');
			$this->handleFailure();
			http_response_code(400);
			return;
		}

		// Set request parameters so validatePayment() can find them
		if(isset($decoded['payment_reference']))
			$_REQUEST['payment_reference'] = $decoded['payment_reference'];

		if(isset($decoded['order_reference']))
			$_REQUEST['order_reference'] = $decoded['order_reference'];

		// Get the configured EveryPay component
		$everyPay=Yii::app()->getComponent($this->componentName);
		if(!$everyPay)
		{
			$this->log("No EveryPay component found with name '{$this->componentName}'!", 'error');
			$this->handleFailure();
			http_response_code(500);
			return;
		}

		// Validate payment using the component
		if ($everyPay->validatePayment())
		{
			// Payment is settled
			if($this->successCallback && method_exists($this->controller, $this->successCallback))
				$this->controller->{$this->successCallback}($everyPay->statusResponse);
			else
				$this->log('No successCallback defined or not callable', $this->logLevel);

			http_response_code(200);
		}
		else
		{
			// Payment validation failed or not settled
			$this->log('EveryPay payment validation failed: ' . $everyPay->errorMessage, $this->logLevel);
			$this->handleFailure();
			http_response_code(200);
		}
	}

	/**
	 * Helper to log messages if logging is enabled.
	 * @param string $message
	 * @param string $level
	 */
	protected function log($message, $level = 'info')
	{
		if($this->log === true)
			Yii::log($message, $level, $this->logCategory);
	}

	/**
	 * Calls the failure callback if defined.
	 */
	protected function handleFailure()
	{
		if ($this->failureCallback && method_exists($this->controller, $this->failureCallback)) {
			$this->controller->{$this->failureCallback}();
		}
	}
}