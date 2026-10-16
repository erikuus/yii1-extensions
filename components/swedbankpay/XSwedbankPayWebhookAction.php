<?php
/**
 * Checkout v3.1 notification adapter. A notification is only a hint to GET state.
 *
 * Configure componentName, bindingCallback and verifiedCallback in actions().
 * bindingCallback($orderId) must resolve a SAVED application binding or return null:
 * array('orderId'=>..., 'reference'=>..., 'amount'=>100, 'currency'=>'EUR',
 *       'captureReference'=>...) -- captureReference may be null before capture intent.
 * Never construct this binding from callback-supplied amount/payee/reference/status.
 * verifiedCallback($evidence) must return true only after durable handling.
 * It receives sanitized, bound GET evidence, including captureVerified. Paid alone
 * does not mean captured. Handlers must tolerate duplicate/reordered notifications.
 *
 * This action neither captures nor knows application models/fulfilment. No action
 * is registered by this library. Application routing/CSRF policy belongs to its
 * integrator; register only after both handlers are implemented and tested.
 */
class XSwedbankPayWebhookAction extends CAction
{
	public $componentName='swedbankPay';
	public $bindingCallback;
	public $verifiedCallback;

	public function run()
	{
		http_response_code($this->processCallback(
			Yii::app()->request->getRequestType(), file_get_contents('php://input')));
	}

	/** Returns an HTTP status; never acknowledges verification/handler failure. */
	public function processCallback($method, $rawBody)
	{
		if($method !== 'POST')
			return 405;
		if(!is_string($rawBody) || strlen($rawBody) > 16384)
			return 400;
		$notification=json_decode($rawBody, true);
		if(!is_array($notification) || json_last_error() !== JSON_ERROR_NONE ||
			!isset($notification['paymentOrder']['id']) || !is_string($notification['paymentOrder']['id']))
			return 400;
		$id=$notification['paymentOrder']['id'];
		try
		{
			$component=$this->getPaymentComponent();
			if(!($component instanceof XSwedbankPay) ||
				!is_string($this->bindingCallback) || !is_callable(array($this->controller, $this->bindingCallback)) ||
				!is_string($this->verifiedCallback) || !is_callable(array($this->controller, $this->verifiedCallback)))
				return 500;
			try { $component->checkOrderId($id); }
			catch(RuntimeException $e) { return 400; }
			$binding=$this->controller->{$this->bindingCallback}($id);
			if($binding === null)
				return 404;
			if(!is_array($binding) || !isset($binding['orderId'], $binding['reference'], $binding['amount'], $binding['currency']) ||
				$binding['orderId'] !== $id)
				return 500;
			$evidence=$component->paymentEvidence($id, $binding['reference'], $binding['amount'], $binding['currency'],
				isset($binding['captureReference']) ? $binding['captureReference'] : null);
			return $this->controller->{$this->verifiedCallback}($evidence) === true ? 200 : 500;
		}
		catch(Exception $e)
		{
			// Do not leak notification bodies, provider payloads, credentials or handler errors.
			return 500;
		}
	}

	protected function getPaymentComponent()
	{
		return Yii::app()->getComponent($this->componentName);
	}
}
