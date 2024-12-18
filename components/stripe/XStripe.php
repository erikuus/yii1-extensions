<?php

require_once(Yii::getPathOfAlias('application.vendor.autoload').'.php'); // Include Composer autoload if needed

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;

/**
 * XStripe component for handling Stripe payments.
 */
class XStripe extends CApplicationComponent
{
    /**
     * Stripe secret API key.
     *
     * @var string
     */
    public $apiKey;

    /**
     * Stripe publishable key (used on the client side if needed).
     *
     * @var string
     */
    public $publishableKey;

    /**
     * Optional webhook secret for validating Stripe webhooks.
     *
     * @var string|null
     */
    public $webhookSecret;

    /**
     * URL to redirect after a successful payment.
     *
     * @var string
     */
    public $returnUrl;

    /**
     * URL to redirect if the user cancels the payment.
     *
     * @var string
     */
    public $cancelUrl;

    /**
     * Payment amount in cents.
     *
     * @var int
     */
    public $amount;

    /**
     * Currency code, e.g., 'EUR'.
     *
     * @var string
     */
    public $currency;

    /**
     * Language code for localization (e.g., 'en', 'de').
     *
     * @var string
     */
    public $language;

    /**
     * Name of the product displayed on Stripe Checkout.
     *
     * @var string
     */
    public $productName;

    /**
     * Description of the product displayed on Stripe Checkout.
     *
     * @var string
     */
    public $productDescription;

    /**
     * Metadata associated with the product for additional information.
     *
     * @var array
     */
    public $productMetadata = [];

    /**
     * Error message from the last operation, if any.
     *
     * @var string|null
     */
    public $errorMessage;

    // Unused properties to maintain parity with XEcom
    public $requestId;
    public $datetime;

    /**
     * Initializes the component by setting the Stripe API key.
     */
    public function init()
    {
        parent::init();
        Stripe::setApiKey($this->apiKey);
    }

    /**
     * Creates a Stripe Checkout Session and redirects the user to Stripe's Checkout page.
     *
     * @throws CHttpException if there is an error creating the Checkout Session.
     */
    public function submitPayment()
    {
        try {
            $separator = (parse_url($this->returnUrl, PHP_URL_QUERY) === null) ? '?' : '&';

            // Create a Checkout Session
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $this->currency,
                        'unit_amount' => $this->amount,
                        'product_data' => [
                            'name' => $this->productName ?: 'Order',
                            'description' => $this->productDescription,
                            'metadata' => $this->productMetadata,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->returnUrl . $separator . 'session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->cancelUrl,
                'locale' => $this->language ?: 'en'
            ]);

            // Redirect the user to Stripe Checkout
            Yii::app()->controller->redirect($session->url);
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            Yii::log('Stripe Payment Error: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
            throw new CHttpException(500, 'Payment processing error');
        }
    }

    /**
     * Validates the payment after returning from Stripe Checkout.
     *
     * @return bool True if the payment is successful, false otherwise.
     */
    public function validatePayment()
    {
        // We expect a `session_id` from Stripe return URL
        $sessionId = Yii::app()->request->getParam('session_id');

        if (!$sessionId) {
            $this->errorMessage = 'Missing session_id for payment validation.';
            return false;
        }

        try {
            // Retrieve the session
            $session = StripeSession::retrieve($sessionId);

            // The session contains a payment_intent if the payment succeeded or is pending
            if (empty($session->payment_intent)) {
                $this->errorMessage = 'No payment intent found for this session.';
                return false;
            }

            $paymentIntent = PaymentIntent::retrieve($session->payment_intent);

            // Check payment status
            if ($paymentIntent->status === 'succeeded') {
                return true;
            } else {
                $this->errorMessage = 'Payment not successful. Status: ' . $paymentIntent->status;
                return false;
            }
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            Yii::log('Stripe Validation Error: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * In old XEcom, isAutoRequest indicates if request was initiated automatically after successful payment.
     * For Stripe Checkout, no automated background calls are made directly to `validate` (unless using webhooks).
     * Return true to keep compatibility.
     */
    public function isAutoRequest()
    {
        return true;
    }
}