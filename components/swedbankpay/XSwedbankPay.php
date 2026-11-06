<?php
/**
 * Checkout v3.1 client (card capture and bank sale verification). Configure as a Yii component; disabled by default.
 * Amounts/VAT are integer minor units. Read methods require the saved binding.
 * submitPayment() redirects; the configured paymentModel stores the binding.
 * No automatic POST retries or application fulfilment.
 * confirmPayment() uses the configured model for durable capture intent/evidence.
 * Low-level captureOrder() callers must serialize and persist intent themselves;
 * after an uncertain outcome reconcile through GET, never blindly retry.
 * Checkout URLs and raw responses may contain tokens: do not log them.
 */
class XSwedbankPay extends CApplicationComponent
{
	const ORIGIN = 'https://api.externalintegration.swedbankpay.com';
	public $enabled=false;
	public $testMode=true;
	public $payeeId;
	public $accessToken;

	/** Model alias implementing binding and confirmation persistence; see README. */
	public $paymentModel;
	public $payment;
	public $productName;
	public $language;
	public $returnUrl;
	public $cancelUrl;
	public $callbackUrl;
	public $termsOfServiceUrl;
	public $logoUrl;
	public $autoRedirect=true;
	public $paymentLink;
	public $errorMessage;
	/** Last validatePayment() result; error means confirmation could not be established. */
	public $validationResult;
	/** Sanitized GET evidence for request-correlated application traces. No payloads. */
	public $validationSummary=array();
	/** Set by the caller from its generated UID and saved binding, never request data. */
	public $traceContext='';
	private $_confirmationStage='binding';
	private $_failureReason;
	private $_confirmationStarted;


	/** Configure payment values and URLs, then submit as with XEveryPay/XStripe. */
	public function submitPayment()
	{
		$this->_checkAccess();
		if(!$this->paymentModel)
			throw new CException('A payment model is required.');
		$class=Yii::import($this->paymentModel);
		$detail=$class::forPayment($this->payment, $this);
		$options=array(
			'description'=>mb_substr($this->productName, 0, 40, 'UTF-8'),
			'userAgent'=>Yii::app()->request->userAgent,
			'language'=>$this->language === 'en' ? 'en-US' : 'et-EE',
			'vatAmount'=>0,
			'urls'=>array(
				'hostUrls'=>array(Yii::app()->request->getHostInfo('https')),
				'completeUrl'=>$this->returnUrl,
				'cancelUrl'=>$this->cancelUrl,
				'termsOfServiceUrl'=>$this->termsOfServiceUrl,
				'logoUrl'=>$this->logoUrl,
			),
		);
		if($this->callbackUrl !== null)
			$options['urls']['callbackUrl']=$this->callbackUrl;
		$options['currency']=$detail->currency;
		if(!$detail->order_id)
		{
			$response=$this->createOrder($detail->reference, (int)$detail->amount, $options);
			$detail->saveOrder($response['paymentOrder']['id']);
		}
		$this->paymentLink=$this->checkoutUrl($detail->order_id, $detail->reference, (int)$detail->amount, $detail->currency);
		if($this->autoRedirect)
			Yii::app()->request->redirect($this->paymentLink);
	}

	/**
	 * Set payment before validation. True means verified settlement, false verified
	 * failure, and null pending/uncertain (never mark failed). No business completion
	 * or parent payment-status writes. errorMessage contains only a safe message key.
	 */
	public function validatePayment($expectedBinding=null)
	{
		$this->validationResult='error';
		$this->validationSummary=array();
		$this->_confirmationStage='binding';
		$this->_failureReason=null;
		$this->_confirmationStarted=microtime(true);
		$this->_trace('confirmation_started');
		$this->errorMessage='Payment confirmation could not be verified. Please try again later.';
		try
		{
			$this->validationResult=$this->confirmPayment($expectedBinding);
			$this->_trace('confirmation_finished',array('result'=>$this->validationResult,
				'elapsedMs'=>$this->_elapsed($this->_confirmationStarted)));
			if($this->validationResult === 'captured')
			{
				$this->errorMessage=null;
				return true;
			}
			if($this->validationResult === 'failed')
			{
				$this->errorMessage='Payment failed or was canceled.';
				return false;
			}
			if($this->validationResult === 'pending')
				$this->errorMessage='Payment confirmation is pending.';
			return null;
		}
		catch(Exception $e)
		{
			$this->validationResult='error';
			$this->validationSummary['reason']=$this->_failureReason ?: 'confirmation_error';
			$this->validationSummary['stage']=$this->_confirmationStage;
			$this->_trace('confirmation_failed',array('reason'=>$this->validationSummary['reason'],
				'elapsedMs'=>$this->_elapsed($this->_confirmationStarted)));
			$this->errorMessage='Payment confirmation could not be verified. Please try again later.';
			return null;
		}
	}

	/** Provider notifications use the separate webhook action, not browser validation. */
	public function isAutoRequest()
	{
		return false;
	}

	/**
	 * Shared browser/webhook confirmation. The model commits capture intent before
	 * POST. Returns pending, failed or captured. No application business IO, parent
	 * status changes or completion claims here; those belong to the legacy flow.
	 */
	public function confirmPayment($expectedBinding=null)
	{
		$this->_checkAccess();
		if(!$this->paymentModel)
			throw new CException('A payment model is required.');
		$class=Yii::import($this->paymentModel);
		$detail=$class::forConfirmation($this->payment, $this);
		// Bind webhook resolution to the same saved payment before any provider IO.
		if($expectedBinding !== null && $expectedBinding != $detail->binding)
			throw new RuntimeException('Notification binding differs from the saved payment.');
		$this->_confirmationStage='evidence';
		$evidence=$this->paymentEvidence($detail->order_id, $detail->reference,
			(int)$detail->amount, $detail->currency, $detail->capture_reference);
		$this->_summarizeEvidence($evidence);
		if($evidence['status'] === 'Paid' && !$evidence['captureVerified'] && !$evidence['saleVerified'])
		{
			if(!$this->_isUncapturedAuthorization($evidence, (int)$detail->amount))
			{
				$this->_failureReason='authorization_precondition';
				throw new RuntimeException('Payment requires reconciliation; no full uncaptured authorization.');
			}
			$this->_confirmationStage='capture_claim';
			if(!$detail->claimCapture())
			{
				$this->_failureReason='capture_already_requested';
				$this->_trace('capture_claim_unavailable');
				throw new RuntimeException('Capture already requested; reconcile through GET only.');
			}
			$this->_trace('capture_claim_acquired');
			$evidence=$this->captureOrder($detail->order_id, $detail->reference,
				(int)$detail->amount, $detail->capture_reference, $detail->currency);
		}
		if(!in_array($evidence['status'], array('Initialized','Paid','Failed','Aborted','Cancelled'), true))
			throw new RuntimeException('Unknown provider payment state.');
		$this->_summarizeEvidence($evidence);
		$this->_confirmationStage='save_confirmation';
		$result=$detail->saveConfirmation($evidence);
		$this->payment->refresh();
		return $result;
	}

	/** Yii consumes the reserved component option `enabled`; configure allowRequests instead. */
	public function setAllowRequests($value)
	{
		$this->enabled=$value;
	}

	/** Validate the request before sending it. */
	public function validateCreate($reference, $amount, array $options)
	{
		$this->_checkAccess();
		$this->_checkExpected($reference, $amount, isset($options['currency']) ? $options['currency'] : 'EUR');
		$this->_checkCreateOptions($options, $amount);
	}

	/** Create or recover an order with the same reference; no automatic POST retry. */
	public function createOrder($reference, $amount, array $options)
	{
		$currency=isset($options['currency']) ? $options['currency'] : 'EUR';
		$this->validateCreate($reference, $amount, $options);
		$response=$this->_request('POST', '/psp/paymentorders', array('paymentOrder'=>array(
			'operation'=>'Purchase',
			'currency'=>$currency,
			'amount'=>$amount,
			'vatAmount'=>$options['vatAmount'],
			'description'=>$options['description'],
			'userAgent'=>$options['userAgent'],
			'language'=>isset($options['language']) ? $options['language'] : 'et-EE',
			'urls'=>$options['urls'],
			'payeeInfo'=>array('payeeId'=>$this->payeeId, 'payeeReference'=>$reference),
		)));
		// Duplicate references return 409 plus the original UUID, including after a
		// lost response. A conflict alone is not proof of the expected binding.
		if(isset($response['conflictingOrderId']))
			return $this->readOrder($response['conflictingOrderId'], $reference, $amount, $currency);
		if(!isset($response['paymentOrder']['id']))
			throw new RuntimeException('Create response missing order ID; retry only with the same reference and binding.');
		$this->checkOrderId($response['paymentOrder']['id']);
		$this->_checkOrder($response, $amount, $currency);
		return $response;
	}

	/** Reads the returned resource and binds it to the saved expected order. */
	public function readOrder($id, $reference, $amount, $currency='EUR')
	{
		$this->_checkAccess();
		$this->_checkExpected($reference, $amount, $currency);
		$this->checkOrderId($id);
		$response=$this->_request('GET', $id);
		$this->_checkOrder($response, $amount, $currency);
		if($response['paymentOrder']['id'] !== $id)
			throw new RuntimeException('Order ID mismatch.');
		if(!isset($response['paymentOrder']['payeeInfo']['id']) ||
			$response['paymentOrder']['payeeInfo']['id'] !== $id.'/payeeinfo')
			throw new RuntimeException('Unexpected payee information resource.');
		$payee=$this->_request('GET', $response['paymentOrder']['payeeInfo']['id']);
		if(!isset($payee['payeeInfo']['payeeId'], $payee['payeeInfo']['payeeReference']) ||
			$payee['payeeInfo']['payeeId'] !== $this->payeeId ||
			$payee['payeeInfo']['payeeReference'] !== $reference)
			throw new RuntimeException('Payee or reference mismatch.');
		return $response;
	}

	/** Checkout URL is a short-lived bearer capability: do not log or persist it. */
	public function checkoutUrl($id, $reference, $amount, $currency='EUR')
	{
		$response=$this->readOrder($id, $reference, $amount, $currency);
		if($response['paymentOrder']['status'] !== 'Initialized')
			throw new RuntimeException('Checkout requires an initialized order.');
		foreach(isset($response['operations']) ? $response['operations'] : array() as $operation)
		{
			if(isset($operation['rel'], $operation['method'], $operation['href']) &&
				$operation['rel'] === 'redirect-checkout' && $operation['method'] === 'GET')
			{
				$url=$operation['href'];
				if(!is_string($url) || !preg_match('~\A'.preg_quote($this->_checkoutOrigin(), '~').'/menu/[^\s#]+\z~', $url))
					throw new RuntimeException('Unexpected checkout origin.');
				return $url;
			}
		}
		throw new RuntimeException('Hosted checkout is unavailable.');
	}

	/** Full capture only. Never retry a POST after an uncertain outcome. */
	public function captureOrder($id, $reference, $amount, $captureReference, $currency='EUR', $vatAmount=0)
	{
		$this->_checkAccess();
		$this->_checkExpected($reference, $amount, $currency);
		$this->_checkExpected($captureReference, $amount, $currency);
		$this->_checkVat($vatAmount, $amount);
		if($captureReference === $reference)
			throw new InvalidArgumentException('Capture needs a distinct, persisted operation reference.');
		$this->_confirmationStage='capture_preflight';
		$before=$this->paymentEvidence($id, $reference, $amount, $currency);
		if(!$this->_isUncapturedAuthorization($before, $amount))
			throw new RuntimeException('Capture requires a wholly uncaptured card authorization.');
		$response=$this->readOrder($id, $reference, $amount, $currency);
		$order=$response['paymentOrder'];
		if($order['status'] !== 'Paid' || !isset($order['remainingCaptureAmount'], $order['vatAmount']) ||
			$order['remainingCaptureAmount'] !== $amount || $order['vatAmount'] !== $vatAmount)
			throw new RuntimeException('Capture precondition changed or VAT does not match the expected order.');
		$path=$id.'/captures';
		$available=false;
		foreach(isset($response['operations']) ? $response['operations'] : array() as $operation)
			if(isset($operation['rel'], $operation['method'], $operation['href']) &&
				$operation['rel'] === 'capture' && $operation['method'] === 'POST' &&
				$operation['href'] === $this->_apiOrigin().$path)
				$available=true;
		if(!$available)
			throw new RuntimeException('Expected capture operation unavailable.');
		$this->_confirmationStage='capture_post';
		$this->_trace('capture_post_started');
		$response=$this->_request('POST', $path, array('transaction'=>array(
			'description'=>'Full card capture', 'amount'=>$amount, 'vatAmount'=>$vatAmount,
			'payeeReference'=>$captureReference,
		)));
		$this->_trace('capture_post_accepted');
		// An accepted POST is not enough. Reconcile independently through GET resources.
		$this->_checkOrder($response, $amount, $currency);
		if($response['paymentOrder']['id'] !== $id)
			throw new RuntimeException('Capture response order mismatch; do not repeat POST.');
		return $this->verifyCapture($id, $reference, $amount, $captureReference, $currency);
	}

	/** GET-only reconciliation, also safe after an uncertain capture invocation. */
	public function verifyCapture($id, $reference, $amount, $captureReference, $currency='EUR')
	{
		$this->_checkExpected($captureReference, $amount, $currency);
		$this->_confirmationStage='capture_reconciliation';
		$this->_trace('capture_reconciliation_started');
		$after=$this->paymentEvidence($id, $reference, $amount, $currency, $captureReference);
		if(!$after['captureVerified'])
		{
			$this->_failureReason='capture_not_verified';
			throw new RuntimeException('Capture not yet verified; use read-only evidence. Do not repeat POST.');
		}
		$this->_trace('capture_verified');
		return $after;
	}

	/** Read-only evidence; Paid/Authorization does not establish captured funds. */
	public function paymentEvidence($id, $reference, $amount, $currency='EUR', $captureReference=null)
	{
		$response=$this->readOrder($id, $reference, $amount, $currency);
		$order=$response['paymentOrder'];
		$evidence=array('orderId'=>$id, 'reference'=>$reference, 'amount'=>$amount,
			'currency'=>$currency, 'status'=>$order['status']);
		foreach(array('remainingCaptureAmount','remainingCancellationAmount','remainingReversalAmount') as $field)
			$evidence[$field]=isset($order[$field]) ? $order[$field] : null;
		$resources=array('financialTransactions','failedAttempts');
		if($order['status'] === 'Paid')
			$resources[]='paid';
		foreach($resources as $resource)
		{
			$path=$id.'/'.strtolower($resource);
			if(!isset($order[$resource]['id']) || $order[$resource]['id'] !== $path)
				throw new RuntimeException('Unexpected payment evidence resource.');
			$data=$this->_request('GET', $path);
			if(!isset($data[$resource]['id']) || $data[$resource]['id'] !== $path)
				throw new RuntimeException('Malformed payment evidence.');
			$evidence[$resource]=$data[$resource];
		}
		if($order['status'] === 'Paid')
		{
			$paid=$evidence['paid'];
			if(!isset($paid['amount'], $paid['payeeReference'], $paid['instrument'], $paid['transactionType']) ||
				$paid['amount'] !== $amount || $paid['payeeReference'] !== $reference || !in_array($paid['instrument'],array('CreditCard','BankLink'),true))
				throw new RuntimeException('Paid transaction does not match the expected supported order.');
			// Exclude card details and any tokens from the caller's evidence output.
			$evidence['paid']=array_intersect_key($paid, array_flip(array('id','number','instrument','transactionType','amount','payeeReference')));
		}
		$transactions=$evidence['financialTransactions'];
		// An empty resource may omit its list for Initialized orders and Sales.
		// It is not settlement proof on its own; the predicates below verify evidence.
		if(($order['status'] === 'Initialized' || ($order['status'] === 'Paid' && $evidence['paid']['transactionType'] === 'Sale')) &&
			array_keys($transactions) === array('id'))
			$transactions['financialTransactionsList']=array();
		if(!isset($transactions['financialTransactionsList']) || !is_array($transactions['financialTransactionsList']))
			throw new RuntimeException('Missing financial transaction list; capture is unverified.');
		$evidence['financialTransactions']=array();
		foreach($transactions['financialTransactionsList'] as $transaction)
		{
			if(!is_array($transaction))
				throw new RuntimeException('Malformed financial transaction.');
			if((isset($transaction['id']) && (!is_string($transaction['id']) || strpos($transaction['id'],$id.'/financialtransactions/') !== 0)) ||
				(isset($transaction['currency']) && $transaction['currency'] !== $currency))
				throw new RuntimeException('Financial transaction binding mismatch.');
			$evidence['financialTransactions'][]=array_intersect_key($transaction,
				array_flip(array('id','created','type','number','amount','payeeReference')));
		}
		$failed=$evidence['failedAttempts'];
		if(!isset($failed['failedAttemptList']) || !is_array($failed['failedAttemptList']))
			throw new RuntimeException('Missing failed attempt list.');
		$evidence['failedAttempts']=array();
		foreach($failed['failedAttemptList'] as $attempt)
		{
			if(!is_array($attempt))
				throw new RuntimeException('Malformed failed attempt.');
			$summary=array_intersect_key($attempt, array_flip(array('created','instrument','number','status')));
			if(isset($attempt['problem']) && is_array($attempt['problem']))
			{
				$summary['problem']=array_intersect_key($attempt['problem'], array_flip(array('type','status')));
				// Keep diagnostic codes, but not free-text details that can echo card data.
				$summary['problem']['codes']=array();
				foreach(isset($attempt['problem']['problems']) && is_array($attempt['problem']['problems']) ? $attempt['problem']['problems'] : array() as $problem)
					if(isset($problem['name']) && is_string($problem['name']) && preg_match('/\A[A-Za-z0-9_]{1,80}\z/', $problem['name']))
						$summary['problem']['codes'][]=$problem['name'];
			}
			$evidence['failedAttempts'][]=$summary;
		}
		$evidence['saleVerified']=$this->_isFullBankSale($evidence, $amount);
		$evidence['captureVerified']=false;
		if($captureReference !== null)
		{
			$this->_checkExpected($captureReference, $amount, $currency);
			$evidence['captureReference']=$captureReference;
			$evidence['captureVerified']=$this->_isFullCapture($evidence, $amount, $captureReference);
		}
		return $evidence;
	}

	/** Check before claiming capture, and again against fresh evidence before POST. */
	private function _isUncapturedAuthorization(array $evidence, $amount)
	{
		return $evidence['status'] === 'Paid' && $evidence['paid']['instrument'] === 'CreditCard' &&
			$evidence['paid']['transactionType'] === 'Authorization' &&
			$evidence['remainingCaptureAmount'] === $amount && $this->_matchesTransactions($evidence, null);
	}

	private function _isFullCapture(array $after, $amount, $captureReference)
	{
		// Live v3.1 omits exhausted balances. Absence alone proves nothing: require
		// the exact full capture AND the full reversible balance before accepting it.
		return $after['status'] === 'Paid' && $after['paid']['instrument'] === 'CreditCard' &&
			$after['paid']['transactionType'] === 'Authorization' &&
			$this->_matchesTransactions($after, $captureReference) &&
			in_array($after['remainingCaptureAmount'], array(0,null), true) &&
			in_array($after['remainingCancellationAmount'], array(0,null), true) &&
			$after['remainingReversalAmount'] === $amount;
	}

	/** v3.1 lists may include the original authorization alongside captures. */
	private function _matchesTransactions(array $evidence, $captureReference)
	{
		$authorizations=0;
		$captures=0;
		foreach($evidence['financialTransactions'] as $transaction)
		{
			if(!isset($transaction['type'], $transaction['amount'], $transaction['payeeReference']) ||
				$transaction['amount'] !== $evidence['amount'])
				return false;
			if($transaction['type'] === 'Authorization')
			{
				if(++$authorizations > 1 || !isset($transaction['number'], $evidence['paid']['number']) ||
					$transaction['number'] !== $evidence['paid']['number'] ||
					$transaction['payeeReference'] !== $evidence['reference'])
					return false;
			}
			elseif($transaction['type'] === 'Capture' && $captureReference !== null)
			{
				if(++$captures > 1 || $transaction['payeeReference'] !== $captureReference)
					return false;
			}
			else
				return false; // Cancellation, reversal, sale, unknown or prior capture.
		}
		return $captures === ($captureReference === null ? 0 : 1);
	}

	/**
	 * Bank sales are already transferred. Never send a card capture for them.
	 * 32-bit PHP decodes large JSON integers as floats; require exact safe integers.
	 */
	private function _isFullBankSale(array $evidence, $amount)
	{
		if($evidence['status'] !== 'Paid' || $evidence['paid']['instrument'] !== 'BankLink' ||
			$evidence['paid']['transactionType'] !== 'Sale' || !isset($evidence['paid']['number']) ||
			(!is_int($evidence['paid']['number']) && !is_float($evidence['paid']['number'])) ||
			$evidence['paid']['number'] < 1 || $evidence['paid']['number'] > 9007199254740991 ||
			floor($evidence['paid']['number']) != $evidence['paid']['number'] ||
			!in_array($evidence['remainingCaptureAmount'],array(0,null),true) ||
			!in_array($evidence['remainingCancellationAmount'],array(0,null),true) ||
			$evidence['remainingReversalAmount'] !== $amount)
			return false;
		// The provider's official module supports Sale omitted from the financial
		// list. The bound paid Sale and full balance prove it, never an empty list alone.
		if(count($evidence['financialTransactions']) === 0)
			return true;
		if(count($evidence['financialTransactions']) !== 1)
			return false;
		$sale=$evidence['financialTransactions'][0];
		return isset($sale['type'],$sale['number'],$sale['amount'],$sale['payeeReference']) &&
			$sale['type'] === 'Sale' && $sale['number'] === $evidence['paid']['number'] &&
			$sale['amount'] === $amount && $sale['payeeReference'] === $evidence['reference'];
	}

	private function _summarizeEvidence(array $evidence)
	{
		$this->validationSummary=array(
			'status'=>in_array($evidence['status'],array('Initialized','Paid','Failed','Aborted','Cancelled'),true) ? $evidence['status'] : 'Unknown',
			'captureVerified'=>$evidence['captureVerified'],
			'saleVerified'=>$evidence['saleVerified'],
			'failedAttemptCount'=>count($evidence['failedAttempts']),
		);
		if(isset($evidence['paid']))
			{
			$this->validationSummary['instrument']=$evidence['paid']['instrument'];
			$this->validationSummary['transactionType']=in_array($evidence['paid']['transactionType'],array('Authorization','Sale'),true) ?
				$evidence['paid']['transactionType'] : 'Unknown';
		}
		$codes=array();
		foreach($evidence['failedAttempts'] as $attempt)
		{
			if(isset($attempt['problem']['codes']))
				$codes=array_merge($codes,$attempt['problem']['codes']);
		}
		$this->validationSummary['failureCodes']=array_values(array_unique($codes));
	}

	private function _checkAccess()
	{
		if($this->enabled !== true || !is_bool($this->testMode))
			throw new RuntimeException('Swedbank Pay client is disabled.');
		if(!is_string($this->payeeId) || !preg_match('/\A[0-9a-f-]{36}\z/i', $this->payeeId) ||
			!is_string($this->accessToken) || !preg_match('/\A[A-Za-z0-9._~+\/-]+=*\z/', $this->accessToken))
			throw new RuntimeException('Missing or invalid credentials.');
	}

	private function _checkExpected($reference, $amount, $currency)
	{
		if(!is_string($reference) || !preg_match('/\A[A-Za-z0-9]{1,30}\z/', $reference) ||
			!is_int($amount) || $amount < 1 || !is_string($currency) || !preg_match('/\A[A-Z]{3}\z/', $currency))
			throw new InvalidArgumentException('Reference must be 1-30 alphanumeric characters, amount a positive integer and currency a three-letter code.');
	}

	public function checkOrderId($id)
	{
		if(!is_string($id) || !preg_match('/\A\/psp\/paymentorders\/[a-z0-9-]+\z/i', $id))
			throw new RuntimeException('Unexpected order resource.');
	}

	private function _checkOrder($response, $amount, $currency)
	{
		if(!isset($response['paymentOrder']['id'], $response['paymentOrder']['amount'],
			$response['paymentOrder']['currency'], $response['paymentOrder']['status']) ||
			$response['paymentOrder']['amount'] !== $amount ||
			$response['paymentOrder']['currency'] !== $currency ||
			!is_string($response['paymentOrder']['status']))
			throw new RuntimeException('Malformed order or amount/currency mismatch.');
	}

	private function _request($method, $path, $payload=null)
	{
		// Never accept an arbitrary absolute URL or follow redirects with the token.
		if(!preg_match('/\A\/psp\/paymentorders(?:\/[a-z0-9-]+(?:\/(?:payeeinfo|paid|financialtransactions|failedattempts|captures))?)?\z/i', $path))
			throw new RuntimeException('Unexpected API resource.');
		$body=$payload === null ? null : json_encode($payload);
		if($body === false)
			throw new RuntimeException('Request encoding failed.');
		$started=microtime(true);
		// Log resource kind only, never URLs, bodies, headers or arbitrary exception text.
		$resource=preg_match('~/([a-z]+)$~',$path,$match) ? $match[1] : 'order';
		$this->_trace('provider_request_started',array('method'=>$method,'resource'=>$resource));
		try
		{
			$result=$this->send($method, $this->_apiOrigin().$path, array(
				'Authorization: Bearer '.$this->accessToken,
				'Content-Type: application/json;version=3.1',
				'Accept: application/json;version=3.1',
			), $body);
		}
		catch(Exception $e)
		{
			$this->_failureReason='transport_error';
			$this->_trace('provider_request_failed',array('method'=>$method,'resource'=>$resource,
				'reason'=>$this->_failureReason,'elapsedMs'=>$this->_elapsed($started)));
			throw $e;
		}
		$this->_trace('provider_request_finished',array('method'=>$method,'resource'=>$resource,
			'httpStatus'=>(int)$result['status'],'elapsedMs'=>$this->_elapsed($started)));
		// This recovery applies only to order creation, never to capture POSTs.
		if($method === 'POST' && $path === '/psp/paymentorders' && $result['status'] === 409)
		{
			$problem=json_decode($result['body'], true);
			if(isset($problem['type'], $problem['problems']) && is_array($problem['problems']) &&
				$problem['type'] === 'https://api.swedbankpay.com/psp/errordetail/paymentorders/conflict')
			{
				$ids=array();
				foreach($problem['problems'] as $field)
					if(isset($field['name'], $field['description']) && $field['name'] === 'ConflictingPaymentOrderId')
						$ids[]=$field['description'];
				if(count($ids) === 1 && is_string($ids[0]) &&
					preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $ids[0]))
					return array('conflictingOrderId'=>'/psp/paymentorders/'.$ids[0]);
			}
		}
		// Never include response bodies, headers or transport diagnostics in errors.
		if($result['status'] < 200 || $result['status'] >= 300)
		{
			$this->_failureReason='provider_http_error';
			throw new RuntimeException('Swedbank Pay HTTP '.$result['status'].'. No automatic retry; reconcile before repeating POST.');
		}
		$decoded=json_decode($result['body'], true);
		if(!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE)
		{
			$this->_failureReason='invalid_json';
			throw new RuntimeException('Invalid JSON response. No automatic retry.');
		}
		return $decoded;
	}

	/** Transport seam for offline tests; no application bootstrap is needed. */
	protected function send($method, $url, $headers, $body)
	{
		$curl=curl_init($url);
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_FOLLOWLOCATION=>false,
			CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
			CURLOPT_SSL_VERIFYPEER=>true,
			CURLOPT_SSL_VERIFYHOST=>2,
			CURLOPT_CONNECTTIMEOUT=>10,
			CURLOPT_TIMEOUT=>30,
			CURLOPT_USERAGENT=>'XSwedbankPay/1.0',
			CURLOPT_HTTPHEADER=>$headers,
		));
		if($method === 'POST')
		{
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
		}
		$response=curl_exec($curl);
		$error=curl_errno($curl);
		$status=curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if($response === false || $error)
			throw new RuntimeException('Swedbank Pay transport failed (cURL '.$error.'). Outcome uncertain; creation retries require the same reference; reconcile capture before retrying.');
		return array('status'=>$status, 'body'=>$response);
	}

	/** Only call with fixed event names and explicitly selected scalar diagnostics. */
	private function _trace($event, array $data=array())
	{
		if($this->traceContext !== '')
			Yii::log('[Swedbank Pay '.$this->traceContext.'] event='.$event.' stage='.$this->_confirmationStage.
				' data='.json_encode($data),'trace','payment');
	}

	private function _elapsed($started)
	{
		return (int)round((microtime(true)-$started)*1000);
	}

	private function _apiOrigin()
	{
		return $this->testMode ? self::ORIGIN : 'https://api.swedbankpay.com';
	}

	private function _checkoutOrigin()
	{
		return $this->testMode ? 'https://swedbank-checkout.externalintegration.swedbankpay.com' : 'https://swedbank-checkout.swedbankpay.com';
	}

	private function _checkVat($vatAmount, $amount)
	{
		if(!is_int($vatAmount) || $vatAmount < 0 || $vatAmount > $amount)
			throw new InvalidArgumentException('VAT must be integer minor units between zero and the total amount.');
	}

	private function _checkCreateOptions(array $options, $amount)
	{
		foreach(array('description','userAgent') as $field)
			if(!isset($options[$field]) || !is_string($options[$field]) || trim($options[$field]) === '')
				throw new InvalidArgumentException('Description and actual payer user agent are required.');
		if(!isset($options['vatAmount']))
			throw new InvalidArgumentException('VAT amount is required.');
		$this->_checkVat($options['vatAmount'], $amount);
		if(isset($options['language']) && (!is_string($options['language']) || !preg_match('/\A[a-z]{2}-[A-Z]{2}\z/', $options['language'])))
			throw new InvalidArgumentException('Invalid language.');
		if(!isset($options['urls']) || !is_array($options['urls']) ||
			!isset($options['urls']['hostUrls'], $options['urls']['completeUrl']) ||
			!is_array($options['urls']['hostUrls']) || !$options['urls']['hostUrls'])
			throw new InvalidArgumentException('Host and completion URLs are required.');
		foreach($options['urls'] as $name=>$value)
		{
			if(!in_array($name, array('hostUrls','completeUrl','cancelUrl','callbackUrl','termsOfServiceUrl','logoUrl'), true))
				throw new InvalidArgumentException('Unsupported checkout URL option.');
			foreach($name === 'hostUrls' ? $value : array($value) as $url)
			{
				$parts=is_string($url) ? parse_url($url) : false;
				if(!$parts || !isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'https' ||
					isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) ||
					preg_match('/[\x00-\x20\x7f]/', $url))
					throw new InvalidArgumentException('Checkout URLs must be absolute HTTPS URLs without credentials or fragments.');
			}
		}
	}
	/** Exact decimal conversion, independent of floating point rounding. */
	public static function minorUnits($total)
	{
		if(!preg_match('/\A([0-9]+)(?:\.([0-9]+))?\z/', (string)$total, $parts))
			throw new CException('Invalid payment amount.');
		$fraction=isset($parts[2]) ? rtrim($parts[2],'0') : '';
		if(strlen($fraction)>2)
			throw new CException('Payment amount has fractional cents.');
		$minor=ltrim($parts[1].str_pad($fraction,2,'0'),'0');
		if($minor === '' || strlen($minor)>10 || (strlen($minor)===10 && strcmp($minor,'2147483647')>0))
			throw new CException('Payment amount is out of range.');
		return (int)$minor;
	}
}
