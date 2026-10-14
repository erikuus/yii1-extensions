<?php
/**
 * GET-only sandbox reconciliation through the real Yii component, no application.
 * php verify-sandbox.php < private-stdin
 * One stdin JSON line: payeeId, accessToken, orderId, reference, amount (integer),
 * captureReference. Keep input in memory; do not save credentials in this repo.
 * This never creates/retries checkout, captures, refunds, or invokes fulfilment.
 * YII_FRAMEWORK optionally points to Yii 1.1 yii.php.
 */
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('display_errors','0');
ini_set('log_errors','0');
require_once getenv('YII_FRAMEWORK') ?: dirname(__FILE__).'/../../../../yii-1.1.8/yii.php';
require_once dirname(__FILE__).'/../XSwedbankPay.php';

class XSwedbankPayReadOnlySmoke extends XSwedbankPay
{
	public $readCount=0;
	protected function send($method, $url, $headers, $body)
	{
		if($method !== 'GET' || $this->testMode !== true ||
			strpos($url, self::ORIGIN.'/psp/paymentorders/') !== 0 || $body !== null)
			throw new RuntimeException('Smoke check permits sandbox GET only.');
		$this->readCount++;
		return parent::send($method,$url,$headers,$body);
	}
}
try
{
	$input=json_decode(fgets(STDIN),true);
	foreach(array('payeeId','accessToken','orderId','reference','amount','captureReference') as $field)
		if(!isset($input[$field])) throw new RuntimeException('Missing stdin field.');
	$client=Yii::createComponent(array('class'=>'XSwedbankPayReadOnlySmoke',
		'enabled'=>true,'testMode'=>true,'payeeId'=>$input['payeeId'],'accessToken'=>$input['accessToken']));
	$client->init();
	$evidence=$client->verifyCapture($input['orderId'],$input['reference'],$input['amount'],$input['captureReference']);
	unset($input);
	echo json_encode(array('verifiedAtUtc'=>gmdate('c'),'getRequests'=>$client->readCount,
		'evidence'=>$evidence)).PHP_EOL;
}
catch(Exception $e)
{
	fwrite(STDERR,$e->getMessage().PHP_EOL);
	exit(1);
}
