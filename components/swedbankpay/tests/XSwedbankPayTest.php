<?php
// Run with PHPUnit 5.7 --no-configuration; loads Yii classes only, no application.
// Set YII_FRAMEWORK to a Yii 1.1 yii.php when installed elsewhere.
require_once getenv('YII_FRAMEWORK') ?: dirname(__FILE__).'/../../../../yii-1.1.8/yii.php';
require_once dirname(__FILE__).'/../XSwedbankPay.php';
require_once dirname(__FILE__).'/../XSwedbankPayWebhookAction.php';

class XSwedbankPayFakeClient extends XSwedbankPay
{
	public $calls=array();
	public $responses=array();

	public function __construct($payeeId, $accessToken, $enabled=false)
	{
		$this->payeeId=$payeeId;
		$this->accessToken=$accessToken;
		$this->enabled=$enabled;
	}

	protected function send($method, $url, $headers, $body)
	{
		$this->calls[]=array($method, $url, $headers, $body);
		$result=array_shift($this->responses);
		if($result instanceof Exception)
			throw $result;
		return $result;
	}
}

class XSwedbankPayTestModule extends CModule { }

class XSwedbankPayTest extends PHPUnit_Framework_TestCase
{
	private $payee='00000000-0000-0000-0000-000000000001';
	private $id='/psp/paymentorders/00000000-0000-0000-0000-000000000002';

	private function client($enabled=true)
	{
		return new XSwedbankPayFakeClient($this->payee, 'offline-test-token', $enabled);
	}

	private function order()
	{
		return array('paymentOrder'=>array(
			'id'=>$this->id, 'amount'=>100, 'currency'=>'EUR', 'status'=>'Initialized',
			'payeeInfo'=>array('id'=>$this->id.'/payeeinfo'),
		));
	}

	private function ok($value)
	{
		return array('status'=>200, 'body'=>json_encode($value));
	}

	public function testDisabledByDefaultAndTruthyValuesCannotEnable()
	{
		foreach(array(false, 1, 'true') as $enabled)
		{
			$client=$this->client($enabled);
			try { $client->createOrder('VAUTest1', 100, $this->options()); $this->fail('Disabled client sent a request.'); }
			catch(RuntimeException $e) { $this->assertContains('disabled', $e->getMessage()); }
			$this->assertCount(0, $client->calls);
		}
		$client=new XSwedbankPayFakeClient($this->payee, 'offline-test-token');
		try { $client->readOrder($this->id, 'VAUTest1', 100); $this->fail('Default enabled.'); }
		catch(RuntimeException $e) { $this->assertCount(0, $client->calls); }
	}

	public function testTermsAndLogoUrlsAreSentUnchangedAndValidated()
	{
		foreach(array('termsOfServiceUrl','logoUrl') as $field)
		{
			$options=$this->options();
			$options['urls'][$field]='https://shop.example/public/resource?lang=et';
			$client=$this->client();
			$client->validateCreate('VAUTest1',100,$options);
			$this->assertCount(0,$client->calls);
			$client->responses=array($this->ok($this->order()));
			$client->createOrder('VAUTest1',100,$options);
			$body=json_decode($client->calls[0][3],true);
			$this->assertSame($options['urls'][$field],$body['paymentOrder']['urls'][$field]);
			foreach(array('http://shop.example/a','//shop.example/a','https://user:secret@shop.example/a','https://shop.example/a#fragment',"https://shop.example/a\n",array('https://shop.example/a')) as $invalid)
			{
				$options['urls'][$field]=$invalid;
				$client=$this->client();
				try { $client->createOrder('VAUTest1',100,$options); $this->fail('Invalid URL accepted.'); }
				catch(InvalidArgumentException $e) { $this->assertCount(0,$client->calls); }
			}
		}
	}

	public function testYiiComponentRegistrationPreservesExplicitRequestOptIn()
	{
		$module=new XSwedbankPayTestModule('swedbank-test',null,array('components'=>array(
			'blocked'=>array('class'=>'XSwedbankPay','enabled'=>true),
			'allowed'=>array('class'=>'XSwedbankPay','allowRequests'=>true),
			'truthy'=>array('class'=>'XSwedbankPay','allowRequests'=>'true'),
		)));
		$this->assertFalse($module->getComponent('blocked')->enabled);
		$this->assertTrue($module->getComponent('allowed')->enabled);
		try { $module->getComponent('truthy')->validateCreate('Test',100,$this->options()); $this->fail('Truthy opt-in accepted.'); }
		catch(RuntimeException $e) { $this->assertContains('disabled',$e->getMessage()); }
	}

	public function testCreatesOrderWithVersionAndSuppliedPayerContext()
	{
		$client=$this->client();
		$client->responses=array($this->ok($this->order()));
		$result=$client->createOrder('VAUTest1', 100, $this->options());
		$this->assertSame('Initialized', $result['paymentOrder']['status']);
		$this->assertCount(1, $client->calls);
		$call=$client->calls[0];
		$this->assertSame('POST', $call[0]);
		$this->assertSame(XSwedbankPay::ORIGIN.'/psp/paymentorders', $call[1]);
		$this->assertContains('Content-Type: application/json;version=3.1', $call[2]);
		$order=json_decode($call[3], true)['paymentOrder'];
		$this->assertArrayNotHasKey('restrictedToInstruments', $order);
		$this->assertSame(100, $order['amount']);
		$this->assertSame('EUR', $order['currency']);
		$this->assertSame('VAUTest1', $order['payeeInfo']['payeeReference']);
		$this->assertArrayNotHasKey('callbackUrl', $order['urls']);
		$this->assertArrayNotHasKey('payer', $order);
	}

	public function invalidInputProvider()
	{
		return array(array('bad/ref',100),array('ok',1.0),array('ok','100'),array('ok',0),array('ok',-1));
	}

	/** @dataProvider invalidInputProvider */
	public function testInvalidInputCannotSend($reference, $amount)
	{
		$client=$this->client();
		try { $client->createOrder($reference, $amount, $this->options()); $this->fail('Input accepted.'); }
		catch(InvalidArgumentException $e) { $this->assertCount(0, $client->calls); }
	}

	public function unsafeResourceProvider()
	{
		return array(array('https://api.swedbankpay.com/psp/paymentorders/abc'),array('//evil.test/x'),
			array('/psp/paymentorders/../other'),array('/psp/paymentorders/abc?x=1'),array('/psp/paymentorders/abc%2fother'));
	}

	/** @dataProvider unsafeResourceProvider */
	public function testUnsafeResourceCannotReceiveCredentials($id)
	{
		$client=$this->client();
		try { $client->readOrder($id, 'VAUTest1', 100); $this->fail('Resource accepted.'); }
		catch(RuntimeException $e) { $this->assertCount(0, $client->calls); }
	}

	public function testReadBindsIdAmountCurrencyPayeeAndReference()
	{
		$client=$this->client();
		$client->responses=array($this->ok($this->order()), $this->ok(array('payeeInfo'=>array(
			'payeeId'=>$this->payee, 'payeeReference'=>'VAUTest1'))));
		$result=$client->readOrder($this->id, 'VAUTest1', 100);
		$this->assertSame('Initialized', $result['paymentOrder']['status']);
		$this->assertCount(2, $client->calls);
		$this->assertSame('GET', $client->calls[0][0]);
		$this->assertContains('Accept: application/json;version=3.1', $client->calls[0][2]);
	}

	public function mismatchProvider()
	{
		return array(array('id','/psp/paymentorders/another'),array('amount',99),array('currency','SEK'),
			array('status',null),array('payeeInfo',array('id'=>'https://evil.test/payeeinfo')));
	}

	/** @dataProvider mismatchProvider */
	public function testMismatchedOrderStopsBeforePayeeRequest($field, $value)
	{
		$client=$this->client(); $order=$this->order(); $order['paymentOrder'][$field]=$value;
		$client->responses=array($this->ok($order));
		try { $client->readOrder($this->id, 'VAUTest1', 100); $this->fail('Mismatch accepted.'); }
		catch(RuntimeException $e) { $this->assertCount(1, $client->calls); }
	}

	public function testPayeeAndReferenceMismatchesAreRejected()
	{
		foreach(array(array('payeeId'=>'other','payeeReference'=>'VAUTest1'),
			array('payeeId'=>$this->payee,'payeeReference'=>'other')) as $payee)
		{
			$client=$this->client();
			$client->responses=array($this->ok($this->order()), $this->ok(array('payeeInfo'=>$payee)));
			try { $client->readOrder($this->id, 'VAUTest1', 100); $this->fail('Mismatch accepted.'); }
			catch(RuntimeException $e) { $this->assertSame('Payee or reference mismatch.', $e->getMessage()); }
		}
	}

	public function testFailuresAreSanitizedAndNeverRetried()
	{
		foreach(array(302,401,429,500,200) as $status)
		{
			$client=$this->client();
			$client->responses=array(array('status'=>$status,'body'=>'private-body-secret'));
			try { $client->createOrder('VAUTest1',100, $this->options()); $this->fail('Bad response accepted.'); }
			catch(RuntimeException $e) { $this->assertNotContains('private-body-secret',$e->getMessage()); }
			$this->assertCount(1,$client->calls);
		}
		$client=$this->client();
		$client->responses=array(new RuntimeException('Simulated timeout'));
		try { $client->createOrder('VAUTest1',100, $this->options()); $this->fail('Timeout accepted.'); }
		catch(RuntimeException $e) { $this->assertCount(1,$client->calls); }
	}

	public function testMissingCredentialsAndHeaderInjectionCannotSend()
	{
		foreach(array('', "token\r\nInjected: value") as $token)
		{
			$client=new XSwedbankPayFakeClient($this->payee, $token, true);
			try { $client->createOrder('VAUTest1',100, $this->options()); $this->fail('Bad credentials accepted.'); }
			catch(RuntimeException $e) { $this->assertCount(0,$client->calls); }
		}
	}

	public function testMalformedCreateResponseIsNotRetried()
	{
		foreach(array(array(), array('paymentOrder'=>array('id'=>'https://evil.test/order'))) as $response)
		{
			$client=$this->client(); $client->responses=array($this->ok($response));
			try { $client->createOrder('VAUTest1',100, $this->options()); $this->fail('Malformed response accepted.'); }
			catch(RuntimeException $e) { $this->assertCount(1,$client->calls); }
		}
	}

	private function payeeResponse()
	{
		return $this->ok(array('payeeInfo'=>array('payeeId'=>$this->payee, 'payeeReference'=>'VAUTest1')));
	}

	public function checkoutUrlProvider()
	{
		return array(
			array('https://swedbank-checkout.externalintegration.swedbankpay.com/menu/test?x=1',true),
			array('https://swedbank-checkout.swedbankpay.com/menu/test',false),
			array('https://swedbank-checkout.externalintegration.swedbankpay.com.evil.test/menu/test',false),
			array('http://swedbank-checkout.externalintegration.swedbankpay.com/menu/test',false),
			array('https://evil.test/menu/test',false),
		);
	}

	/** @dataProvider checkoutUrlProvider */
	public function testCheckoutUsesOnlyVerifiedSandboxOrigin($url,$allowed)
	{
		$client=$this->client(); $order=$this->order();
		$order['operations']=array(array('rel'=>'redirect-checkout','method'=>'GET','href'=>$url));
		$client->responses=array($this->ok($order),$this->payeeResponse());
		if(!$allowed) $this->expectException(RuntimeException::class);
		$this->assertSame($url,$client->checkoutUrl($this->id,'VAUTest1',100));
	}

	public function testPaidOrderCannotBeOpenedForAnotherCheckout()
	{
		$client=$this->client(); $order=$this->order(); $order['paymentOrder']['status']='Paid';
		$client->responses=array($this->ok($order),$this->payeeResponse());
		$this->expectException(RuntimeException::class);
		$client->checkoutUrl($this->id,'VAUTest1',100);
	}

	private function evidenceResponses($status='Paid')
	{
		$order=$this->order(); $order['paymentOrder']['status']=$status;
		foreach(array('paid','financialTransactions','failedAttempts') as $resource)
			$order['paymentOrder'][$resource]=array('id'=>$this->id.'/'.strtolower($resource));
		$order['paymentOrder']['remainingCaptureAmount']=100;
		return array($this->ok($order),$this->payeeResponse(),
			$this->ok(array('financialTransactions'=>array('id'=>$this->id.'/financialtransactions','financialTransactionsList'=>array()))),
			$this->ok(array('failedAttempts'=>array('id'=>$this->id.'/failedattempts','failedAttemptList'=>array(
				array('status'=>'Failed','problem'=>array('type'=>'carddeclined','status'=>403,'detail'=>'private detail',
					'problems'=>array(array('name'=>'INELIGIBLE_CARD_RANGE','description'=>'private card data'),
						array('name'=>'private detail with spaces')))))))),
			$this->ok(array('paid'=>array('id'=>$this->id.'/paid','amount'=>100,'payeeReference'=>'VAUTest1',
				'instrument'=>'CreditCard','transactionType'=>'Authorization','details'=>array('private'=>'card detail'),
				'paymentToken'=>'private token'))));
	}

	public function testPaidAuthorizationIsNotReportedAsCaptureAndSecretsAreExcluded()
	{
		$client=$this->client(); $client->responses=$this->evidenceResponses();
		$evidence=$client->paymentEvidence($this->id,'VAUTest1',100);
		$this->assertSame('Authorization',$evidence['paid']['transactionType']);
		$this->assertSame(100,$evidence['remainingCaptureAmount']);
		$this->assertSame(array(),$evidence['financialTransactions']);
		$this->assertNotContains('private',json_encode($evidence));
		foreach($client->calls as $call) $this->assertSame('GET',$call[0]);
	}

	public function testFailedAttemptCanLeaveOrderInitializedWithNoFinancialTransactions()
	{
		$client=$this->client(); $client->responses=$this->evidenceResponses('Initialized');
		$evidence=$client->paymentEvidence($this->id,'VAUTest1',100);
		$this->assertSame('Initialized',$evidence['status']);
		$this->assertSame(array(),$evidence['financialTransactions']);
		$this->assertSame('Failed',$evidence['failedAttempts'][0]['status']);
		$this->assertSame(array('INELIGIBLE_CARD_RANGE'),$evidence['failedAttempts'][0]['problem']['codes']);
		$this->assertNotContains('private',json_encode($evidence));
		$this->assertArrayNotHasKey('paid',$evidence);
		$this->assertCount(4,$client->calls);
	}

	public function testEvidenceResourceMismatchIsRejected()
	{
		$client=$this->client(); $client->responses=$this->evidenceResponses();
		$client->responses[2]=$this->ok(array('financialTransactions'=>array('id'=>'/psp/paymentorders/other/financialtransactions')));
		$this->expectException(RuntimeException::class);
		$client->paymentEvidence($this->id,'VAUTest1',100);
	}

	public function testPaidReferenceMismatchIsRejected()
	{
		$client=$this->client(); $client->responses=$this->evidenceResponses();
		$data=json_decode($client->responses[4]['body'],true); $data['paid']['payeeReference']='another';
		$client->responses[4]=$this->ok($data);
		$this->expectException(RuntimeException::class);
		$client->paymentEvidence($this->id,'VAUTest1',100);
	}

	public function testMissingFinancialListIsNotInterpretedAsZeroCaptures()
	{
		$client=$this->client(); $client->responses=$this->evidenceResponses();
		$client->responses[2]=$this->ok(array('financialTransactions'=>array('id'=>$this->id.'/financialtransactions')));
		$this->expectException(RuntimeException::class);
		$client->paymentEvidence($this->id,'VAUTest1',100);
	}

	private function captureResponses()
	{
		$before=$this->evidenceResponses();
		$order=json_decode($before[0]['body'],true);
		$order['paymentOrder']['vatAmount']=0;
		$order['operations']=array(array('rel'=>'capture','method'=>'POST',
			'href'=>XSwedbankPay::ORIGIN.$this->id.'/captures'));
		$after=$this->evidenceResponses();
		$captured=$order;
		$captured['paymentOrder']['remainingCaptureAmount']=0;
		$captured['paymentOrder']['remainingCancellationAmount']=0;
		$captured['paymentOrder']['remainingReversalAmount']=100;
		$captured['operations']=array();
		$after[0]=$this->ok($captured);
		$after[2]=$this->ok(array('financialTransactions'=>array('id'=>$this->id.'/financialtransactions',
			'financialTransactionsList'=>array(array('type'=>'Capture','amount'=>100,
				'number'=>123,'payeeReference'=>'VAUTest1C1')))));
		return array_merge($before,array($this->ok($order),$this->payeeResponse(),$this->ok($captured)),$after);
	}

	public function testCapturePostsOnceAndVerifiesSeparateReadBack()
	{
		$client=$this->client(); $client->responses=$this->captureResponses();
		$result=$client->captureOrder($this->id,'VAUTest1',100, 'VAUTest1C1');
		$this->assertTrue($result['captureVerified']);
		$this->assertSame('VAUTest1C1',$result['captureReference']);
		$this->assertCount(13,$client->calls);
		foreach($client->calls as $index=>$call) $this->assertSame($index === 7 ? 'POST' : 'GET',$call[0]);
		$this->assertSame(XSwedbankPay::ORIGIN.$this->id.'/captures',$client->calls[7][1]);
		$transaction=json_decode($client->calls[7][3],true)['transaction'];
		$this->assertSame(100,$transaction['amount']);
		$this->assertSame(0,$transaction['vatAmount']);
		$this->assertSame('VAUTest1C1',$transaction['payeeReference']);
		$this->assertNotContains('private',json_encode($result));
	}

	public function capturePreconditionProvider()
	{
		return array(array('disabled'),array('alreadyCaptured'),array('wrongPayee'),array('wrongReference'),
			array('notAuthorization'),array('balanceChanged'),array('foreignOperation'),array('noOperation'),array('vat'));
	}

	/** @dataProvider capturePreconditionProvider */
	public function testUnsafeCaptureDoesNotPost($scenario)
	{
		$client=$this->client($scenario !== 'disabled'); $responses=$this->captureResponses();
		if($scenario === 'alreadyCaptured') $responses[0]=$responses[8];
		if($scenario === 'wrongPayee' || $scenario === 'wrongReference')
		{
			$data=json_decode($responses[1]['body'],true);
			$data['payeeInfo'][$scenario === 'wrongPayee' ? 'payeeId' : 'payeeReference']='other';
			$responses[1]=$this->ok($data);
		}
		if($scenario === 'notAuthorization')
		{
			$data=json_decode($responses[4]['body'],true); $data['paid']['transactionType']='Sale';
			$responses[4]=$this->ok($data);
		}
		$order=json_decode($responses[5]['body'],true);
		if($scenario === 'balanceChanged') $order['paymentOrder']['remainingCaptureAmount']=0;
		if($scenario === 'foreignOperation') $order['operations'][0]['href']='https://api.swedbankpay.com'.$this->id.'/captures';
		if($scenario === 'noOperation') $order['operations']=array();
		if($scenario === 'vat') $order['paymentOrder']['vatAmount']=20;
		$responses[5]=$this->ok($order); $client->responses=$responses;
		try { $client->captureOrder($this->id,'VAUTest1',100, 'VAUTest1C1'); $this->fail('Unsafe capture accepted.'); }
		catch(RuntimeException $e) { foreach($client->calls as $call) $this->assertSame('GET',$call[0]); }
	}

	public function testCaptureUncertainPostIsNeverRetried()
	{
		foreach(array(new RuntimeException('Simulated timeout'),array('status'=>502,'body'=>'private response'),
			array('status'=>200,'body'=>'malformed')) as $failure)
		{
			$client=$this->client(); $client->responses=$this->captureResponses(); $client->responses[7]=$failure;
			try { $client->captureOrder($this->id,'VAUTest1',100, 'VAUTest1C1'); $this->fail('Uncertain capture accepted.'); }
			catch(RuntimeException $e) { $this->assertNotContains('private',$e->getMessage()); }
			$this->assertCount(8,$client->calls);
		}
	}

	public function captureMismatchProvider()
	{
		return array(array('missingTransaction'),array('wrongReference'),array('wrongAmount'),array('wrongType'),
			array('balance'),array('missingReversal'),array('absentBalancesWithoutTransaction'));
	}

	/** @dataProvider captureMismatchProvider */
	public function testAcceptedCaptureStillRequiresMatchingTransactionAndBalances($scenario)
	{
		$client=$this->client(); $responses=$this->captureResponses();
		$data=json_decode($responses[10]['body'],true);
		if($scenario === 'missingTransaction') $data['financialTransactions']['financialTransactionsList']=array();
		if($scenario === 'wrongReference') $data['financialTransactions']['financialTransactionsList'][0]['payeeReference']='other';
		if($scenario === 'wrongAmount') $data['financialTransactions']['financialTransactionsList'][0]['amount']=99;
		if($scenario === 'wrongType') $data['financialTransactions']['financialTransactionsList'][0]['type']='Authorization';
		$responses[10]=$this->ok($data);
		if($scenario === 'balance') $responses[8]=$responses[0];
		if($scenario === 'missingReversal' || $scenario === 'absentBalancesWithoutTransaction')
		{
			$order=json_decode($responses[8]['body'],true);
			unset($order['paymentOrder']['remainingCaptureAmount'], $order['paymentOrder']['remainingCancellationAmount']);
			if($scenario === 'missingReversal') unset($order['paymentOrder']['remainingReversalAmount']);
			else $responses[10]=$responses[2];
			$responses[8]=$this->ok($order);
		}
		$client->responses=$responses;
		try { $client->captureOrder($this->id,'VAUTest1',100, 'VAUTest1C1'); $this->fail('Capture falsely verified.'); }
		catch(RuntimeException $e) { $this->assertContains('Do not repeat POST',$e->getMessage()); }
		$this->assertCount(13,$client->calls);
	}

	public function testReadOnlyCaptureVerificationAcceptsOmittedExhaustedBalancesWithPositiveEvidence()
	{
		$client=$this->client(); $responses=array_slice($this->captureResponses(),8);
		$order=json_decode($responses[0]['body'],true);
		unset($order['paymentOrder']['remainingCaptureAmount'], $order['paymentOrder']['remainingCancellationAmount']);
		$responses[0]=$this->ok($order); $client->responses=$responses;
		$result=$client->verifyCapture($this->id,'VAUTest1',100, 'VAUTest1C1');
		$this->assertTrue($result['captureVerified']);
		$this->assertNull($result['remainingCaptureAmount']);
		$this->assertSame(100,$result['remainingReversalAmount']);
		$this->assertCount(5,$client->calls);
		foreach($client->calls as $call) $this->assertSame('GET',$call[0]);
	}

	private function options()
	{
		return array('description'=>'Offline checkout', 'userAgent'=>'Actual payer browser',
			'vatAmount'=>0, 'urls'=>array('hostUrls'=>array('https://shop.example'),
			'completeUrl'=>'https://shop.example/complete', 'cancelUrl'=>'https://shop.example/cancel'));
	}

	public function testYiiConfigurationAndNonSyntheticAmountCurrencyVat()
	{
		$component=Yii::createComponent(array('class'=>'XSwedbankPay'));
		$component->init();
		$this->assertTrue($component->isInitialized);
		$this->assertFalse($component->enabled);
		$this->assertTrue($component->testMode);
		$client=$this->client();
		$order=$this->order(); $order['paymentOrder']['amount']=12500; $order['paymentOrder']['currency']='SEK';
		$client->responses=array($this->ok($order),$this->ok($order),$this->payeeResponse());
		$options=$this->options(); $options['currency']='SEK'; $options['vatAmount']=2500;
		$options['urls']['callbackUrl']='https://shop.example/callback';
		$client->createOrder('VAUTest1',12500,$options);
		$sent=json_decode($client->calls[0][3],true)['paymentOrder'];
		$this->assertSame(12500,$sent['amount']);
		$this->assertSame(2500,$sent['vatAmount']);
		$this->assertSame('Actual payer browser',$sent['userAgent']);
		$this->assertSame($options['urls'],$sent['urls']);
		$client->readOrder($this->id,'VAUTest1',12500,'SEK');
		$this->assertCount(3,$client->calls);
	}

	public function testInvalidCreationOptionsCannotPost()
	{
		$invalid=array();
		foreach(array('description','userAgent','vatAmount','urls') as $field)
		{ $options=$this->options(); unset($options[$field]); $invalid[]=$options; }
		foreach(array(-1,101,'0') as $vat)
		{ $options=$this->options(); $options['vatAmount']=$vat; $invalid[]=$options; }
		foreach(array('http://shop.example','https://user:pass@shop.example','https://shop.example/#fragment',"https://shop.example/\n") as $url)
		{ $options=$this->options(); $options['urls']['callbackUrl']=$url; $invalid[]=$options; }
		$options=$this->options(); $options['currency']='eur'; $invalid[]=$options;
		$options=$this->options(); $options['language']=array(); $invalid[]=$options;
		foreach($invalid as $options)
		{
			$client=$this->client();
			try { $client->createOrder('VAUTest1',100,$options); $this->fail('Bad options accepted.'); }
			catch(InvalidArgumentException $e) { $this->assertCount(0,$client->calls); }
		}
	}

	public function testExplicitCaptureReferenceAndVatAreUsed()
	{
		$client=$this->client(); $responses=$this->captureResponses();
		foreach($responses as &$response)
			$response['body']=str_replace('VAUTest1C1','SavedCapture42',$response['body']);
		unset($response);
		$order=json_decode($responses[5]['body'],true); $order['paymentOrder']['vatAmount']=20;
		$responses[5]=$this->ok($order); $client->responses=$responses;
		$evidence=$client->captureOrder($this->id,'VAUTest1',100,'SavedCapture42','EUR',20);
		$sent=json_decode($client->calls[7][3],true)['transaction'];
		$this->assertSame(20,$sent['vatAmount']);
		$this->assertSame('SavedCapture42',$sent['payeeReference']);
		$this->assertTrue($evidence['captureVerified']);
	}

	public function testCaptureRequiresDistinctValidReferenceBeforeNetwork()
	{
		foreach(array('VAUTest1','bad/ref') as $reference)
		{
			$client=$this->client();
			try { $client->captureOrder($this->id,'VAUTest1',100,$reference); $this->fail('Bad capture reference.'); }
			catch(InvalidArgumentException $e) { $this->assertCount(0,$client->calls); }
		}
	}

	private function action($responses)
	{
		$controller=new XSwedbankPayTestController('offline');
		$controller->binding=array('orderId'=>$this->id,'reference'=>'VAUTest1','amount'=>100,
			'currency'=>'EUR','captureReference'=>'VAUTest1C1');
		$action=new XSwedbankPayTestAction($controller,'callback');
		$action->bindingCallback='bindingForOrder'; $action->verifiedCallback='recordEvidence';
		$action->component=$this->client(); $action->component->responses=$responses;
		return $action;
	}

	private function notification()
	{
		return json_encode(array('paymentOrder'=>array('id'=>$this->id),
			'status'=>'Paid','amount'=>1,'payeeReference'=>'attacker','captureVerified'=>true));
	}

	public function testCallbackAuthorizationDoesNotBecomeCaptureOrMakeWrites()
	{
		$action=$this->action($this->evidenceResponses());
		$this->assertSame(200,$action->processCallback('POST',$this->notification()));
		$evidence=$action->controller->received[0];
		$this->assertSame(100,$evidence['amount']);
		$this->assertSame('VAUTest1',$evidence['reference']);
		$this->assertFalse($evidence['captureVerified']);
		$this->assertSame('Authorization',$evidence['paid']['transactionType']);
		foreach($action->component->calls as $call) $this->assertSame('GET',$call[0]);
	}

	public function testDuplicateAndReorderedCallbacksFetchFreshState()
	{
		$captured=array_slice($this->captureResponses(),8);
		$action=$this->action(array_merge($this->evidenceResponses(),$captured,$captured));
		for($i=0;$i<3;$i++)
			$this->assertSame(200,$action->processCallback('POST',$this->notification()));
		$seen=$action->controller->received;
		$this->assertFalse($seen[0]['captureVerified']);
		$this->assertTrue($seen[1]['captureVerified']);
		$this->assertTrue($seen[2]['captureVerified']);
		foreach($action->component->calls as $call) $this->assertSame('GET',$call[0]);
	}

	public function testCallbackDoesNotAcknowledgeReadOrHandlerFailures()
	{
		foreach(array('transport','wrongPayee','wrongAmount','handlerThrows','handlerFalse','resolverThrows','badBinding','disabled','missingHandler') as $scenario)
		{
			$action=$this->action($this->evidenceResponses());
			if($scenario === 'transport') $action->component->responses=array(new RuntimeException('private transport'));
			if($scenario === 'wrongPayee') $action->component->responses[1]=$this->ok(array('payeeInfo'=>array('payeeId'=>'wrong','payeeReference'=>'VAUTest1')));
			if($scenario === 'wrongAmount') $action->controller->binding['amount']=99;
			if($scenario === 'handlerThrows') $action->controller->handlerResult=new RuntimeException('private handler');
			if($scenario === 'handlerFalse') $action->controller->handlerResult=false;
			if($scenario === 'resolverThrows') $action->controller->binding=new RuntimeException('private resolver');
			if($scenario === 'badBinding') $action->controller->binding['orderId']='/psp/paymentorders/other';
			if($scenario === 'disabled') $action->component->enabled=false;
			if($scenario === 'missingHandler') $action->verifiedCallback=null;
			$this->assertSame(500,$action->processCallback('POST',$this->notification()),$scenario);
			if(!in_array($scenario,array('handlerThrows','handlerFalse'),true))
				$this->assertCount(0,$action->controller->received);
			foreach($action->component->calls as $call) $this->assertSame('GET',$call[0]);
		}
	}

	public function testMalformedAndUnknownCallbacksCannotReachProvider()
	{
		foreach(array('{}','bad json',json_encode(array('paymentOrder'=>array('id'=>'https://evil.example'))),str_repeat('x',16385)) as $body)
		{
			$action=$this->action(array());
			$this->assertSame(400,$action->processCallback('POST',$body));
			$this->assertCount(0,$action->component->calls);
		}
		$action=$this->action(array());
		$this->assertSame(405,$action->processCallback('GET',$this->notification()));
		$action->controller->binding=null;
		$this->assertSame(404,$action->processCallback('POST',$this->notification()));
		$this->assertCount(0,$action->component->calls);
	}
	public function testProductionOriginSelectionUsesOnlyFakeTransport()
	{
		$client=$this->client(); $client->testMode=false;
		$order=$this->order();
		$order['operations']=array(array('rel'=>'redirect-checkout','method'=>'GET',
			'href'=>'https://swedbank-checkout.swedbankpay.com/menu/offline'));
		$client->responses=array($this->ok($order),$this->payeeResponse());
		$this->assertSame('https://swedbank-checkout.swedbankpay.com/menu/offline',$client->checkoutUrl($this->id,'VAUTest1',100));
		$this->assertSame('https://api.swedbankpay.com'.$this->id,$client->calls[0][1]);
		$client=$this->client(); $client->testMode='false';
		try { $client->readOrder($this->id,'VAUTest1',100); $this->fail('Ambiguous environment accepted.'); }
		catch(RuntimeException $e) { $this->assertCount(0,$client->calls); }
	}

	public function testMissingOrWrongCaptureIntentCannotVerifyCallbackCapture()
	{
		foreach(array(null,'OtherCapture') as $reference)
		{
			$action=$this->action(array_slice($this->captureResponses(),8));
			$action->controller->binding['captureReference']=$reference;
			$this->assertSame(200,$action->processCallback('POST',$this->notification()));
			$this->assertFalse($action->controller->received[0]['captureVerified']);
		}
	}

	public function testNonCardPaidEvidenceIsRejectedBeforeApplicationHandler()
	{
		$responses=$this->evidenceResponses();
		$paid=json_decode($responses[4]['body'],true); $paid['paid']['instrument']='BankLink';
		$responses[4]=$this->ok($paid);
		$action=$this->action($responses);
		$this->assertSame(500,$action->processCallback('POST',$this->notification()));
		$this->assertCount(0,$action->controller->received);
	}

	private function duplicateResponse($description=null)
	{
		return array('status'=>409,'body'=>json_encode(array(
			'type'=>'https://api.swedbankpay.com/psp/errordetail/paymentorders/conflict',
			'problems'=>array(array('name'=>'ConflictingPaymentOrderId',
				'description'=>$description === null ? basename($this->id) : $description)))));
	}

	public function testDuplicateReferenceRecoversOnlyAfterVerifiedGet()
	{
		$client=$this->client();
		$client->responses=array($this->duplicateResponse(),$this->ok($this->order()),$this->payeeResponse());
		$result=$client->createOrder('VAUTest1',100,$this->options());
		$this->assertSame($this->id,$result['paymentOrder']['id']);
		$this->assertSame(array('POST','GET','GET'),array_column($client->calls,0));
	}

	public function testDuplicateReferenceCannotOverrideAmountCurrencyMerchantOrReference()
	{
		foreach(array('amount','currency','id','payeeId','payeeReference') as $field)
		{
			$order=$this->order();
			$payee=array('payeeInfo'=>array('payeeId'=>$this->payee,'payeeReference'=>'VAUTest1'));
			if($field === 'payeeId' || $field === 'payeeReference') $payee['payeeInfo'][$field]='different';
			else $order['paymentOrder'][$field]=$field === 'amount' ? 101 : 'different';
			$client=$this->client();
			$client->responses=array($this->duplicateResponse(),$this->ok($order),$this->ok($payee));
			try { $client->createOrder('VAUTest1',100,$this->options()); $this->fail('Mismatched conflict accepted.'); }
			catch(RuntimeException $e) { $this->assertContains('mismatch',$e->getMessage()); }
			$this->assertSame(1,count(array_keys(array_column($client->calls,0),'POST')));
		}
	}

	public function testUnrecognizedOrAmbiguousConflictDoesNotTriggerRecovery()
	{
		$responses=array($this->duplicateResponse('https://evil.test/order'),$this->duplicateResponse('../payeeinfo'),
			array('status'=>409,'body'=>'not-json'));
		foreach(array('type','missing','multiple') as $case)
		{
			$response=$this->duplicateResponse();
			$data=json_decode($response['body'],true);
			if($case === 'type') $data['type']='unrelated-conflict';
			elseif($case === 'missing') $data['problems']=array();
			else $data['problems'][]=$data['problems'][0];
			$response['body']=json_encode($data);
			$responses[]=$response;
		}
		foreach($responses as $response)
		{
			$client=$this->client();
			$client->responses=array($response);
			try { $client->createOrder('VAUTest1',100,$this->options()); $this->fail('Unrecognized conflict accepted.'); }
			catch(RuntimeException $e) { $this->assertContains('HTTP 409',$e->getMessage()); }
			$this->assertCount(1,$client->calls);
		}
	}

	public function testCreationRecoveryDoesNotApplyToCaptureConflict()
	{
		$client=$this->client();
		$client->responses=$this->captureResponses();
		$client->responses[7]=$this->duplicateResponse();
		try { $client->captureOrder($this->id,'VAUTest1',100,'VAUTest1C1'); $this->fail('Capture conflict accepted.'); }
		catch(RuntimeException $e) { $this->assertContains('HTTP 409',$e->getMessage()); }
		$this->assertCount(8,$client->calls);
	}

}

class XSwedbankPayTestAction extends XSwedbankPayWebhookAction
{
	public $component;
	protected function getPaymentComponent() { return $this->component; }
}
class XSwedbankPayTestController extends CController
{
	public $binding;
	public $received=array();
	public $handlerResult=true;
	public function bindingForOrder($id)
	{
		if($this->binding instanceof Exception) throw $this->binding;
		return $this->binding;
	}
	public function recordEvidence($evidence)
	{
		$this->received[]=$evidence;
		if($this->handlerResult instanceof Exception) throw $this->handlerResult;
		return $this->handlerResult;
	}
}
