<?php
/**
 * XEveryPay component for handling EveryPay payments.
 *
 * Example usage in config/main.php:
 *
 * 'components' => array(
 *     'creditcard' => array(
 *         'class' => 'ext.components.everypay.XEveryPay',
 *         'apiUsername' => 'username',
 *         'apiSecret' => 'secret',
 *         'accountName' => 'account'
 *     )
 * )
 *
 * @link https://support.every-pay.com/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
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
	public $testMode=false;

	/**
	 * @var string Language code
	 */
	public $language;

	/**
	 * @var int Payment amount in cents (1.00 EUR=100)
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
	public $autoRedirect=true;

	/**
	 * @var string The Payment Link returned by EveryPay on successful creation
	 */
	public $paymentLink;

	/**
	 * @var string Final status returned by the payment status request
	 */
	public $paymentState;

	/**
	 * Whether to force request as auto
	 *
	 * @var boolean
	 */
	public $forceAutoRequest;

	/**
	 * @var array|null Stores the full decoded status response from EveryPay.
	 */
	public $statusResponse=null;

	/**
	 * Unused properties to maintain parity with XStripe
	 */
	public $cancelUrl;
	public $productName;
	public $productDescription;
	public $metadata;

	/**
	 * Unused properties to maintain parity with XEcom
	 */
	public $datetime;

	/**
	 * v4 Endpoints (One-off payments)
	 */
	protected $testUrlV4='https://igw-demo.every-pay.com/api/v4/payments/oneoff';
	protected $liveUrlV4='https://pay.every-pay.eu/api/v4/payments/oneoff';
	protected $testStatusUrlV4='https://igw-demo.every-pay.com/api/v4/payments/';
	protected $liveStatusUrlV4='https://pay.every-pay.eu/api/v4/payments/';

	/**
	 * Creates a payment on EveryPay using v4 (nonce + timestamp).
	 * On success, sets $this->paymentLink and optionally redirects there.
	 */
	public function submitPayment()
	{
		try
		{
			// Convert from cents to a float
			$amountFloat=number_format($this->amount / 100, 2, '.', '');

			// v4 requires:
			// - 'nonce' (uniqid)
			// - 'timestamp' (ISO8601)
			// - 'account_name'
			// - 'api_username'
			// - 'order_reference'
			$nonce    =uniqid();
			$timestamp=date('c'); // ISO 8601

			// Build request array
			$requestData=array(
				'api_username'    => $this->apiUsername,
				'account_name'    => $this->accountName,
				'amount'          => $amountFloat,
				'order_reference' => $this->requestId,
				'nonce'           => $nonce,
				'timestamp'       => $timestamp,
				'customer_url'    => $this->returnUrl,
				'locale'          => $this->language,
			);

			// If a currency is required or different from default
			if(!empty($this->currency))
				$requestData['currency']=$this->currency;

			// Determine correct endpoint
			$apiUrl=$this->testMode ? $this->testUrlV4 : $this->liveUrlV4;

			// Build x-www-form-urlencoded payload (per v4 docs)
			$postFields=http_build_query($requestData, '', '&', PHP_QUERY_RFC3986);

			// Initialize cURL
			$ch=curl_init($apiUrl);
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

			$responseData=curl_exec($ch);
			$curlError   =curl_error($ch);
			curl_close($ch);

			if($curlError)
			{
				$this->log('EveryPay cURL error: '.$curlError);
				throw new CHttpException(500);
			}

			// Parse JSON response
			$decoded=json_decode($responseData, true);
			if(!empty($decoded['payment_link']))
			{
				$this->paymentLink=$decoded['payment_link'];

				if($this->autoRedirect)
					Yii::app()->controller->redirect($this->paymentLink);
			}
			else
			{
				$this->log(
					'Could not create payment link.'.PHP_EOL.
					'Response data: '.var_export($responseData, true).PHP_EOL.
					'Request data: '.var_export($requestData, true)
				);

				throw new CHttpException(500);
			}

		}
		catch(Exception $e)
		{
			$this->log('XEveryPay submitPayment exception: ' .$e->getMessage());
			throw new CHttpException(500);
		}
	}

	/**
	 * Checks payment status via the v4 endpoint.
	 * Returns true if 'payment_state' == 'settled'.
	 * Otherwise, false with errorMessage set.
	 */
	public function validatePayment()
	{
		$paymentReference=Yii::app()->request->getParam('payment_reference');

		if(!$paymentReference)
		{
			$this->errorMessage='No payment_reference provided for validation.';
			$this->log($this->errorMessage);
			return false;
		}

		// Build status URL
		$statusUrlBase=$this->testMode ? $this->testStatusUrlV4 : $this->liveStatusUrlV4;
		$statusUrl=$statusUrlBase.urlencode($paymentReference).'?api_username=' . urlencode($this->apiUsername);

		try
		{
			$ch=curl_init($statusUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json'
			));
			curl_setopt($ch, CURLOPT_USERPWD, $this->apiUsername . ':' . $this->apiSecret);

			$responseData=curl_exec($ch);
			$curlError=curl_error($ch);
			curl_close($ch);

			if($curlError)
			{
				$this->errorMessage='EveryPay cURL GET error: ' . $curlError;
				$this->log($this->errorMessage);
				return false;
			}

			// Decode response
			$decoded=json_decode($responseData, true);

			// Store response for webhook action
			$this->statusResponse = $decoded;

			if(!isset($decoded['payment_state']))
			{
				$this->errorMessage='Unexpected EveryPay status response: ' . var_export($decoded, true);
				$this->log($this->errorMessage);
				return false;
			}

			// e.g. "settled", "failed", "abandoned", "outdated", etc.
			$this->paymentState=$decoded['payment_state'];

			if($this->paymentState==='settled')
				return true;
			else
			{
				$this->errorMessage=Yii::t('XEveryPay.everypay', 'Payment not completed!');
				return false;
			}

		}
		catch(Exception $e)
		{
			$this->errorMessage='Validate Payment Exception: ' . $e->getMessage();
			$this->log($this->errorMessage);
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

	/**
	 * Logs a message.
	 * @param string $message
	 */
	protected function log($message)
	{
		Yii::log(__CLASS__.' '.$message, CLogger::LEVEL_ERROR);
	}
}