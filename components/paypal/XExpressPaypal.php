<?php
/**
 * XExpressPaypal class.
 *
 * XExpressPaypal extends XPaypal adding methods for paypal express checkout.
 * Creating/cancelling recurring payments profile and validating IPN is also supported.
 *
 * Configure component:
 * <pre>
 * 'components'=>array(
 *     'paypal'=> array(
 *         'class'=>'ext.components.paypal.XExpressPaypal',
 *         'apiUsername'=>'username',
 *         'apiPassword'=>'Z2KRMCKY85TTS..',
 *         'apiSignature'=>'AFcWxV21C7fd...',
 *         'apiLive'=>true,
 *         'receiverEmail'=>'erik.uus@gmail.com',
 *     ),
 * )
 * </pre>
 *
 * PAYMENT
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
 *     $payment->total=$product->price;
 *     $payment->status=Payment::STATUS_SUBMITTED;
 *     $payment->save();
 *
 *     $paypal=Yii::app()->paypal;
 *     $paypal->amount=$payment->total;
 *     $paypal->currency=$payment->currency;
 *     $paypal->description=$payment->transactionMessage;
 *     $paypal->returnUrl=Yii::app()->createAbsoluteUrl('validate',array('id'=>$payment->id));
 *     $paypal->cancelUrl=Yii::app()->createAbsoluteUrl('cancel');
 *     if(!$paypal->submitPayment())
 *     {
 *         Yii::app()->user->setFlash('error', $paypal->errorMessage);
 *         $this->redirect(/controller/action);
 *     }
 * }
 * </pre>
 *
 * Validate payment in controller:
 * <pre>
 * public function actionValidate($id)
 * {
 *     $payment=$this->loadModel($id);
 *
 *     $paypal=Yii::app()->paypal;
 *     $paypal->amount=$payment->total;
 *     $paypal->currency=$payment->currency;
 *
 *     if($paypal->validatePayment())
 *     {
 *         $payment=Payment::model()->findByPk($id);
 *         $payment->status=Payment::STATUS_VALID;
 *         if($payment->save())
 *             Yii::app()->user->setFlash('success', 'Payment was successful!');
 *         else
 *             Yii::app()->user->setFlash('error', 'Transaction was successful, but payment was not recorded.'));
 *     }
 *     else
 *     {
 *         $payment->status=Payment::STATUS_FAILED;
 *         $payment->save();
 *
 *         Yii::app()->user->setFlash('error', $paypal->errorMessage);
 *     }
 *
 *     $this->redirect('/controller/action');
 * }
 * </pre>
 *
 * RECURRING PAYMENT
 *
 * Submit/create recurring payment profile in controller:
 * <pre>
 * public function actionSubmitPayment($productId)
 * {
 *     $service=Service::model()->findByPk($id);
 *
 *     $subscription=new Subscription;
 *     $subscription->user_id=Yii::app()->user->id;
 *     $subscription->service_id=$service->id;
 *     $subscription->total=$service->price;
 *     $subscription->currency=$service->currency;
 *     $subscription->billing_period=$service->billing_period;
 *     $subscription->billing_frequency=$service->billing_frequency;
 *     $subscription->status=Subscription::STATUS_SUBMITTED;
 *     $subscription->description=$service->description;
 *
 *     $paypal=Yii::app()->paypal;
 *     $paypal->recurringPaymentsProfile=true;
 *     $paypal->description=$service->description;
 *     $paypal->returnUrl=Yii::app()->createAbsoluteUrl('validate',array('id'=>$subscription->id));
 *     $paypal->cancelUrl=Yii::app()->createAbsoluteUrl('cancel');
 *     if(!$paypal->submitPayment())
 *     {
 *         Yii::app()->user->setFlash('error', $paypal->errorMessage);
 *         $this->redirect(/controller/action);
 *     }
 * }
 * </pre>
 *
 * Validate/create recurring payment profile in controller:
 * <pre>
 * public function actionValidate($id)
 * {
 *     $subscription=$this->loadModel($id);
 *
 *     $paypal=Yii::app()->paypal;
 *     $paypal->recurringPaymentsProfile=true;
 *     $paypal->profileStartDate=gmdate("Y-m-d\TH:i:s\Z");
 *     $paypal->description=$subscription->description;
 *     $paypal->amount=$subscription->total;
 *     $paypal->currency=$subscription->currency;
 *     $paypal->billingPeriod=$subscription->billing_period;
 *     $paypal->billingFrequency=$subscription->billing_frequency;
 *
 *     if($paypal->validatePayment())
 *     {
 *         $subscription->status=Subscription::STATUS_VALIDATED;
 *         $subscription->paypal_profile_id=$paypal->paymentResult['PROFILEID'];
 *
 *         if($subscription->save())
 *             Yii::app()->user->setFlash('success', 'Create recurring payment profile was successful!');
 *         else
 *             Yii::app()->user->setFlash('error', 'Transaction was successful, but recurring payment profile was not recorded.'));
 *     }
 *     else
 *     {
 *         $subscription->status=Subscription::STATUS_FAILED;
 *         $subscription->save();
 *
 *         Yii::app()->user->setFlash('error', $paypal->errorMessage);
 *     }
 *
 *     $this->redirect('/controller/action');
 * }
 * </pre>
 *
 * Validate paypal ipn (instant payment notification)
 * See https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/
 * <pre>
 * public function actionIPN()
 * {
 *     $paypal=Yii::app()->paypal;
 *     if($paypal->validateIPN())
 *     {
 *         $txnType=Yii::app()->request->getPost('txn_type');
 *         switch ($txnType)
 *         {
 *             case 'recurring_payment':
 *                 if(Yii::app()->request->getPost('payment_status')!='Completed')
 *                 {
 *                     Yii::log(
 *                         'PayPal recurring payment status not completed'.PHP_EOL.
 *                         'POST: '.var_export($_POST, true),
 *                         CLogger::LEVEL_ERROR
 *                     );
 *                 }
 *                 elseif(Yii::app()->request->getPost('receiver_email')!=$paypal->receiverEmail)
 *                 {
 *                     Yii::log(
 *                         'PayPal recurring payment receiver email mismatch'.PHP_EOL.
 *                         '$paypal->receiverEmail '.$paypal->receiverEmail.PHP_EOL.
 *                         'POST: '.var_export($_POST, true),
 *                         CLogger::LEVEL_ERROR
 *                     );
 *                 }
 *                 else
 *                 {
 *                     $subscription=UserSubscription::model()->findByAttributes(array(
 *                         'paypal_profile_id'=>Yii::app()->request->getPost('recurring_payment_id',0)
 *                     ));
 *                     if($subscription)
 *                     {
 *                         if(Yii::app()->request->getPost('mc_gross')==$subscription->total &&
 *                         Yii::app()->request->getPost('mc_currency')==$subscription->currency)
 *                         {
 *                             $txnId=Yii::app()->request->getPost('txn_id');
 *                             $this->handleRecurringPayment($subscription, $txnId);
 *                         }
 *                         else
 *                         {
 *                             Yii::log(
 *                                 'PayPal recurring payment amount/currency mismatch'.PHP_EOL.
 *                                 'Subscription attributes: '.var_export($subscription->getAttributes(), true).PHP_EOL.
 *                                 'POST: '.var_export($_POST, true),
 *                                 CLogger::LEVEL_ERROR
 *                             );
 *                         }
 *                     }
 *                     else
 *                     {
 *                         Yii::log(
 *                             'PayPal recurring payment subscription not found'.PHP_EOL.
 *                             'POST: '.var_export($_POST, true),
 *                             CLogger::LEVEL_ERROR
 *                         );
 *                     }
 *                 }
 *                 break;
 *             case 'recurring_payment_profile_created':
 *                 $subscription=Subscription::model()->findByAttributes(array(
 *                     'paypal_profile_id'=>Yii::app()->request->getPost('recurring_payment_id',0)
 *                 ));
 *                 if($subscription && $subscription->status!=Subscription::STATUS_VALIDATED)
 *                 {
 *                     $subscription->status=Subscription::STATUS_VALIDATED;
 *                     $subscription->save(false);
 *                 }
 *                 break;
 *             case 'recurring_payment_profile_cancel':
 *                 $subscription=Subscription::model()->findByAttributes(array(
 *                     'paypal_profile_id'=>Yii::app()->request->getPost('recurring_payment_id',0)
 *                 ));
 *                 if($subscription && $subscription->status!=Subscription::STATUS_CANCELED)
 *                 {
 *                     $subscription->status=Subscription::STATUS_CANCELED;
 *                     $subscription->save(false);
 *                 }
 *                 break;
 *             case 'recurring_payment_suspended_due_to_max_failed_payment':
 *                 $subscription=UserSubscription::model()->findByAttributes(array(
 *                     'paypal_profile_id'=>Yii::app()->request->getPost('recurring_payment_id',0)
 *                 ));
 *                 if($subscription && $subscription->status!=UserSubscription::STATUS_SUSPENDED)
 *                 {
 *                     $subscription->status=UserSubscription::STATUS_SUSPENDED;
 *                     $subscription->save(false);
 *                 }
 *                 Yii::log(
 *                     'Recurring payment failed and the related recurring payment profile has been suspended'.PHP_EOL.
 *                     'POST: '.var_export($_POST, true),
 *                     CLogger::LEVEL_ERROR
 *                 );
 *                 break;
 *             case 'recurring_payment_expired':
 *                 Yii::log(
 *                     'PayPal recurring payment expired'.PHP_EOL.
 *                     'POST: '.var_export($_POST, true),
 *                     CLogger::LEVEL_ERROR
 *                 );
 *                 break;
 *             case 'recurring_payment_failed':
 *                 Yii::log(
 *                     'PayPal recurring payment failed'.PHP_EOL.
 *                     'POST: '.var_export($_POST, true),
 *                     CLogger::LEVEL_ERROR
 *                 );
 *                 break;
 *             case 'recurring_payment_skipped':
 *                 Yii::log(
 *                     'PayPal recurring payment skipped'.PHP_EOL.
 *                     'POST: '.var_export($_POST, true),
 *                     CLogger::LEVEL_ERROR
 *                 );
 *                 break;
 *             case 'recurring_payment_suspended':
 *                 Yii::log(
 *                     'PayPal recurring payment suspended'.PHP_EOL.
 *                     'POST: '.var_export($_POST, true),
 *                     CLogger::LEVEL_ERROR
 *                 );
 *                 break;
 *         }
 *     }
 *     else
 *     {
 *         Yii::log(
 *             'PayPal IPN validation failed'.PHP_EOL.
 *             'Message: '.$paypal->errorMessage.PHP_EOL.
 *             'POST: '.var_export($_POST, true),
 *             CLogger::LEVEL_ERROR
 *         );
 *     }
 * }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/paypal/XPaypal.php';
require_once dirname(__FILE__).'/vendor/src/IpnListener.php';

class XExpressPaypal extends XPaypal
{
	/**
	 * Error codes
	 */
	const ERROR_NONE=0;
	const ERROR_SET_EXPRESS_CHECKOUT=1;
	const ERROR_GET_EXPRESS_CHECKOUT=2;
	const ERROR_DO_EXPRESS_CHECKOUT=3;
	const ERROR_CREATE_RECCURRING=4;
	const ERROR_MANAGE_RECCURRING=5;
	const ERROR_VERIFY_IPN=6;

	/**
	 * @var boolean $recurringPaymentsProfile whether to create recurring payments profile instead of single payment
	 */
	public $recurringPaymentsProfile=false;
	/**
	 * @var array paymentResult
	 * This is useful when creating recurring payments profile.
	 * as $paymentResult will contain profile ID.
	 * For example: array (
	 *   'PROFILEID' => 'I-WHUJRNGWXEHD',
	 *   'PROFILESTATUS' => 'ActiveProfile',
	 *   'TIMESTAMP' => '2015-10-13T13:04:10Z',
	 *   'CORRELATIONID' => '4cdeded14228',
	 *   'ACK' => 'Success',
	 *   'VERSION' => '98.0',
	 *   'BUILD' => '18308778',
	 * )
	 */
	public $paymentResult;
	/**
	 * @var string receiver primary PayPal email
	 * We need this to check IPN response
	 */
	public $receiverEmail;
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
	 * Submit payment.
	 * Get token and call paypal.
	 * @param boolean $recurring whether to sumbit recurring payment request
	 */
	public function submitPayment()
	{
		$result=$this->recurringPaymentsProfile ?
			$this->setExpressCheckoutForRecurringPaymentsProfile() :
			$this->setExpressCheckout();

		if(!$this->isCallSucceeded($result))
		{
			$this->errorCode=self::ERROR_SET_EXPRESS_CHECKOUT;
			if($this->apiLive===true)
				$this->errorMessage=Yii::t('XExpressPaypal.paypal', 'We were unable to access PayPal. Please try again later.');
			else
				$this->errorMessage=$result['L_LONGMESSAGE0'];

			return false;
		}
		else
		{
			$token=urldecode($result["TOKEN"]);
			yii::app()->controller->redirect($this->paypalUrl.$token);
		}
	}

	/**
	 * Validate payment.
	 * After a customer has completed their order,
	 * paypal will contact the script you provided in the "returnUrl"
	 * argument. Paypal will POST the order information to your script
	 * and it's up to us to verify that it's a valid order.
	 * @return boolean whether payment validates
	 */
	public function validatePayment()
	{
		$token=trim($_GET['token']);
		$result=$this->getExpressCheckoutDetails($token);
		$result['TOKEN'] = $token;

		if(!$this->isCallSucceeded($result))
		{
			$this->errorCode=self::ERROR_GET_EXPRESS_CHECKOUT;

			if($this->apiLive===true)
				$this->errorMessage=Yii::t('XExpressPaypal.paypal', 'We were unable to receive data from PayPal. Please try again later.');
			else
				$this->errorMessage=$result['L_LONGMESSAGE0'];

			return false;
		}
		else
		{
			$paymentResult=$this->recurringPaymentsProfile ?
				$this->createRecurringPaymentsProfile($result) :
				$this->doExpressCheckoutPayment($result);

			if(!$this->isCallSucceeded($paymentResult))
			{
				$this->errorCode=$this->recurringPaymentsProfile ?
					self::ERROR_CREATE_RECCURRING :
					self::ERROR_DO_EXPRESS_CHECKOUT;

				if($this->apiLive===true)
					$this->errorMessage=Yii::t('XExpressPaypal.paypal', 'Payment failed! Please try again later.');
				else
					$this->errorMessage=$paymentResult['L_LONGMESSAGE0'];

				return false;
			}
			else
			{
				$this->paymentResult=$paymentResult;
				return true;
			}
		}
	}

	/**
	 * Cancel subscription
	 */
	public function manageReccuringPaymentsProfileStatus()
	{
		$result=$this->manageRecurringPaymentsProfileStatus();

		if(!$this->isCallSucceeded($result))
		{
			$this->errorCode=self::ERROR_MANAGE_RECCURRING;
			if($this->apiLive===true)
				$this->errorMessage=Yii::t('XExpressPaypal.paypal', 'Manage subscripton failed! Please try again later.');
			else
				$this->errorMessage=$result['L_LONGMESSAGE0'];

			return false;
		}
		else
			return true;
	}

	/**
	 * Validate IPN Message
	 * PayPal provides a simple solution for notifying us when a payment has been processed;
	 * they call it Instant Payment Notifications (IPN). In order to take advantage of IPN,
	 * we create an IPN listener for our application (see https://github.com/Quixotix/PHP-PayPal-IPN).
	 * See also https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/
	 * @return boolean whether ipn was validates
	 */
	public function validateIPN()
	{
		$listener=new IpnListener();
		$listener->use_sandbox=!$this->apiLive;

		if ($listener->processIpn()) // Valid IPN
		{
			return true;
		}
		else // Invalid IPN
		{
			$this->errorCode=self::ERROR_VERIFY_IPN;
			$this->errorMessage=var_export($listener->getErrors(), true);
			return false;
		}
	}
}