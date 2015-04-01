<?php
/**
 * XExpressPaypal class.
 *
 * XExpressPaypal extends XPaypal adding sendPayment and validatePayment
 * methods for paypal express checkout
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
 *     ),
 * )
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
 *         Yii::app()->user->setFlash('error', Yii::app()->swedbank->errorMessage);
 *
 *     $this->redirect('/controller/action');
 * }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/paypal/XPaypal.php';

class XExpressPaypal extends XPaypal
{
	/**
	 * Error codes
	 */
	const ERROR_NONE=0;
	const ERROR_SET_EXPRESS_CHECKOUT=1;
	const ERROR_GET_EXPRESS_CHECKOUT=2;
	const ERROR_DO_EXPRESS_CHECKOUT=3;

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
	 * Get token and call paypal
	 */
	public function submitPayment()
	{
		$result=$this->SetExpressCheckout();

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
	 * Validate paypal express checkout payment.
	 * After a customer has completed their order,
	 * paypal will contact the script you provided in the "returnUrl"
	 * argument. Paypal will POST the order information to your script
	 * and it's up to us to verify that it's a valid order.
	 * @return boolean whether payment validates
	 */
	public function validatePayment()
	{
		$token=trim($_GET['token']);
		$payerId=trim($_GET['PayerID']);

		$result=$this->GetExpressCheckoutDetails($token);

		$result['PAYERID'] = $payerId;
		$result['TOKEN'] = $token;
		$result['ORDERTOTAL'] = $this->amount;

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
			$paymentResult=$this->DoExpressCheckoutPayment($result);
			if(!$this->isCallSucceeded($paymentResult))
			{
				$this->errorCode=self::ERROR_DO_EXPRESS_CHECKOUT;
				if($this->apiLive===true)
					$this->errorMessage=Yii::t('XExpressPaypal.paypal', 'Payment failed! Please try again later.');
				else
					$this->errorMessage=$result['L_LONGMESSAGE0'];

				return false;
			}
			else
				return true;
		}
	}
}