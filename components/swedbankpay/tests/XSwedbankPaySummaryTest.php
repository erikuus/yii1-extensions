<?php
require_once dirname(__FILE__).'/XSwedbankPayTest.php';

/** Offline response fixtures based on the two BankLink sandbox outcomes. */
class XSwedbankPaySummaryTest extends PHPUnit_Framework_TestCase
{
	private function _client(array $attempts, $orderStatus='Initialized')
	{
		$detail=new XSwedbankPaySummaryModel;
		$id=$detail->order_id;
		$client=new XSwedbankPayFakeClient('00000000-0000-0000-0000-000000000001','offline-test-token',true);
		$client->paymentModel='XSwedbankPaySummaryModel';
		$client->payment=new XSwedbankPaySummaryPayment;
		$client->responses=array(
			array('status'=>200,'body'=>json_encode(array('paymentOrder'=>array(
				'id'=>$id,'amount'=>3000,'currency'=>'EUR','status'=>$orderStatus,
				'payeeInfo'=>array('id'=>$id.'/payeeinfo'),
				'financialTransactions'=>array('id'=>$id.'/financialtransactions'),
				'failedAttempts'=>array('id'=>$id.'/failedattempts'))))),
			array('status'=>200,'body'=>json_encode(array('payeeInfo'=>array(
				'payeeId'=>$client->payeeId,'payeeReference'=>'VAU1')))),
			array('status'=>200,'body'=>json_encode(array('financialTransactions'=>array(
				'id'=>$id.'/financialtransactions','financialTransactionsList'=>array())))),
			array('status'=>200,'body'=>json_encode(array('failedAttempts'=>array(
				'id'=>$id.'/failedattempts','failedAttemptList'=>$attempts)))),
		);
		return $client;
	}

	private function _attempt($status='Failed', $type='paymentattempt-declined')
	{
		return array('instrument'=>'BankLink','status'=>$status,'number'=>601000002133,
			'problem'=>array('type'=>$type,'status'=>-1,'title'=>'private title','detail'=>'private bank details'),
			'operations'=>array(array('href'=>'https://private.example/token')));
	}

	public function bankOutcomes()
	{
		return array(
			array('Initialized','Aborted','paymentattempt-aborted'),
			array('Initialized','Failed','paymentattempt-declined'),
			array('Aborted','Aborted','paymentattempt-aborted'),
			array('Aborted','Failed','paymentattempt-declined'),
			array('Aborted','Aborted','paymentattempt-aborted',true),
		);
	}

	/** @dataProvider bankOutcomes */
	public function testBrowserAndWebhookLogAttemptReasonWithoutChangingConfirmation($orderStatus, $status, $type, $idOnly=false)
	{
		foreach(array(false,true) as $webhook)
		{
			$client=$this->_client(array($this->_attempt($status,$type)),$orderStatus);
			if($idOnly)
				$client->responses[2]=array('status'=>200,'body'=>json_encode(array(
					'financialTransactions'=>array('id'=>(new XSwedbankPaySummaryModel)->order_id.'/financialtransactions'))));
			if($webhook)
			{
				$controller=new XSwedbankPayTestController('offline');
				$detail=new XSwedbankPaySummaryModel;
				$controller->binding=$detail->binding;
				$controller->binding['payment']=$client->payment;
				$action=new XSwedbankPayTestAction($controller,'callback');
				$action->component=$client;
				$action->bindingCallback='bindingForOrder';
				$action->successCallback='recordEvidence';
				$action->failureCallback='recordFailure';
				$action->log=true;
				$action->logCategory='swedbankAttemptSummaryTest';
				$action->logLevel='warning';
				$this->assertSame(200,$action->processCallback('POST',json_encode(array('paymentOrder'=>array('id'=>$detail->order_id)))));
				$this->assertCount(0,$controller->received);
				$this->assertCount($orderStatus === 'Aborted' ? 1 : 0,$controller->failures);
				$logs=Yii::getLogger()->getLogs('warning','swedbankAttemptSummaryTest');
				$verification=$logs[count($logs)-2][0];
				$this->assertContains('source=webhook',$verification);
				$this->assertContains('"problemType":"'.$type.'"',$verification);
				$this->assertNotContains('private',$verification);
			}
			else
				$this->assertSame($orderStatus === 'Initialized' ? null : false,$client->validatePayment());
			$this->assertSame($orderStatus === 'Initialized' ? 'pending' : 'failed',$client->validationResult);
			$this->assertSame($orderStatus,$client->validationSummary['status']);
			$this->assertFalse($client->validationSummary['captureVerified']);
			$this->assertFalse($client->validationSummary['saleVerified']);
			$this->assertSame(array(),$client->validationSummary['failureCodes']);
			$this->assertSame(array(array('instrument'=>'BankLink','status'=>$status,'problemType'=>$type,
				'number'=>601000002133)),$client->validationSummary['failedAttempts']);
			$this->assertFalse($client->validationSummary['failedAttemptsTruncated']);
			$this->assertSame(-1,XSwedbankPaySummaryModel::$savedEvidence['failedAttempts'][0]['problem']['status']);
			$this->assertSame($status,XSwedbankPaySummaryModel::$savedEvidence['failedAttempts'][0]['status']);
			$this->assertSame(1,$client->payment->refreshes);
			$this->assertCount(4,$client->calls);
			foreach($client->calls as $call)
				$this->assertSame('GET',$call[0]);
		}
	}

	public function testCardProblemUrisAreNormalizedAndExistingCodesRemain()
	{
		foreach(array('carddeclined','https://api.payex.com/psp/errordetail/creditcard/carddeclined',
			'https://api.swedbankpay.com/psp/errordetail/carddeclined') as $type)
		{
			$attempt=$this->_attempt('Failed',$type);
			$attempt['instrument']='CreditCard';
			$attempt['problem']['problems']=array(array('name'=>'INELIGIBLE_CARD_RANGE','description'=>'private card'));
			$client=$this->_client(array($attempt));
			$this->assertNull($client->validatePayment());
			$this->assertSame('carddeclined',$client->validationSummary['failedAttempts'][0]['problemType']);
			$this->assertSame(array('INELIGIBLE_CARD_RANGE'),$client->validationSummary['failureCodes']);
			$this->assertNotContains('private',json_encode($client->validationSummary));
		}
		foreach(array('3dsecureusercanceled','3dsecureusercancelled') as $type)
		{
			$attempt=$this->_attempt('Aborted','https://api.payex.com/psp/errordetail/creditcard/'.$type);
			$attempt['instrument']='CreditCard';
			$client=$this->_client(array($attempt));
			$this->assertNull($client->validatePayment());
			$this->assertSame($type,$client->validationSummary['failedAttempts'][0]['problemType']);
		}
	}

	public function testMissingMalformedAndContradictoryFieldsNeverLeakOrInventReason()
	{
		$attempts=array(array(),array('instrument'=>array('private'),'status'=>array('private'),
			'problem'=>array('type'=>array('private'))));
		foreach(array('private','https://evil.example/carddeclined',
			'https://api.payex.com/psp/errordetail/creditcard/carddeclined?token=private',
			"paymentattempt-declined\nprivate",str_repeat('private',200)) as $type)
			$attempts[]=$this->_attempt('Failed',$type);
		$attempts[]=$this->_attempt('Aborted','paymentattempt-declined');
		$attempt=$this->_attempt();
		$attempt['instrument']='CreditCard';
		$attempts[]=$attempt;
		$attempt=$this->_attempt();
		unset($attempt['status']);
		$attempts[]=$attempt;
		foreach($attempts as $attempt)
		{
			$client=$this->_client(array($attempt));
			$this->assertNull($client->validatePayment());
			$this->assertSame('Unknown',$client->validationSummary['failedAttempts'][0]['problemType']);
			$this->assertNotContains('private',json_encode($client->validationSummary));
		}
	}

	public function testAttemptNumbersRejectUnsafeTypesAndPreserveProviderSizedIntegers()
	{
		foreach(array('601000002133',true,1.5,0,-1,9007199254740992) as $number)
		{
			$attempt=$this->_attempt();
			$attempt['number']=$number;
			$client=$this->_client(array($attempt));
			$this->assertNull($client->validatePayment());
			$this->assertArrayNotHasKey('number',$client->validationSummary['failedAttempts'][0]);
		}
	}

	public function testMultipleAttemptsAreSeparateAndBoundedAndSummaryResets()
	{
		$attempts=array($this->_attempt('Aborted','paymentattempt-aborted'),$this->_attempt());
		$attempts[0]['number']=601000002130;
		while(count($attempts) < 12)
			$attempts[]=$this->_attempt();
		$client=$this->_client($attempts);
		$this->assertNull($client->validatePayment());
		$this->assertSame(12,$client->validationSummary['failedAttemptCount']);
		$this->assertCount(10,$client->validationSummary['failedAttempts']);
		$this->assertTrue($client->validationSummary['failedAttemptsTruncated']);
		$this->assertSame('Aborted',$client->validationSummary['failedAttempts'][0]['status']);
		$this->assertSame('Failed',$client->validationSummary['failedAttempts'][1]['status']);
		$this->assertCount(12,XSwedbankPaySummaryModel::$savedEvidence['failedAttempts']);
		$empty=$this->_client(array());
		$client->responses=$empty->responses;
		$this->assertNull($client->validatePayment());
		$this->assertSame(array(),$client->validationSummary['failedAttempts']);
		$this->assertFalse($client->validationSummary['failedAttemptsTruncated']);
		$this->assertSame(0,$client->validationSummary['failedAttemptCount']);
	}
}

// phpcs:ignore Squiz.Classes.ClassFileName.NoMatch -- In-memory confirmation model; no application or database.
class XSwedbankPaySummaryModel
{
	public static $savedEvidence;
	public $order_id='/psp/paymentorders/00000000-0000-0000-0000-000000000002';
	public $reference='VAU1';
	public $amount=3000;
	public $currency='EUR';
	public $capture_reference='VAU1C1';
	public $binding;

	public function __construct()
	{
		$this->binding=array('orderId'=>$this->order_id,'reference'=>$this->reference,'amount'=>$this->amount,
			'currency'=>$this->currency,'captureReference'=>$this->capture_reference);
	}

	public static function forConfirmation($payment, $client)
	{
		return new self;
	}

	public function saveConfirmation(array $evidence)
	{
		self::$savedEvidence=$evidence;
		return $evidence['status'] === 'Initialized' ? 'pending' : 'failed';
	}
}

// phpcs:ignore Squiz.Classes.ClassFileName.NoMatch -- Synthetic parent payment with no persistence.
class XSwedbankPaySummaryPayment
{
	public $refreshes=0;

	public function refresh()
	{
		$this->refreshes++;
	}
}
