<?php
/**
 * XEveryPayWebhookAction class file.
 *
 * XEveryPayWebhookAction handles webhook POST requests from EveryPay
 * and passes the decoded data to a specified callback function in the controller.
 *
 * Usage example in your controller:
 *
 * public function actions()
 * {
 *     return array(
 *         'everypayWebhook'=>array(
 *             'class'=>'ext.components.everypay.XEveryPayWebhookAction',
 *             'successCallback'=>'handleEveryPaySuccess',
 *             'failureCallback'=>'handleEveryPayFailure',
 *             // optional secret token for verifying the request
 *             // 'secretToken' => 'abc123',
 *             'log'=>true,
 *             'logCategory'=>'payment.everypay.webhook'
 *         )
 *     );
 * }
 *
 * Then define handleEveryPaySuccess($data) and handleEveryPayFailure() in your controller.
 *
 * @link https://support.every-pay.com/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XEveryPayWebhookAction extends CAction
{
	/**
	 * @var string $successCallback The name of the controller method called on success.
	 * The method should accept 1 parameter: an associative array of the decoded request data.
	 */
	public $successCallback;

	/**
	 * @var string $failureCallback The name of the controller method called on failure.
	 */
	public $failureCallback;

	/**
	 * @var bool $log Whether to log events and errors.
	 * Defaults to false.
	 */
	public $log=false;

	/**
	 * @var string $logLevel The level for log messages (trace, info, warning, error, etc.).
	 * Defaults to 'error'.
	 */
	public $logLevel='error';

	/**
	 * @var string $logCategory The category for log messages.
	 * Defaults to 'ext.components.everypay.XEveryPayWebhookAction'.
	 */
	public $logCategory='ext.components.everypay.XEveryPayWebhookAction';

	/**
	 * @var string|null $secretToken (Optional) If set, we check for a matching header, param, or
	 * something else as a basic form of verification that this request is from EveryPay.
	 * Adjust usage below to match how you want to verify authenticity.
	 */
	public $secretToken;

	/**
	 * Run the action: parse EveryPay webhook request, call success or failure callback.
	 */
	public function run()
	{
		// Read the request body
		$rawBody = @file_get_contents('php://input');
		$this->log('EveryPay webhook raw body: ' . $rawBody, 'trace');

		// Optionally verify the request came from EveryPay checking a "secret token" in GET param
		if($this->secretToken!==null)
		{
			$token=Yii::app()->request->getParam('token');
			if($token!==$this->secretToken)
			{
				$this->log('Invalid token in EveryPay webhook request!', $this->logLevel);
				$this->handleFailure();
				http_response_code(400);
				return;
			}
		}

		// Decode JSON
		$decoded=json_decode($rawBody, true);
		if(json_last_error()!==JSON_ERROR_NONE)
		{
			$this->log('EveryPay webhook JSON parse error: ' . json_last_error_msg(), $this->logLevel);
			$this->handleFailure();
			// Send an HTTP status code
			http_response_code(400);
			return;
		}

		// Additional checks on the structure
		if(empty($decoded['payment_reference']) && empty($decoded['order_reference']))
		{
			$this->log('EveryPay webhook missing payment_reference/order_reference', $this->logLevel);
			$this->handleFailure();
			http_response_code(400);
			return;
		}

		// Call success callback
		if($this->successCallback && method_exists($this->controller, $this->successCallback))
		{
			$this->controller->{$this->successCallback}($decoded);
			http_response_code(200);
		}
		else
		{
			$this->log('No success callback defined or not callable', $this->logLevel);
			// Typically return 200 so EveryPay does not keep retrying infinitely.
			http_response_code(200);
		}
	}

	/**
	 * Logs a message if logging is enabled.
	 */
	protected function log($message, $level='info')
	{
		if($this->log===true)
			Yii::log($message, $level, $this->logCategory);
	}

	/**
	 * Handle failures by calling the failure callback if defined.
	 */
	protected function handleFailure()
	{
		if($this->failureCallback && method_exists($this->controller, $this->failureCallback))
			$this->controller->{$this->failureCallback}();
	}
}