<?php
require_once dirname(__FILE__).'/XSwedbankPayTest.php';

/** Contract fixtures, not a claim that any bank has been exercised live. */
class XSwedbankPayBankTest extends PHPUnit_Framework_TestCase
{
	private $_id='/psp/paymentorders/00000000-0000-0000-0000-000000000002';

	private function _client($change=null)
	{
		$id=$this->_id;
		$payee='00000000-0000-0000-0000-000000000001';
		$order=array('id'=>$id,'amount'=>100,'currency'=>'EUR','status'=>'Paid',
			'payeeInfo'=>array('id'=>$id.'/payeeinfo'),'remainingReversalAmount'=>100);
		foreach(array('financialTransactions','failedAttempts','paid') as $resource)
			$order[$resource]=array('id'=>$id.'/'.strtolower($resource));
		$paid=array('id'=>$id.'/paid','number'=>123,'instrument'=>'BankLink',
			'transactionType'=>'Sale','amount'=>100,'payeeReference'=>'VAU1',
			'details'=>array('account'=>'private bank account'),'paymentToken'=>'private token');
		$sale=array('type'=>'Sale','number'=>123,'amount'=>100,'payeeReference'=>'VAU1');
		$transactions=array('id'=>$id.'/financialtransactions','financialTransactionsList'=>array($sale));
		if($change)
			call_user_func_array($change,array(&$order,&$paid,&$transactions));
		$client=new XSwedbankPayFakeClient($payee,'offline-test-token',true);
		foreach(array(array('paymentOrder'=>$order),array('payeeInfo'=>array('payeeId'=>$payee,'payeeReference'=>'VAU1')),
			array('financialTransactions'=>$transactions),array('failedAttempts'=>array('id'=>$id.'/failedattempts','failedAttemptList'=>array())),
			array('paid'=>$paid)) as $response)
			$client->responses[]=array('status'=>200,'body'=>json_encode($response));
		return $client;
	}

	public function testBankSaleUsesGetEvidenceAndNeverClaimsCardCapture()
	{
		$client=$this->_client();
		$evidence=$client->paymentEvidence($this->_id,'VAU1',100,'EUR','VAU1C1');
		$this->assertTrue($evidence['saleVerified']);
		$this->assertFalse($evidence['captureVerified']);
		$this->assertNotContains('private',json_encode($evidence));
		$this->assertCount(5,$client->calls);
		foreach($client->calls as $call)
			$this->assertSame('GET',$call[0]);
	}

	public function testSaleMayBeOmittedFromFinancialListWithPositivePaidAndBalanceEvidence()
	{
		foreach(array(true,false) as $omit)
		{
			$client=$this->_client(function(&$order,&$paid,&$transactions) use ($omit) {
				if($omit)
					unset($transactions['financialTransactionsList']);
				else
					$transactions['financialTransactionsList']=array();
			});
			$this->assertTrue($client->paymentEvidence($this->_id,'VAU1',100)['saleVerified']);
		}
	}

	public function testProviderSizedTransactionNumberWorksOn32BitPhp()
	{
		$client=$this->_client(function(&$order,&$paid,&$transactions) {
			$paid['number']=79100113652;
			$transactions['financialTransactionsList'][0]['number']=79100113652;
		});
		$this->assertTrue($client->paymentEvidence($this->_id,'VAU1',100)['saleVerified']);
	}

	public function invalidSaleCases()
	{
		return array_map(function($case) { return array($case); },array(
			'authorization','missing_number','capture_balance','cancel_balance','no_reversible_balance',
			'partial_reversible_balance','duplicate','reversal','partial_sale','wrong_reference','wrong_number',
			'wrong_type','wrong_currency','wrong_resource','paid_amount','paid_reference','unknown_instrument',
			'fractional_number','unsafe_number','string_number','boolean_number'));
	}

	/** @dataProvider invalidSaleCases */
	public function testIncompleteContradictoryAndUnboundSalesCannotSettle($case)
	{
		$client=$this->_client(function(&$order,&$paid,&$transactions) use ($case) {
			switch($case)
			{
				case 'fractional_number': $paid['number']=123.5; break;
				case 'unsafe_number': $paid['number']=9007199254740992; break;
				case 'string_number': $paid['number']='123'; break;
				case 'boolean_number': $paid['number']=true; break;
				case 'authorization': $paid['transactionType']='Authorization'; break;
				case 'missing_number': unset($paid['number']); break;
				case 'capture_balance': $order['remainingCaptureAmount']=100; break;
				case 'cancel_balance': $order['remainingCancellationAmount']=100; break;
				case 'no_reversible_balance': unset($order['remainingReversalAmount']); break;
				case 'partial_reversible_balance': $order['remainingReversalAmount']=50; break;
				case 'duplicate': $transactions['financialTransactionsList'][]=$transactions['financialTransactionsList'][0]; break;
				case 'reversal': $transactions['financialTransactionsList'][]=array('type'=>'Reversal','amount'=>100,'payeeReference'=>'OTHER'); break;
				case 'partial_sale': $transactions['financialTransactionsList'][0]['amount']=50; break;
				case 'wrong_reference': $transactions['financialTransactionsList'][0]['payeeReference']='OTHER'; break;
				case 'wrong_number': $transactions['financialTransactionsList'][0]['number']=456; break;
				case 'wrong_type': $transactions['financialTransactionsList'][0]['type']='Capture'; break;
				case 'wrong_currency': $transactions['financialTransactionsList'][0]['currency']='USD'; break;
				case 'wrong_resource': $transactions['id'].='other'; break;
				case 'paid_amount': $paid['amount']=50; break;
				case 'paid_reference': $paid['payeeReference']='OTHER'; break;
				case 'unknown_instrument': $paid['instrument']='Unknown'; break;
			}
		});
		try
		{
			$evidence=$client->paymentEvidence($this->_id,'VAU1',100,'EUR','VAU1C1');
			$this->assertFalse($evidence['saleVerified']);
			$this->assertFalse($evidence['captureVerified']);
		}
		catch(RuntimeException $e)
		{
			$this->assertNotEmpty($e->getMessage());
		}
		foreach($client->calls as $call)
			$this->assertSame('GET',$call[0]);
	}
}
