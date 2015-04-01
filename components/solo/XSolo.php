<?php
/**
 * XSolo class file
 *
 * XSolo component enables to submit and validate bank payments based on Solo protocol (mainly used by Nordea bank)
 *
 * The following shows how to use XSolo component:
 *
 * Configure component:
 * <pre>
 * 'components'=>array(
 *     'nordea'=> array(
 *         'class'=>'ext.components.solo.XSolo',
 *         'serviceUrl'=>'https://netbank.nordea.com/pnbepay/query.jsp',
 *         'merchantId'=>'12345',
 *         'merchantAccount'=>'EE209900123456789012',
 *         'merchantName'=>'My Company',
 *         'macSecret'=>'qwerty',
 *     ),
 * )
 * </pre>
  *
 * Query unique request id in model:
 * <pre>
 * public function getRequestId()
 * {
 *     do {
 *         $requestId=date("Ym").rand(100000,999999);
 *     } while(self::model()->findByAttributes(array('request_id'=>$requestId))!==null);
 *
 *     return $requestId;
 * }
 * </pre>
 *
 * Submit payment in controller:
 * <pre>
 * public function actionSubmit($productId)
 * {
 *     $product=Product::model()->findByPk($id);
 *
 *     $payment=new Payment;
 *     $payment->user_id=Yii::app()->user->id;
 *     $payment->product_id=$product->id;
 *     $payment->status=Payment::STATUS_SUBMITTED;
 *     $payment->save();
 *
 *     $nordea=Yii::app()->nordea;
 *     $nordea->requestId=Payment::model()->getRequestId();
 *     $nordea->datetime=date(DATE_ISO8601);
 *     $nordea->language=Yii::app()->language;
 *     $nordea->amount=$product->price;
 *     $nordea->message=Yii::app()->name.', '.Yii::app()->user->name.', '.$nordea->requestId;
 *     $nordea->returnUrl=Yii::app()->createAbsoluteUrl('validate',array('id'=>$payment->id));
 *     $nordea->cancelUrl=Yii::app()->createAbsoluteUrl('cancel');
 *     $nordea->rejectUrl=Yii::app()->createAbsoluteUrl('reject');
 *     $nordea->submitPayment();
 * }
 * </pre>
 *
 * Validate payment in controller:
 * <pre>
 * public function actionValidate($id)
 * {
 *     if(Yii::app()->nordea->validatePayment())
 *     {
 *         $payment=Payment::model()->findByPk($id);
 *         $payment->status=Payment::STATUS_VALID;
 *         if($payment->save())
 *             Yii::app()->user->setFlash('success', 'Payment was successful!');
 *         else
 *             Yii::app()->user->setFlash('error', 'Transaction was successful, but payment was not recorded.'));
 *     }
 *     else
 *         Yii::app()->user->setFlash('error', Yii::app()->nordea->errorMessage);
 *
 *     $this->redirect('/controller/action');
 * }
 * </pre>
 *
 * @link http://www.nordea.ee/Teenused+%C3%A4rikliendile/Igap%C3%A4evapangandus/Maksete+kogumine/E-makse/1562142.html
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XSolo extends CApplicationComponent
{
	/**
	 * Error codes
	 */
	const ERROR_NONE=0;
	const ERROR_TRANSACTION_FAILED=1;
	const ERROR_SIGNATURE_INVALID=2;
	const ERROR_UNKNOWN=3;

	/**
	 * User interface languages that can be converted to SOLOPMT_LANGUAGE format
	 */
	const LANGUAGE_ET='et';
	const LANGUAGE_EN='en';

	/**
	 * Default hash function to use
	 */
	const DEFAULT_HASH_FUNCTION = 'md5';

	/**
	 * @var string $serviceUrl the Solo payment service request target URL
	 */
	public $serviceUrl;
	/**
	 * @var string $serviceVersion the Solo payment service version
	 * Defaults to '0003'
	 */
	public $serviceVersion='0003';
	/**
	 * @var string $keyVersion the Solo payment mac key version
	 * Defaults to '0001'
	 */
	public $keyVersion='0001';
	/**
	 * @var string $confirm the payment confirmation [YES|NO]
	 * Whether to ask for additional information on the return link and a control code.
	 * Defaults to 'YES'
	 */
	public $confirm='YES';
	/**
	 * @var string $merchantId the id of merchant that receives payment (available through payment service contract)
	 */
	public $merchantId;
	/**
	 * @var string $merchantAccount the bank account number of merchant that receives payment
	 */
	public $merchantAccount;
	/**
	 * @var string $merchantName the name of merchant that receives payment
	 */
	public $merchantName;
	/**
	 * @var string $requestId the id of payment request
	 */
	public $requestId;
	/**
	 * @var float $amount the amount of money to be paid
	 */
	public $amount;
	/**
	 * @var string $currency the payment currency ISO-4217. Defaults to 'EUR'
	 */
	public $currency='EUR';
	/**
	 * @var string $date the payment due date [EXPRESS|DD.MM.YYYY].
	 * If the due date is indicated as EXPRESS, the transfer from the service user to the service
	 * provider is effective immediately after the service user has accepted the payment.
	 * Defaults to 'EXPRESS'
	 */
	public $date='EXPRESS';
	/**
	 * @var string $reference the payment reference code
	 */
	public $reference;
	/**
	 * @var string $message the payment message
	 */
	public $message;
	/**
	 * @var string $returnUrl the url that bank server returns respons to
	 */
	public $returnUrl;
	/**
	 * @var string $cancelUrl the url that bank server returns to when payment is canceled
	 */
	public $cancelUrl;
	/**
	 * @var string $rejectUrl the url that bank server returns to when payment is rejected
	 */
	public $rejectUrl;
	/**
	 * @var string $language the user interface language [et|en].
	 */
	public $language;
    /**
     * @var string $macSecret the secret word used to generate mac code.
     */
    public $macSecret;
    /**
     * @var array the hash function used to generate mac code [sha1|md5].
     * Defaults to 'md5'
     */
    public $hashFunction = 'md5';
	/**
	 * @var integer the authentication error code. If there is an error, the error code will be non-zero.
	 * Defaults to 0, meaning no error.
	 */
	public $errorCode=self::ERROR_NONE;
	/**
	 * @var string the authentication error message. Defaults to empty.
	 */
	public $errorMessage;

	/**
	 * Render form with hidden fields and autosubmit
	 */
	public function submitPayment()
	{
		$params=$this->getValidParams();
		$params['SOLOPMT_MAC']=$this->getMac('submit', $this->getValidParams());

		$file=dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'form.php';
		Yii::app()->controller->renderFile($file, array(
			'serviceUrl'=>$this->serviceUrl,
			'params'=>$params
		));
	}

	/**
	 * Validate Solo payment.
	 * After a customer has completed their order,
	 * solo bank server will contact the script you provided in the "returnUrl"
	 * argument. Bank will POST the order information to your script
	 * and it's up to us to verify that it's a valid order.
	 * @return boolean whether payment validates
	 */
	public function validatePayment()
	{
		// get mac
		$mac=$this->getMac('validate', $_REQUEST);

		// set error and return false/true
		if ($mac===$_REQUEST['SOLOPMT_RETURN_MAC'] && isset($_REQUEST['SOLOPMT_RETURN_PAID']))
		{
			$this->errorCode=self::ERROR_NONE;
			return true;
		}
		else
		{
			if (!isset($_REQUEST['SOLOPMT_RETURN_PAID']))
			{
				$this->errorCode=self::ERROR_TRANSACTION_FAILED;
				$this->errorMessage=Yii::t('XSolo.solo', 'Payment failed! Bank did not authorize the transaction.');
			}
			elseif ($mac!==$_REQUEST['SOLOPMT_RETURN_MAC'])
			{
				$this->errorCode=self::ERROR_SIGNATURE_INVALID;
				$this->errorMessage=Yii::t('XSolo.solo', 'Payment failed! Could not authorize the merchant.');
			}
			else
			{
				$this->errorCode=self::ERROR_UNKNOWN;
				$this->errorMessage=Yii::t('XSolo.solo', 'Payment failed!');
			}
			return false;
		}
	}

	/**
	 * Generates the MAC data string
	 * @param string $requestType the key of getMacParamMap() array
	 * @param array request params
	 * @return string
	 */
	protected function getMac($requestType, $params)
	{
		// get mac params
		$macParamMap = $this->getMacParamMap();

		if (!isset($macParamMap[$requestType]))
			throw new Exception("Unknown service: $requestType");

		$macParams = array_intersect_key($params, array_flip($macParamMap[$requestType]));

		// build mac string
		$mac='';
		foreach($macParams as $value)
			$mac.=sprintf('%s&', $value ? $value : null);

		// add secret
		$mac.= $this->macSecret.'&';

		// hash
		$hashFunction=$this->hashFunction;
		$mac=strtoupper($hashFunction($mac));

		return $mac;
	}

	/**
	 * @return string language code for payment request
	 */
	protected function getLanguageCode()
	{
		switch($this->language)
		{
			case self::LANGUAGE_ET:
				return 4;
			case self::LANGUAGE_EN:
			default:
				return 3;
		}
	}

	/**
	 * Since solo supports only iso-8859-1 charsets, all other characters will be transliterated.
	 * @return array params cut to allowed length and transliterated
	 */
	protected function getValidParams()
	{
		$params=$this->getParams();
		$lengths=$this->getParamLengths();
		$validParams=array();
		foreach ($params as $name=>$value)
		{
			$value = isset($lengths[$name]) ? mb_substr($value, 0, $lengths[$name]) : $value;
			$value = iconv('utf-8', 'iso-8859-1//TRANSLIT', $value);
			$validParams[$name]=$value;
		}
		return $validParams;
	}

	/**
	 * @return array params of payment request (except MAC)
	 */
	protected function getParams()
	{
		return array(
			// start mac params
			'SOLOPMT_VERSION' => $this->serviceVersion,
			'SOLOPMT_STAMP' => $this->requestId,
			'SOLOPMT_RCV_ID' => $this->merchantId,
			'SOLOPMT_AMOUNT' => $this->amount,
			'SOLOPMT_REF' => $this->reference,
			'SOLOPMT_DATE' => $this->date,
			'SOLOPMT_CUR' => $this->currency,
			// end mac params
			'SOLOPMT_RCV_ACCOUNT' => $this->merchantAccount,
			'SOLOPMT_RCV_NAME' => $this->merchantName,
			'SOLOPMT_MSG' => $this->message,
			'SOLOPMT_RETURN' => $this->returnUrl,
			'SOLOPMT_CANCEL' => $this->cancelUrl,
		    'SOLOPMT_REJECT' => $this->rejectUrl,
			'SOLOPMT_LANGUAGE' => $this->getLanguageCode(),
			'SOLOPMT_KEYVERS' => $this->keyVersion,
			'SOLOPMT_CONFIRM' => $this->confirm,
		);
	}

	/**
	 * @return array param length
	 */
	protected function getParamLengths()
	{
		return array(
			'SOLOPMT_VERSION' => 4,
			'SOLOPMT_STAMP' => 20,
			'SOLOPMT_RCV_ID' => 15,
			'SOLOPMT_RCV_ACCOUNT' => 21,
			'SOLOPMT_RCV_NAME' => 30,
			'SOLOPMT_LANGUAGE' => 1,
			'SOLOPMT_AMOUNT' => 19,
			'SOLOPMT_REF' => 16,
			'SOLOPMT_TAX_CODE' => 28,
			'SOLOPMT_DATE' => 10,
			'SOLOPMT_MSG' => 210,
			'SOLOPMT_RETURN' => 256,
			'SOLOPMT_CANCEL' => 256,
			'SOLOPMT_REJECT' => 256,
			'SOLOPMT_MAC' => 32,
			'SOLOPMT_CONFIRM' => 3,
			'SOLOPMT_KEYVERS' => 4,
			'SOLOPMT_CUR' => 3,
			'SOLOPMT_RETURN_VERSION' => 4, // payment_response only
			'SOLOPMT_RETURN_STAMP' => 20, // payment_response only
			'SOLOPMT_RETURN_REF' => 16, // payment_response only
			'SOLOPMT_RETURN_PAID' => 24, // payment_response only
		);
	}

	/**
	 * @return array mac params names mapped by service id
	 */
	protected function getMacParamMap()
	{
		return array(
			'submit' => array(
				'SOLOPMT_VERSION',
				'SOLOPMT_STAMP',
				'SOLOPMT_RCV_ID',
				'SOLOPMT_AMOUNT',
				'SOLOPMT_REF',
				'SOLOPMT_DATE',
				'SOLOPMT_CUR'
			),
			'validate' => array(
				'SOLOPMT_RETURN_VERSION',
				'SOLOPMT_RETURN_STAMP',
				'SOLOPMT_RETURN_REF',
				'SOLOPMT_RETURN_PAID'
			)
		);
	}
}
?>