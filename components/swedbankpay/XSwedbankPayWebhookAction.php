<?php
/**
 * Checkout v3.1 notifications are hints; validate through the component first.
 * bindingCallback($orderId) returns saved expectations plus the local payment,
 * or null for an unknown order. Never derive expectations from notification data.
 * successCallback($binding, $webhookUid) must return true after durable handling.
 * failureCallback($message, $webhookUid) handles verified terminal failure only.
 * Pending is acknowledged without dispatch; uncertain results request a retry.
 * Capture safeguards belong to the component/model, fulfilment to the controller.
 */
class XSwedbankPayWebhookAction extends CAction
{
	public $componentName='swedbankPay';
	public $bindingCallback;
	public $successCallback;
	public $failureCallback;
	public $log=false;
	public $logLevel='error';
	public $logCategory='ext.components.swedbankpay.XSwedbankPayWebhookAction';

	public function run()
	{
		http_response_code($this->processCallback(
			Yii::app()->request->getRequestType(), file_get_contents('php://input', false, null, 0, 16385)));
	}

	/** Returns an HTTP status; never acknowledges verification/handler failure. */
	public function processCallback($method, $rawBody)
	{
		$webhookUid=uniqid('wh_', true);
		$prefix='UID='.$webhookUid.' source=webhook ';
		$this->log($prefix.'Callback received.');
		if($method !== 'POST')
		{
			$this->log($prefix.'Callback rejected. HTTP=405');
			return 405;
		}
		if(!is_string($rawBody) || strlen($rawBody) > 16384)
		{
			$this->log($prefix.'Callback rejected. HTTP=400');
			return 400;
		}
		$notification=json_decode($rawBody, true);
		if(!is_array($notification) || json_last_error() !== JSON_ERROR_NONE ||
			!isset($notification['paymentOrder']['id']) || !is_string($notification['paymentOrder']['id']))
		{
			$this->log($prefix.'Callback rejected. HTTP=400');
			return 400;
		}
		$id=$notification['paymentOrder']['id'];
		try
		{
			$component=$this->getPaymentComponent();
			if(!($component instanceof XSwedbankPay))
				throw new RuntimeException('Missing component.');
			foreach(array($this->bindingCallback,$this->successCallback,$this->failureCallback) as $callback)
				if(!is_string($callback) || !is_callable(array($this->controller,$callback)))
					throw new RuntimeException('Missing callback.');
			try
			{
				$component->checkOrderId($id);
			}
			catch(RuntimeException $e)
			{
				$this->log($prefix.'Callback rejected. HTTP=400');
				return 400;
			}

			$binding=$this->controller->{$this->bindingCallback}($id);
			if($binding === null)
			{
				$this->log($prefix.'Unknown binding. HTTP=404');
				return 404;
			}
			if(!is_array($binding) || !isset($binding['orderId'], $binding['reference'], $binding['amount'], $binding['currency'], $binding['payment']) ||
				$binding['orderId'] !== $id || !is_object($binding['payment']))
				throw new RuntimeException('Invalid saved binding.');
			$component->payment=$binding['payment'];
			$component->traceContext=$this->log === true ? $prefix.'reference='.$binding['reference'] : '';
			unset($binding['payment']);
			$prefix.='reference='.$binding['reference'].' order='.$binding['orderId'].' ';
			$result=$component->validatePayment($binding);
			$this->log($prefix.'Verification result='.$component->validationResult.' evidence='.json_encode($component->validationSummary));
			if($result === true)
			{
				if($this->controller->{$this->successCallback}($binding,$webhookUid) !== true)
					throw new RuntimeException('Success handler did not finish.');
			}
			elseif($result === false)
				$this->controller->{$this->failureCallback}($component->errorMessage,$webhookUid);
			elseif($component->validationResult !== 'pending')
				throw new RuntimeException('Uncertain payment state.');
			$this->log($prefix.'Callback acknowledged. HTTP=200');
			return 200;
		}
		catch(Exception $e)
		{
			// Never log notification bodies, provider payloads, credentials or exception text.
			$this->log($prefix.'Payment verification or handling failed; retry required. HTTP=500');
			return 500;
		}
	}

	protected function log($message)
	{
		if($this->log === true)
			Yii::log(__CLASS__.' '.$message,$this->logLevel,$this->logCategory);
	}

	protected function getPaymentComponent()
	{
		return Yii::app()->getComponent($this->componentName);
	}
}
