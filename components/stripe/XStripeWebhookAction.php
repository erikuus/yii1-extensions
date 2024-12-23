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
 * In your controller:
 *
 * ```php
 * public function handlePostbackSuccess($event)
 * {
 *     // $event is a Stripe\Event object
 *     // Process the event as needed, for example:
 *     if($event->type === 'checkout.session.completed')
 *     {
 *         $session = $event->data->object;
 *         // Retrieve your custom data from metadata
 *         $session->metadata->payment_id
 *         // Update payment status in your database
 *     }
 * }
 *
 * public function handlePostbackFailure()
 * {
 *     // Handle failures, e.g., log or notify
 * }
 * ```
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
        // Read the request body
        $body=@file_get_contents('php://input');


        // Retrieve Stripe signature from headers
        $sigHeader=isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : null;

        if($sigHeader===null)
        {
            $this->handleFailure();
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
            $this->controller->{$this->successCallback}($event);
            http_response_code(200);

        }
        catch(SignatureVerificationException $e)
        {
            // Invalid signature
            $this->log('Signature verification failed: ' . $e->getMessage());
            $this->handleFailure();
            http_response_code(400);

        }
        catch(Exception $e)
        {
            // Other errors
            $this->log('Error handling Stripe webhook: ' . $e->getMessage());
            $this->handleFailure();
            http_response_code(400);
        }
    }

    /**
     * Handle failures by calling the failure callback if defined.
     */
    protected function handleFailure()
    {
        if($this->failureCallback && method_exists($this->controller, $this->failureCallback))
            $this->controller->{$this->failureCallback}();
    }

    /**
     * Logs a message if logging is enabled.
     * @param string $message
     */
    protected function log($message)
    {
        if($this->log===true)
            Yii::log($message, $this->logLevel, $this->logCategory);
    }
}