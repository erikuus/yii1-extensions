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
 *         'serviceId'=>'B51D77CEE0',
 *         'certificatePath'=>'/path/to/80_ecom.crt',
 *         'privateKeyPath'=>'/path/to/private.key',
 *     ),
 * )
 * </pre>
 *
 * Render pay button:
 * <pre>
 * $ecom = Yii::app()->ecom;
 * $ecom->lang = Yii::app()->language;
 * $ecom->datetime = date("YmdHis");
 * $ecom->eamount = $price * 100;
 * $ecom->feedBackUrl = Yii::app()->createAbsoluteUrl('pay');
 * $ecom->renderPayButton();
 * </pre>
 *
 * Validate payment:
 * <pre>
 * public function actionPay()
 * {
 *     if(Yii::app()->request->isPostRequest && Yii::app()->ecom->validatePayment())
 *         // Update database and display message that payment was successful
 *     else
 *         // Log request and display message that payment failed
 *
 *     // redirect or render view
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
	 * @var string $serviceUrl iPay payment service request target URL
	 */
	public $serviceUrl;
	/**
	 * @var string $serviceId your web service id (available through payment service contract)
	 */
 	public $serviceId;
	/**
	 * @var string $privateKey the path to https ssl private key
	 */
	public $privateKeyPath;
	/**
	 * @var string $certificate the path to https ssl certificate
	 */
	public $certificatePath;
	/**
	 * @var string $action iPay action name. Defaults to 'gaf'
	 */
 	public $action='gaf';
	/**
	 * @var integer $ver iPay protocol version. Defaults to '004'
	 */
 	public $ver='004';
	/**
	 * @var string $delivery delivery symbol. Defaults to 'S'
	 */
 	public $delivery='S';
	/**
	 * @var string $charEncoding character encoding. Defaults to 'UTF-8'
	 */
 	public $charEncoding='UTF-8';
	/**
	 * @var string $cur Payment currency ISO-4217. Defaults to 'EUR'
	 */
 	public $cur='EUR';
	/**
	 * @var string $payButtonLabel label for pay button. Defaults to 'Pay'
	 */
 	public $payButtonLabel='Pay';
	/**
	 * @var array $payButtonHtmlOptions thml options for pay button
	 */
 	public $payButtonHtmlOptions=array();
	/**
	 * @var string $lang interface language ISO 639-1
	 */
	public $lang;
	/**
	 * @var integer $eamount Payment amount in cents
	 */
 	public $eamount;
	/**
	 * @var string $datetime Timestamp format [YYYYMMDDhhmmss] ISO-8601
	 */
 	public $datetime;
	/**
	 * @var string $feedBackUrl feedback url
	 */
 	public $feedBackUrl;

	/**
	 * @var integer $ecuno the unique transaction number
	 */
	private $_ecuno;

	/**
	 * Render pay button inside form with hidden fields
	 */
	public function renderPayButton()
	{
		$file=dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'_form.php';

		Yii::app()->controller->renderFile($file, array(
			'serviceUrl'=>$this->serviceUrl,
			'serviceId'=>$this->serviceId,
			'action'=>$this->action,
			'ver'=>$this->ver,
			'delivery'=>$this->delivery,
			'charEncoding'=>$this->charEncoding,
			'cur'=>$this->cur,
			'payButtonLabel'=>$this->payButtonLabel,
			'payButtonHtmlOptions'=>$this->payButtonHtmlOptions,
			'lang'=>$this->lang,
			'datetime'=>$this->datetime,
			'feedBackUrl'=>$this->feedBackUrl,
			'eamount'=>sprintf("%012s", $this->eamount),
			'ecuno'=>$this->_ecuno,
			'mac'=>$this->getMac(),
		));
	}

	/**
	 * Set unique transaction number
	 * as time stamp [YYYYMM] + random number between 100000-999999
	 */
	protected function setEcuno()
	{
		$rnd=rand(100000,999999);
		$date=$datetime=date("Ym");
		$this->_ecuno=$date.$rnd;
	}

	/**
	 * Get signed data
	 * @return signed data in HEX format
	 */
	protected function getMac()
	{
		$this->setEcuno();

		// construct data string
		$serviceId=sprintf("%-10s", $this->serviceId);
		$feedbackurl=sprintf("%-128s", $this->feedBackUrl);
		$ecuno=sprintf("%012s", $this->_ecuno);
		$eamount=sprintf("%012s", $this->eamount);

		$data=
			$this->ver .
			$serviceId .
			$ecuno .
			$eamount .
			$this->cur .
			$this->datetime .
			$feedbackurl .
			$this->delivery;

		// prepare private key
		$fp=fopen($this->privateKeyPath,'r');
		$fs=filesize($this->privateKeyPath);
		$privateKey=fread($fp,$fs);
		fclose($fp);

		// sign
		$signature=sha1($data);
		$privateKeyId=openssl_get_privatekey($privateKey);
		openssl_sign($data, $signature, $privateKeyId);
		openssl_free_key($privateKeyId);

		// convert to hex
		$mac=bin2hex($signature);

		return $mac;
	}

	/**
	 * Validate E-Commerce Payment Gateway feedback.
	 * After a customer has completed their order through E-Commerce Payment Gateway,
	 * E-Commerce Payment Gateway will contact the script you provided in the "feedBackUrl"
	 * argument. E-Commerce Payment Gateway will POST the order information to your script
	 * and it's up to us to verify that it’s a valid order.
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
			$_REQUEST['respcode'] .
			$_REQUEST['datetime'] .
			$this->mb_sprintf("%-40s",$_POST['msgdata']) .
			$this->mb_sprintf("%-40s", $_POST['actiontext']);

		// get mac
		$mac = $this->hex2str($_POST['mac']);

		// get key
		$fp = fopen($this->certificatePath, 'r');
		$certificate = fread($fp, 8192);
		fclose($fp);
		$publicKeyId = openssl_get_publickey($certificate);

		// return whether signature is okay or not
		$ok = openssl_verify($data, $mac, $publicKeyId);
		return ($ok == 1) ? true : false;
	}

	/**
	 * Convert hexcode to string
	 * @param $hex the mac signature in hexdecimal format
	 * @return string mac signature
	 */
	protected function hex2str($hex)
	{
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
		return $this->mb_vsprintf($format, $argv) ;
	}

	/**
	 * Multibyte safe vsprintf
	 */
	protected function mb_vsprintf($format,$argv,$encoding=null)
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
					if($precision>0&&mb_strlen($arg,$encoding)>$precision)
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
