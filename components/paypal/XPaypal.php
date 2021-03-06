<?php
/**
 * Paypal.php
 *
 * https://github.com/stdevteam/yii-paypal
 *
 * @author STDev <yii@st-dev.com>
 * @copyright 2013 STDev http://st-dev.com
 * @license released under dual license BSD License and LGP License
 * @package PayPal
 * @version 1.0
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XPaypal extends CComponent
{
	/**
	 * @var string $apiUsername The user that is identified as making the call.
	 */
	public $apiUsername;
	/**
	 * @var string $apiPassword The password associated with the API user
	 */
	public $apiPassword;
	/**
	 * @var string $apiSignature The Signature associated with the API user, which is generated by paypal.
	 */
	public $apiSignature;
	/**
	 * @var boolean $apiLive whether to use live or sandbox site.
	 * You can create test account at https://developer.paypal.com/webapps/developer/applications/accounts
	 */
	public $apiLive=false;
	/**
	 * @var string $returnUrl the url that paypal returns respons to
	 */
	public $returnUrl;
	/**
	 * @var string $cancelUrl the url that paypal returns to when payment is canceled
	 */
	public $cancelUrl;
	/**
	 * @var float $amount the amount of money to be paid
	 */
	public $amount;
	/**
	 * @var string $currency the payment currency ISO-4217. Defaults to 'EUR'
	 */
	public $currency='EUR';
	/**
	 * @var string $description the payment description
	 */
	public $description;
	/**
	 * @var string $profileStartDate the date when billing for profile begins.
	 */
	public $profileStartDate;
	/**
	 * @var string $billingPeriod the unit for billing during this subscription period.
	 * It is one of the following values: Day|Week|SemiMonth|Month|Year
	 * For SemiMonth, billing is done on the 1st and 15th of each month.
	 * Note! The combination of billingPeriod and billingFrequency cannot exceed one year.
	 */
	public $billingPeriod;
	/**
	 * @var integer $billingFrequency the number of billing periods that make up one billing cycle.
	 * The combination of billing frequency and billing period must be less than or equal to one year.
	 * For example, if the billing cycle is Month, the maximum value for billing frequency is 12. Similarly,
	 * if the billing cycle is Week, the maximum value for billing frequency is 52.
	 * Note! If the billing period is SemiMonth, the billing frequency must be 1.
	 */
	public $billingFrequency;
	/**
	 * @var float $initAmount the initial non-recurring payment when the recurring payments profile is created
	 */
	public $initAmount;
	/**
	 * @var string $failedInitAmountAction the action to be performed when initial payment fails.
	 * By default, PayPal does not activate the profile if the initial payment amount fails.
	 * To override this default behavior, set the this to ContinueOnFailure. If the initial payment amount fails,
	 * ContinueOnFailure instructs PayPal to add the failed payment amount to the outstanding balance due on this
	 * recurring payment profile. If you do not set it or set it to CancelOnFailure, PayPal creates the recurring
	 * payment profile. However, PayPal places the profile into a pending status until the initial payment completes.
	 * If the initial payment clears, PayPal notifies you by Instant Payment Notification (IPN) that it has activated
	 * the pending profile. If the payment fails, PayPal notifies you by IPN that it has canceled the pending profile.
	 * If you created the profile using Express Checkout, the buyer receives an email stating that PayPal cleared the
	 * initial payment or canceled the pending profile.
	 */
	public $failedInitAmountAction;
	/**
	 * @var string $maxFailedPayments the number of scheduled payments that can fail before the profile is automatically suspended.
	 * An IPN message is sent to the merchant when the specified number of failed payments is reached.
	 */
	public $maxFailedPayments;
	/**
	 * @var string $profileId the Recurring payments profile ID returned in the CreateRecurringPaymentsProfile response
	 */
	public $profileId;
	/**
	 * @var string $profileAction the action to be performed to the recurring payments profile. Must be one of the following:
	 * [Cancel|Suspend|Reactivate]
	 */
	public $profileAction;
	/**
	 * @var string $endPoint the server URL which you have to connect for submitting your API request.
	 */
	public $endPoint;
	/**
	 * USE_PROXY: Set this variable to TRUE to route all the API requests through proxy.
	 * like define('USE_PROXY',TRUE);
	 */
	public $useProxy=false;
	public $proxyHost='127.0.0.1';
	public $proxyPort='808';
	/**
	 * @var string $paypalUrl This is the URL that the buyer is first sent to to authorize payment with their paypal account
	 * change the URL depending if you are testing on the sandbox or going to the live PayPal site
	 * For the sandbox, the URL is https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
	 * For the live site, the URL is https://www.paypal.com/webscr&cmd=_express-checkout&token=
	 */
	public $paypalUrl;
	/**
	 * @var string $version this is the API version in the request.
	 * It is a mandatory parameter for each API request.
	 */
	public $version='98.0';

	public function init()
	{
		// Whether we are in sandbox or in live environment
		if((bool)$this->apiLive===true)
		{
			$this->paypalUrl='https://www.paypal.com/webscr&cmd=_express-checkout&useraction=commit&token=';
			$this->endPoint='https://api-3t.paypal.com/nvp';
		}
		else
		{
			$this->paypalUrl='https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&useraction=commit&token=';
			$this->endPoint='https://api-3t.sandbox.paypal.com/nvp';
		}

		// set return and cancel urls
		$this->returnUrl=Yii::app()->createAbsoluteUrl($this->returnUrl);
		$this->cancelUrl=Yii::app()->createAbsoluteUrl($this->cancelUrl);
	}

	public function setExpressCheckout()
	{
		$nvpstr=
			'&PAYMENTREQUEST_0_PAYMENTACTION=Sale'.
			'&PAYMENTREQUEST_0_AMT='.urlencode($this->amount).
			'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->currency).
			'&PAYMENTREQUEST_0_DESC='.urlencode($this->description).
			'&RETURNURL='.urlencode($this->returnUrl).
			'&CANCELURL='.urlencode($this->cancelUrl);

		$resArray=$this->hash_call("SetExpressCheckout",$nvpstr);
		return $resArray;
	}

	public function setExpressCheckoutForRecurringPaymentsProfile()
	{
		$nvpstr=
			'&L_BILLINGTYPE0=RecurringPayments'.
			'&L_BILLINGAGREEMENTDESCRIPTION0='.urlencode($this->description).
			'&RETURNURL='.urlencode($this->returnUrl).
			'&CANCELURL='.urlencode($this->cancelUrl);

		$resArray=$this->hash_call("SetExpressCheckout",$nvpstr);
		return $resArray;
	}

	public function getExpressCheckoutDetails($token)
	{
		$nvpstr='&TOKEN='.$token;
		$resArray=$this->hash_call("GetExpressCheckoutDetails",$nvpstr);
		return $resArray;
	}

	public function doExpressCheckoutPayment($paymentInfo=array())
	{
		$nvpstr=
			'&PAYMENTREQUEST_0_PAYMENTACTION=Sale'.
			'&PAYMENTREQUEST_0_AMT='.urlencode($this->amount).
			'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->currency).
			'&TOKEN='.urlencode($paymentInfo['TOKEN']).
			'&PAYERID='.urlencode($paymentInfo['PAYERID']);

		$resArray=$this->hash_call("DoExpressCheckoutPayment",$nvpstr);
		return $resArray;
	}

	public function createRecurringPaymentsProfile($paymentInfo=array())
	{
		$nvpstr=
			'&AMT='.urlencode($this->amount).
			// Note! You must ensure that this field matches the corresponding
			// billing agreement description included in the SetExpressCheckout request.
			'&DESC='.urlencode($this->description).
			'&CURRENCYCODE='.urlencode($this->currency).
			'&PROFILESTARTDATE='.urlencode($this->profileStartDate).
			'&BILLINGPERIOD='.urlencode($this->billingPeriod).
			'&BILLINGFREQUENCY='.urlencode($this->billingFrequency).
			'&TOKEN='.urlencode($paymentInfo['TOKEN']).
			'&PAYERID='.urlencode($paymentInfo['PAYERID']);

		if($this->maxFailedPayments)
			$nvpstr.='&MAXFAILEDPAYMENTS='.urlencode($this->maxFailedPayments);

		if($this->initAmount)
			$nvpstr.='&INITAMT='.urlencode($this->initAmount);

		if($this->failedInitAmountAction)
			$nvpstr.='&FAILEDINITAMTACTION='.urlencode($this->failedInitAmountAction);

		$resArray=$this->hash_call("CreateRecurringPaymentsProfile",$nvpstr);
		return $resArray;
	}

	public function manageRecurringPaymentsProfileStatus()
	{
		$nvpstr=
			'&PROFILEID='.urlencode($this->profileId).
			'&ACTION='.urlencode($this->profileAction).
			'&NOTE='.urlencode($this->description);

		$resArray=$this->hash_call("ManageRecurringPaymentsProfileStatus",$nvpstr);
		return $resArray;
	}

	public function APIError($errorNo,$errorMsg,$resArray)
	{
		$resArray['Error']['Number']=$errorNo;
		$resArray['Error']['Number']=$errorMsg;
		return $resArray;
	}

	public function isCallSucceeded($resArray)
	{
		if (isset($resArray['ACK']))
		{
			$ack=strtoupper($resArray["ACK"]);

			if($ack!="SUCCESS" && $ack!='SUCCESSWITHWARNING')
				return false;
			else
				return true;
		}
		else
			return false;
	}

	public function hash_call($methodName,$nvpStr)
	{
		$API_UserName=$this->apiUsername;
		$API_Password=$this->apiPassword;
		$API_Signature=$this->apiSignature;
		$API_Endpoint=$this->endPoint;
		$version=$this->version;

		// setting the curl parameters.
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$API_Endpoint);
		curl_setopt($ch,CURLOPT_VERBOSE,1);

		// turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);

		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_POST,1);

		// if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
		// Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php
		if($this->useProxy)
			curl_setopt($ch,CURLOPT_PROXY,$this->proxyHost.":".$this->proxyPort);

		// NVPRequest for submitting to server
		$nvpreq="METHOD=".urlencode($methodName)."&VERSION=".urlencode($version)."&PWD=".urlencode($API_Password)."&USER=".urlencode($API_UserName)."&SIGNATURE=".urlencode($API_Signature).$nvpStr;

		// setting the nvpreq as POST FIELD to curl
		curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

		// getting response from server
		$response=curl_exec($ch);

		// converting NVP Response to an Associative Array
		$nvpResArray=$this->deformatNVP($response);
		$nvpReqArray=$this->deformatNVP($nvpreq);

		if(curl_errno($ch))
			$nvpResArray=$this->APIError(curl_errno($ch),curl_error($ch),$nvpResArray);
		else
			curl_close($ch);

		return $nvpResArray;
	}

	/**
	 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
	 * It is usefull to search for a particular key and displaying arrays.
	 * @nvpstr is NVPString.
	 * @nvpArray is Associative Array.
	 */
	public function deformatNVP($nvpstr)
	{
		$intial=0;
		$nvpArray=array();

		while(strlen($nvpstr))
		{
			//postion of Key
			$keypos=strpos($nvpstr,'=');
			//position of value
			$valuepos=strpos($nvpstr,'&') ? strpos($nvpstr,'&') : strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)]=urldecode($valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
		}
		return $nvpArray;
	}

	/**
	 * This function helps to refund the transaction by payerId and transactionId
	 * TransactionId returned by {@see DoExpressCheckoutPayment}
	 * @link https://developer.paypal.com/docs/classic/api/merchant/RefundTransaction_API_Operation_NVP/
	 *
	 * @param array $paymentInfo
	 * @return array
	 */
	public function RefundTransaction($paymentInfo=array())
	{
		$currencyCode=$this->currency;
		$nvpstr='&PAYERID='.urlencode($paymentInfo['PAYERID']).'&TRANSACTIONID='.urlencode($paymentInfo['TRANSACTIONID']).'&CURRENCYCODE='.urlencode($currencyCode).'&REFUNDTYPE='.urlencode('Full');
		$resArray=$this->hash_call("RefundTransaction",$nvpstr);
		return $resArray;
	}
}
