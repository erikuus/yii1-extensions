<?php
/**
 * XEcom class file
 *
 * XEcom component enables to submit and validate credit card payment via E-Commerce Payment Gateway.
 *
 * The following shows how to use XEcom component:
 *
 * Configure component:
 * <pre>
 * 'components'=>array(
 *     'ecom'=> array(
 *         'class'=>'ext.components.ecom.XEcom',
 *         'serviceUrl'=>'https://pos.estcard.ee/test-pos/servlet/iPAYServlet',
 *         'merchantId'=>'318DC77DC8',
 *         'certificatePath'=>'/path/to/80_ecom.crt',
 *         'privateKeyPath'=>'/path/to/private.key',
 *     ),
 * )
 * </pre>
 *
 * Create requestId in model:
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
 * public function actionSubmitPayment($productId)
 * {
 *     $product=Product::model()->findByPk($id);
 *
 *     $payment=new Payment;
 *     $payment->user_id=Yii::app()->user->id;
 *     $payment->product_id=$product->id;
 *     $payment->status=Payment::STATUS_SUBMITTED;
 *     $payment->save();
 *
 *     $ecom=Yii::app()->ecom;
 *     $ecom->requestId = Payment::model()->getRequestId();
 *     $ecom->language = Yii::app()->language;
 *     $ecom->amount = $product->price*100;
 *     $ecom->returnUrl = Yii::app()->createAbsoluteUrl('validatePayment', array('id'=>$payment->id));
 *     $ecom->submitPayment();
 * }
 * </pre>
 *
 * Validate payment in controller:
 * <pre>
 * public function actionValidatePayment($id)
 * {
 *         if(Yii::app()->ecom->validatePayment())
 *         {
 *             $payment=Payment::model()->findByPk($id);
 *             $payment->status=Payment::STATUS_VALID;
 *             if($payment->save())
 *                 Yii::app()->user->setFlash('success', 'Payment was successful!');
 *             else
 *                 Yii::app()->user->setFlash('error', 'Transaction was successful, but payment was not recorded.'));
 *         }
 *         else
 *             Yii::app()->user->setFlash('error', Yii::app()->ecom->errorMessage);
 *
 *         $this->redirect('/controller/action');
 * }
 * </pre>
 *
 * @link http://www.estcard.ee/publicweb/files/ecomdevel/e-comDocEST.html
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XEcom extends CApplicationComponent
{
	/**
	 * Error codes
	 */
	const ERROR_NONE=0;
	const ERROR_TRANSACTION_FAILED=1;
	const ERROR_SIGNATURE_INVALID=2;
	const ERROR_UNKNOWN=3;

	/**
	 * @var string $serviceUrl iPay payment service request target URL
	 */
	public $serviceUrl;
	/**
	 * @var string $action iPay action name. Defaults to 'gaf'
	 */
 	public $action='gaf';
	/**
	 * @var integer $version iPay protocol version. Defaults to '004'
	 */
 	public $version='004';
	/**
	 * @var string $delivery delivery symbol. Defaults to 'S'
	 */
 	public $delivery='S';
	/**
	 * @var string $merchantId the id of merchant that sends payment request (available through payment service contract)
	 */
 	public $merchantId;
	/**
	 * @var integer $requestId the unique transaction number as time stamp [YYYYMM] + random number between 100000-999999
	 */
	public $requestId;
	/**
	 * @var integer $amount Payment amount in cents
	 */
 	public $amount;
	/**
	 * @var string $currency Payment currency ISO-4217. Defaults to 'EUR'
	 */
 	public $currency='EUR';
	/**
	 * @var string $datetime Timestamp format [YYYYMMDDhhmmss] ISO-8601
	 */
 	public $datetime;
	/**
	 * @var string $returnUrl the url that bank server returns respons to
	 */
 	public $returnUrl;
	/**
	 * @var string $language interface language ISO 639-1
	 */
	public $language;
	/**
	 * @var string $charEncoding character encoding. Defaults to 'UTF-8'
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
	 * @var boolean whether to force request as auto (for testing only)
	 */
	public $forceAutoRequest;

	// Unused properties to maintain parity with XStripe
	public $cancelUrl;
	public $productName;
	public $productDescription;
	public $productMetadata=array();

	/**
	 * Render form with hidden fields and autosubmit
	 */
	public function submitPayment()
	{
		$file=dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'form.php';

		Yii::app()->controller->renderFile($file, array(
			'serviceUrl'=>$this->serviceUrl,
			'params'=>array(
				'id'=>$this->merchantId,
				'action'=>$this->action,
				'ver'=>$this->version,
				'delivery'=>$this->delivery,
				'charEncoding'=>$this->charEncoding,
				'cur'=>$this->currency,
				'lang'=>$this->language,
				'datetime'=>$this->datetime,
				'feedBackUrl'=>$this->returnUrl,
				'eamount'=>sprintf("%012s", $this->amount),
				'ecuno'=>$this->requestId,
				'mac'=>$this->getMacSignature(),
			)
		));
	}

	/**
	 * Get signed data
	 * @return signed data in HEX format
	 */
	protected function getMacSignature()
	{
		$data=
			$this->version .
			sprintf("%-10s", $this->merchantId) .
			sprintf("%012s", $this->requestId) .
			sprintf("%012s", $this->amount) .
			$this->currency .
			$this->datetime .
			sprintf("%-128s", $this->returnUrl) .
			$this->delivery;

		// sign with private key
		$privateKey=openssl_pkey_get_private(file_get_contents($this->privateKeyPath), $this->privateKeyPass);
		openssl_sign($data, $macSignature, $privateKey);
		openssl_free_key($privateKey);

		// convert to hex
		$macSignature=bin2hex($macSignature);

		return $macSignature;
	}

	/**
	 * Validate E-Commerce Payment Gateway feedback.
	 * After a customer has completed their order through E-Commerce Payment Gateway,
	 * E-Commerce Payment Gateway will contact the script you provided in the "returnUrl"
	 * argument. E-Commerce Payment Gateway will POST the order information to your script
	 * and it's up to us to verify that it's a valid order.
	 * @return boolean whether payment validates
	 */
	public function validatePayment()
	{
		// get data
		$data =
			sprintf("%03s", $_POST['ver']) .
			sprintf("%-10s", $_POST['id']) .
			sprintf("%012s", $_POST['ecuno']) .
			sprintf("%06s", $_POST['receipt_no']) .
			sprintf("%012s", $_POST['eamount']) .
			sprintf("%3s", $_POST['cur']) .
			$_POST['respcode'] .
			$_POST['datetime'] .
			$this->mb_sprintf("%-40s",$_POST['msgdata']) .
			$this->mb_sprintf("%-40s", $_POST['actiontext']);

		// get mac
		$macSignature = $this->hex2str($_POST['mac']);

		// verify with public key
		$publicKey = openssl_pkey_get_public(file_get_contents($this->certificatePath));
		$signatureOK = openssl_verify($data, $macSignature, $publicKey);
		openssl_free_key($publicKey);

		// set error and return false/true
		if ($signatureOK==1)
		{
			if($_POST['respcode']==000)
			{
				$this->errorCode=self::ERROR_NONE;
				return true;
			}
			else
			{
				$this->errorCode=self::ERROR_TRANSACTION_FAILED;
				$this->errorMessage=Yii::t('XEcom.ecom', 'Payment failed! Bank did not authorize the transaction.');
				return false;
			}
		}
		else
		{
			if ($signatureOK==0)
			{
				$this->errorCode=self::ERROR_SIGNATURE_INVALID;
				$this->errorMessage=Yii::t('XEcom.ecom', 'Payment failed! Could not authorize the merchant.');
			}
			else
			{
				$this->errorCode=self::ERROR_UNKNOWN;
				$this->errorMessage=Yii::t('XEcom.ecom', 'Payment failed!');
			}
			return false;
		}
	}

	/**
	 * Check whether it is automated request
	 * @return boolean whether it is automated request
	 */
	public function isAutoRequest()
	{
		if($this->forceAutoRequest===true)
			return true;
		else
			return isset($_REQUEST['auto']) && $_REQUEST['auto']=='Y';
	}

	/**
	 * Convert hexcode to string
	 * @param $hex the mac signature in hexdecimal format
	 * @return string mac signature
	 */
	protected function hex2str($hex)
	{
		$str='';
		for($i=0;$i<strlen($hex);$i+=2)
			$str.=chr(hexdec(substr($hex,$i,2)));
		return $str;
	}

	/**
	 * Multibyte safe sprintf
	 * @param $format the format string is composed of zero or more directives
	 * @return string produced according to the formatting
	 */
	protected function mb_sprintf($format)
	{
		$argv = func_get_args() ;
		array_shift($argv) ;
		return $this->mb_vsprintf($format, $argv);
	}

	/**
	 * Multibyte safe vsprintf
	 */
	protected function mb_vsprintf($format, $argv, $encoding=null)
	{
		if(is_null($encoding))
			$encoding=mb_internal_encoding();

		// Use UTF-8 in the format so we can use the u flag in preg_split
		$format=mb_convert_encoding($format,'UTF-8',$encoding);

		$newformat=""; // build a new format in UTF-8
		$newargv=array(); // unhandled args in unchanged encoding

		while($format!=="")
		{
			// Split the format in two parts: $pre and $post by the first %-directive
			// We get also the matched groups
			list($pre,$sign,$filler,$align,$size,$precision,$type,$post)=preg_split("!\%(\+?)('.|[0 ]|)(-?)([1-9][0-9]*|)(\.[1-9][0-9]*|)([%a-zA-Z])!u",$format,2,PREG_SPLIT_DELIM_CAPTURE);

			$newformat.=mb_convert_encoding($pre,$encoding,'UTF-8');

			if($type=='')
			{
				// didn't match. do nothing. this is the last iteration.
			}
			elseif($type=='%')
			{
				// an escaped %
				$newformat.='%%';
			}
			elseif($type=='s')
			{
				$arg=array_shift($argv);
				$arg=mb_convert_encoding($arg,'UTF-8',$encoding);
				$padding_pre='';
				$padding_post='';

				// truncate $arg
				if($precision!=='')
				{
					$precision=intval(substr($precision,1));
					if($precision>0 && mb_strlen($arg,$encoding)>$precision)
						$arg=mb_substr($precision,0,$precision,$encoding);
				}

				// define padding
				if($size>0)
				{
					$arglen=mb_strlen($arg,$encoding);
					if($arglen<$size)
					{
						if($filler==='')
							$filler=' ';
						if($align=='-')
							$padding_post=str_repeat($filler,$size-$arglen);
						else
							$padding_pre=str_repeat($filler,$size-$arglen);
					}
				}

				// escape % and pass it forward
				$newformat.=$padding_pre.str_replace('%','%%',$arg).$padding_post;
			}
			else
			{
				// another type, pass forward
				$newformat.="%$sign$filler$align$size$precision$type";
				$newargv[]=array_shift($argv);
			}
			$format=strval($post);
		}
		// Convert new format back from UTF-8 to the original encoding
		$newformat=mb_convert_encoding($newformat,$encoding,'UTF-8');
		return vsprintf($newformat,$newargv);
	}
}
?>
