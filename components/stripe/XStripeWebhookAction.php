<?php
/**
 * XStripeWebhookAction class file.
 *
 * XStripeWebhookAction handles webhook POST requests from Stripe and passes the verified
 * event data to a specified callback function in the controller.
 *
 * To use, configure your Stripe webhook endpoint in the Stripe Dashboard and set the
 * 'webhookSecret' property to your Stripe webhook signing secret.
 *
 * Configure Webhooks in Stripe Dashboard:
 * - Go to Developers > Webhooks in your Stripe Dashboard.
 * - Click Add endpoint and enter your webhook URL (e.g., https://www.yourdomain.com/index.php/payment/webhook).
 * - Select the event types you want to listen to, such as checkout.session.completed.
 *
 * ```php
 * public function actions()
 * {
 *     return array(
 *         'stripeWebhook'=>array(
 *             'class'=>'ext.components.stripe.XStripeWebhookAction',
 *             'successCallback'=>'handlePostbackSuccess',
 *             'failureCallback'=>'handlePostbackFailure',
 *             'webhookSecret'=>'whsec_YourStripeSigningSecret'
 *         )
 *     );
 * }
 * ```
 *
 * @link https://www.stripe.com
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once(Yii::getPathOfAlias('ext.vendor.autoload').'.php');

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class XStripeWebhookAction extends CAction
{
	/**
	 * @var string $successCallback The name of the controller method called on successful event verification.
	 * The method should accept one parameter which will be the Stripe Event object.
	 */
	public $successCallback;
	/**
	 * @var string $failureCallback The name of the controller method called on failure.
	 */
	public $failureCallback;
	/**
	 * @var string $webhookSecret The Stripe webhook signing secret.
	 * You can find it in your Stripe Dashboard under Developers > Webhooks.
	 */
	public $webhookSecret;
	/**
	 * @var boolean $log Whether to log events and errors.
	 * Defaults to false.
	 */
	public $log=false;
	/**
	 * @var string $logLevel The level for log messages.
	 * One of: 'trace', 'info', 'profile', 'warning', 'error'.
	 * Defaults to 'error'.
	 */
	public $logLevel='error';

	/**
	 * @var string $logCategory The category for log messages.
	 * Defaults to 'ext.components.stripe.XStripeWebhookAction'.
	 */
	public $logCategory='ext.components.stripe.XStripeWebhookAction';

	/**
	 * Handles the Stripe webhook request.
	 * Verifies the signature, decodes the event object, and calls success or failure callbacks.
	 */
	public function run()
	{
		$webhookUid=uniqid('wh_', true);

		// Read the request body
		$body=@file_get_contents('php://input');

		// Retrieve Stripe signature from headers
		$sigHeader=isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : null;

		if($sigHeader===null)
		{
			$logMessage='Missing signature HTTP_STRIPE_SIGNATURE';
			$this->handleFailure($logMessage, $webhookUid);
			return;
		}

		// Attempt to construct and verify the event
		try
		{
			$event=Webhook::constructEvent(
				$body,
				$sigHeader,
				$this->webhookSecret
			);

			// Successfully verified
			$this->controller->{$this->successCallback}($event, $webhookUid);
			http_response_code(200);

		}
		catch(SignatureVerificationException $e)
		{
			// Invalid signature
			$logMessage='Signature verification failed: ' . $e->getMessage();
			$this->log($logMessage);
			$this->handleFailure($logMessage, $webhookUid);
			http_response_code(400);

		}
		catch(Exception $e)
		{
			// Other errors
			$logMessage='Error handling Stripe webhook: ' . $e->getMessage();
			$this->log($logMessage);
			$this->handleFailure($logMessage, $webhookUid);
			http_response_code(400);
		}
	}

	/**
	 * Handle failures by calling the failure callback if defined.
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