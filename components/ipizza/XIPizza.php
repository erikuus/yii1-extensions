<?php
/**
 * XIPizza class file
 *
 * XIPizza component enables to submit and validate iPizza-based bank payment (SEB, Swedbank, etc)
 *
 * The following shows how to use XIPizza component:
 *
 * Configure component:
 * <pre>
 * 'components'=>array(
 *     'swedbank'=> array(
 *         'class'=>'ext.components.ipizza.XCommon',
 *         'serviceId'=>'1011',
 *         'serviceUrl'=>'https://www.swedbank.ee/banklink',
 *         'merchantId'=>'12345',
 *         'merchantAccount'=>'EE209900123456789012',
 *         'merchantName'=>'My Company',
 *         'certificatePath'=>'/path/to/swedbank.crt',
 *         'privateKeyPath'=>'/path/to/swedbank.key',
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
 *     $swedbank=Yii::app()->swedbank;
 *     $swedbank->requestId=Payment::model()->getRequestId();
 *     $swedbank->datetime=date(DATE_ISO8601);
 *     $swedbank->language=Yii::app()->language;
 *     $swedbank->amount=$product->price;
 *     $swedbank->message=Yii::app()->name.', '.Yii::app()->user->name.', '.$swedbank->requestId;
 *     $swedbank->returnUrl=Yii::app()->createAbsoluteUrl('validate',array('id'=>$payment->id));
 *     $swedbank->cancelUrl=Yii::app()->createAbsoluteUrl('cancel');
 *     $swedbank->submitPayment();
 * }
 * </pre>
 *
 * Validate payment in controller:
 * <pre>
 * public function actionValidate($id)
 * {
 *     if(Yii::app()->swedbank->validatePayment())
 *     {
 *         $payment=Payment::model()->findByPk($id);
 *         $payment->status=Payment::STATUS_VALID;
 *         if($payment->save())
 *             Yii::app()->user->setFlash('success', 'Payment was successful!');
 *         else
 *             Yii::app()->user->setFlash('error', 'Transaction was successful, but payment was not recorded.'));
 *     }
 *     else
 *         Yii::app()->user->setFlash('error', Yii::app()->swedbank->errorMessage);
 *
 *     $this->redirect('/controller/action');
 * }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
abstract class XIPizza extends CApplicationComponent
{
	/**
	 * Error codes
	 */
	const ERROR_NONE=0;
	const ERROR_TRANSACTION_FAILED=1;
	const ERROR_SIGNATURE_INVALID=2;
	const ERROR_UNKNOWN=3;

	/**
	 * User interface languages that can be converted to VK_LANG format
	 */
	const LANGUAGE_ET='et';
	const LANGUAGE_RU='ru';
	const LANGUAGE_EN='en';

	/**
	 * @var string $serviceUrl the iPizza payment service request target URL
	 */
	public $serviceUrl;
	/**
	 * @var integer $serviceId the iPizza payment service id
	 */
	public $serviceId;
	/**
	 * @var string $serviceVersion the iPizza payment service version
	 * Defaults to '008'
	 */
	public $serviceVersion='008';
	/**
	 * @var string $merchantId the id of merchant that sends payment request (available through payment service contract)
	 */
	public $merchantId;
	/**
	 * @var string $merchantAccount the bank account number of merchant that sends payment request
	 */
	public $merchantAccount;
	/**
	 * @var string $merchantName the name of merchant that sends payment request
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
	 * @var string $datetime Timestamp format [YYYYMMDDhhmmss] ISO-8601
	 */
	public $datetime;
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
	 * @var string $language the user interface language [et,ru,en]
	 */
	public $language;
	/**
	 * @var string $charEncoding the request character encoding. Defaults to 'UTF-8'
	 */
	public $charEncoding='UTF-8';
	/**
	 * @var string $certificate the path to https ssl certificate
	 */
	public $certificatePath;
	/**
	 * @var string $privateKey the path to https ssl private key
	 */
	public $privateKeyPath;
	/**
	 * @var string $privateKeyPass the passphrase must be used if the specified key is encrypted (protected by a passphrase).
	 */
	public $privateKeyPass;
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
	 * @var a valid string returned by openssl_get_md_methods() example, "SHA1" or "SHA512".
	 */
	public $algorithm='SHA1';

	/**
	 * Render form with hidden fields and autosubmit
	 */
	public function submitPayment()
	{
		$params=$this->getValidParams();
		$params[$this->getMacParamName()]=$this->getMacSignature();

		$file=dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'form.php';
		Yii::app()->controller->renderFile($file, array(
			'serviceUrl'=>$this->serviceUrl,
			'params'=>$params
		));
	}

	/**
	 * Validate IPizza payment.
	 * After a customer has completed their order,
	 * ipizza bank server will contact the script you provided in the "returnUrl"
	 * argument. Bank will POST the order information to your script
	 * and it's up to us to verify that it's a valid order.
	 * @return boolean whether payment validates
	 */
	public function validatePayment()
	{
		if(!isset($_REQUEST[$this->getServiceParamName()]))
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');

		// get data
		$serviceId=$_REQUEST[$this->getServiceParamName()];
		$data=$this->getMacString($serviceId, $_REQUEST);

		// get decoded mac signature
		$macSignature=$_REQUEST[$this->getMacParamName()];
		$macSignature=base64_decode($macSignature);

		// verify with public key
		$publicKey = openssl_pkey_get_public(file_get_contents($this->certificatePath));
		$signatureOK = openssl_verify($data, $macSignature, $publicKey, $this->algorithm);
		openssl_free_key($publicKey);

		// set error and return false/true
		if($signatureOK==1 && $serviceId==$this->getSuccessServiceId())
		{
			$this->errorCode=self::ERROR_NONE;
			return true;
		}
		else
		{
			if($signatureOK==1 && $serviceId==$this->getFailureServiceId())
			{
				$this->errorCode=self::ERROR_TRANSACTION_FAILED;
				$this->errorMessage=Yii::t('XIPizza.ipizza', 'Payment failed! Bank did not authorize the transaction.');
			}
			elseif ($signatureOK==0)
			{
				$this->errorCode=self::ERROR_SIGNATURE_INVALID;
				$this->errorMessage=Yii::t('XIPizza.ipizza', 'Payment failed! Could not authorize the merchant.');
			}
			else
			{
				$this->errorCode=self::ERROR_UNKNOWN;
				$this->errorMessage=Yii::t('XIPizza.ipizza', 'Payment failed!');
			}
			return false;
		}
	}

	/**
	 * Generates MAC signature
	 * @return signed data in base64 encoding
	 */
	protected function getMacSignature()
	{
		// construct data string
		$data=$this->getMacString($this->serviceId, $this->getValidParams());

		// sign with private key
		$privateKey=openssl_pkey_get_private(file_get_contents($this->privateKeyPath), $this->privateKeyPass);
		openssl_sign($data, $macSignature, $privateKey, $this->algorithm);
		openssl_free_key($privateKey);

		// return encoded
		return base64_encode($macSignature);
	}

	/**
	 * Generates the MAC data string
	 * @param integer service id
	 * @param array request params
	 * @return string
	 */
	protected function getMacString($serviceId, $params)
	{
		$macParamMap = $this->getMacParamMap();

		if(!isset($macParamMap[$serviceId]))
			throw new Exception("Unknown service: $serviceId");

		$str='';
		foreach($macParamMap[$serviceId] as $macParamName)
		{
			if(isset($params[$macParamName]))
			{
				$value=$params[$macParamName];
				$length=mb_strlen($value);
				$str.=str_pad($length,3,'0',STR_PAD_LEFT).$value;
			}
		}
		return $str;
	}

	/**
	 * @return string language code for payment request
	 */
	protected function getLanguageCode()
	{
		switch($this->language)
		{
			case self::LANGUAGE_ET:
				return 'EST';
			case self::LANGUAGE_RU:
				return 'RUS';
			case self::LANGUAGE_EN:
			default:
				return 'ENG';
		}
	}

	/**
	 * @return array params cut to allowed length
	 */
	protected function getValidParams()
	{
		$params=$this->getParams();
		$lengths=$this->getParamLengths();
		$validParams=array();
		foreach ($params as $name=>$value)
			$validParams[$name]=isset($lengths[$name]) ? mb_substr($value, 0, $lengths[$name]) : $value;
		return $validParams;
	}

	/**
	 * @return string mac param name
	 */
	abstract protected function getSuccessServiceId();

	/**
	 * @return string service id param name
	 */
	abstract protected function getFailureServiceId();

	/**
	 * @return string mac param name
	 */
	abstract protected function getMacParamName();

	/**
	 * @return string service id param name
	 */
	abstract protected function getServiceParamName();

	/**
	 * @return array params of payment request
	 */
	abstract protected function getParams();

	/**
	 * @return array param length
	 */
	abstract protected function getParamLengths();

	/**
	 * @return array mac params names by service id
	 */
	abstract protected function getMacParamMap();
}
?>