<?php
/**
 * EveryPay v4 Payment Component for Yii1
 *
 * Example usage in config/main.php:
 *
 * 'components' => array(
 *     'creditcard' => array(
 *         'class'       => 'application.components.everypay.XEveryPay',
 *         'apiUsername' => 'username',
 *         'apiSecret'   => 'secret',
 *         'accountName' => 'account',
 *         'testMode'    => true,
 *     ),
 *     // ...
 * ),
 *
 * Then in PaymentBaseController you can do:
 *   $transaction = Yii::app()->creditcard; // now points to XEveryPay
 *   $transaction->language = Yii::app()->language;
 *   $transaction->amount = $payment->total * 100;  // cents
 *   $transaction->currency = $payment->currency;
 *   $transaction->returnUrl = $this->createAbsoluteUrl('validate', array('id'=>$payment->id,'type'=>$payment->type));
 *   $transaction->cancelUrl = $this->createAbsoluteUrl('cancel', array('id'=>$payment->id));
 *   $transaction->requestId = $payment->request_id; // set your unique request ID
 *   $transaction->submitPayment();
 *
 * Then in actionValidate:
 *   $transaction = Yii::app()->creditcard;
 *   if($transaction->validatePayment()) {
 *       // Payment success
 *   } else {
 *       // Payment failure: $transaction->errorMessage has reason
 *   }
 *
 */
class XEveryPay extends CApplicationComponent
{
	/**
	 * @var string EveryPay API Username (from portal)
	 */
	public $apiUsername;

	/**
	 * @var string EveryPay API Secret (from portal)
	 */
	public $apiSecret;

	/**
	 * @var string The "account_name" in your EveryPay portal (e.g. "EUR3D1")
	 */
	public $accountName;

	/**
	 * @var bool If true, uses test (demo) endpoints; otherwise uses live endpoints
	 */
	public $testMode = false;

	/**
	 * @var string Language code
	 */
	public $language;

	/**
	 * @var int Payment amount in cents (1.00 EUR = 100)
	 */
	public $amount;

	/**
	 * @var string Payment currency (e.g. "EUR")
	 */
	public $currency;

	/**
	 * @var string Return URL (customer_url) after payment success or fail
	 */
	public $returnUrl;

	/**
	 * @var string Unique request ID that we use for order_reference
	 */
	public $requestId;

	/**
	 * @var string Holds errors if submission or validation fails
	 */
	public $errorMessage;

	/**
	 * @var bool If true, automatically redirects to the Payment URL after creation
	 */
	public $autoRedirect = true;

	/**
	 * @var string The Payment Link returned by EveryPay on successful creation
	 */
	public $paymentLink;

	/**
	 * @var string Final status returned by the payment status request
	 */
	public $paymentState;

	/**
	 * @var array Additional metadata we embed into integration_details
	 */
	public $metadata = array();

	/**
	 * Whether to force request as auto
	 *
	 * @var boolean
	 */
	public $forceAutoRequest;	

	/**
	 * Unused properties to maintain parity with XStripe
	 */
	public $cancelUrl;
	public $productName;
	public $productDescription;

	/**
	 * Unused properties to maintain parity with XEcom
	 */
	public $datetime;

	/**
	 * v4 Endpoints (One-off payments)
	 */
	protected $testUrlV4       = 'https://igw-demo.every-pay.com/api/v4/payments/oneoff';
	protected $liveUrlV4       = 'https://pay.every-pay.eu/api/v4/payments/oneoff';
	protected $testStatusUrlV4 = 'https://igw-demo.every-pay.com/api/v4/payments/';
	protected $liveStatusUrlV4 = 'https://pay.every-pay.eu/api/v4/payments/';

	/**
	 * Creates a payment on EveryPay using v4 (nonce + timestamp).
	 * On success, sets $this->paymentLink and optionally redirects there.
	 */
	public function submitPayment()
	{
		try
		{
			// Convert from cents to a float
			$amountFloat = number_format($this->amount / 100, 2, '.', '');

			// v4 requires:
			// - 'nonce' (uniqid)
			// - 'timestamp' (ISO8601)
			// - 'account_name'
			// - 'api_username'
			// - 'order_reference'
			$nonce     = uniqid();
			$timestamp = date('c'); // ISO 8601

			// Build request array
			$requestData = array(
				'api_username'    => $this->apiUsername,
				'account_name'    => $this->accountName,
				'amount'          => $amountFloat,
				'order_reference' => $this->requestId,
				'nonce'           => $nonce,
				'timestamp'       => $timestamp,
				'customer_url'    => $this->returnUrl,
			);

			// If a currency is required or different from default
			if(!empty($this->currency))
				$requestData['currency'] = $this->currency;

			// Store metadata in integration_details
			$requestData['integration_details'] = $this->metadata;

			// Determine correct endpoint
			$apiUrl = $this->testMode ? $this->testUrlV4 : $this->liveUrlV4;

			// Build x-www-form-urlencoded payload (per v4 docs)
			$postFields = http_build_query($requestData, '', '&', PHP_QUERY_RFC3986);

			// Initialize cURL
			$ch = curl_init($apiUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: application/json',
				'Content-Length: ' . strlen($postFields),
			));
			curl_setopt($ch, CURLOPT_USERPWD, $this->apiUsername . ':' . $this->apiSecret);

			$responseData = curl_exec($ch);
			$curlError    = curl_error($ch);
			curl_close($ch);

			if($curlError)
			{
				$this->errorMessage = 'EveryPay cURL error: ' . $curlError;
				Yii::log($this->errorMessage, CLogger::LEVEL_ERROR);
				return;
			}

			// Parse JSON response
			$decoded = json_decode($responseData, true);
			if (!empty($decoded['payment_link']))
			{
				$this->paymentLink = $decoded['payment_link'];

				if ($this->autoRedirect)
					Yii::app()->controller->redirect($this->paymentLink);
			}
			else
			{
				// Something went wrong
				$this->errorMessage = isset($decoded['error_message'])
					? $decoded['error_message']
					: 'Could not create payment link.';

				Yii::log(
					'XEveryPay submitPayment error: ' . var_export($decoded, true),
					CLogger::LEVEL_ERROR
				);
			}

		}
		catch (Exception $e)
		{
			$this->errorMessage = $e->getMessage();
			Yii::log(
				'XEveryPay submitPayment exception: ' . $e->getMessage(),
				CLogger::LEVEL_ERROR
			);
		}
	}

	/**
	 * Checks payment status via the v4 endpoint.
	 * Returns true if 'payment_state' == 'settled'.
	 * Otherwise, false with errorMessage set.
	 */
	public function validatePayment()
	{
		// Typically we expect EveryPay to pass payment_reference or order_reference in GET/POST
		$paymentReference = Yii::app()->request->getParam('payment_reference');

		if(!$paymentReference)
			$paymentReference = Yii::app()->request->getParam('order_reference');

		if(!$paymentReference)
		{
			$this->errorMessage = 'No payment_reference provided for validation.';
			return false;
		}

		// Build status URL
		$statusUrlBase = $this->testMode ? $this->testStatusUrlV4 : $this->liveStatusUrlV4;
		$statusUrl     = $statusUrlBase . urlencode($paymentReference) . '?api_username=' . urlencode($this->apiUsername);

		try
		{
			$ch = curl_init($statusUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json'
			));
			curl_setopt($ch, CURLOPT_USERPWD, $this->apiUsername . ':' . $this->apiSecret);

			$responseData = curl_exec($ch);
			$curlError    = curl_error($ch);
			curl_close($ch);

			if($curlError)
			{
				$this->errorMessage = 'EveryPay cURL GET error: ' . $curlError;
				Yii::log($this->errorMessage, CLogger::LEVEL_ERROR);
				return false;
			}

			// Decode response
			$decoded = json_decode($responseData, true);
			if(!isset($decoded['payment_state']))
			{
				$this->errorMessage = 'Unexpected EveryPay status response: ' . var_export($decoded, true);
				Yii::log($this->errorMessage, CLogger::LEVEL_ERROR);
				return false;
			}

			// e.g. "settled", "failed", "abandoned", "outdated", etc.
			$this->paymentState = $decoded['payment_state'];

			if($this->paymentState === 'settled')
				return true;
			else
			{
				$this->errorMessage = 'EveryPay Payment State: ' . $this->paymentState;
				return false;
			}

		}
		catch (Exception $e)
		{
			$this->errorMessage = 'Validate Payment Exception: ' . $e->getMessage();
			Yii::log($this->errorMessage, CLogger::LEVEL_ERROR);
			return false;
		}
	}

	/**
	 * In XEcom and XIpizza, isAutoRequest indicates if request was initiated automatically after successful payment.
	 * For Stripe Checkout, no automated background calls are made directly to `validate`.
	 * As we can not use webhooks in dev, we can force autorequest flag so that our app handles everything with one request.
	 */
	public function isAutoRequest()
	{
		if($this->forceAutoRequest===true)
			return true;
		else
			return false;
	}
}