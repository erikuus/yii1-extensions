<?php
/**
 * XSwedbank class.
 *
 * Handles payment request for Swedbank.
 *
 * NOTE! Since October 2014 all members of bank union (incl. Swedbank) will accept new protcol (see XCommon.php)
 *
 * See base class XIPizza for usage example
 *
 * @link https://www.swedbank.ee/static/pdf/business/d2d/paymentcollection/info_banklink_techspec_est.pdf
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/ipizza/XIPizza.php';

class XSwedbank extends XIPizza
{
	/**
	 * @return string mac param name
	 */
	protected function getSuccessServiceId()
	{
		return 1101;
	}

	/**
	 * @return string service id param name
	 */
	protected function getFailureServiceId()
	{
		return 1901;
	}

	/**
	 * @return string mac param name
	 */
	protected function getMacParamName()
	{
		return 'VK_MAC';
	}

	/**
	 * @return string service id param name
	 */
	protected function getServiceParamName()
	{
		return 'VK_SERVICE';
	}

	/**
	 * @return array params of payment request (except MAC)
	 */
	protected function getParams()
	{
		return array(
			'VK_SERVICE'=>$this->serviceId,
			'VK_VERSION'=>$this->serviceVersion,
			'VK_SND_ID'=>$this->merchantId,
			'VK_STAMP'=>$this->requestId,
			'VK_AMOUNT'=>$this->amount,
			'VK_CURR'=>$this->currency,
			'VK_ACC'=>$this->merchantAccount,
			'VK_NAME'=>$this->merchantName,
			'VK_REF'=>$this->reference,
			'VK_MSG'=>$this->message,
			'VK_RETURN'=>$this->returnUrl,
			'VK_ENCODING'=>$this->charEncoding,
			'VK_LANG'=>$this->getLanguageCode(),
		);
	}

    /**
     * @return array param max length
     */
    protected function getParamLengths()
    {
        return array(
            'VK_SERVICE' => 4,
            'VK_VERSION' => 3,
            'VK_SND_ID' => 10,
            'VK_STAMP' => 20,
            'VK_AMOUNT' => 17,
            'VK_CURR' => 3,
            'VK_ACC' => 34,
            'VK_NAME' => 30,
            'VK_REF' => 20,
            'VK_MSG' => 70,
            'VK_MAC' => 700,
            'VK_RETURN' => 60,
            'VK_LANG' => 3,
        );
    }

	/**
	 * @return array mac params names mapped by service id
	 */
    protected function getMacParamMap()
    {
        return array(
            // request to make a transaction
            '1001' => array(
                'VK_SERVICE',
                'VK_VERSION',
                'VK_SND_ID',
                'VK_STAMP',
                'VK_AMOUNT',
                'VK_CURR',
                'VK_ACC',
                'VK_NAME',
                'VK_REF',
                'VK_MSG'
            ),
            // request to make a transaction
            '1002' => array(
                'VK_SERVICE',
                'VK_VERSION',
                'VK_SND_ID',
                'VK_STAMP',
                'VK_AMOUNT',
                'VK_CURR',
                'VK_REF',
                'VK_MSG'
            ),
            // 'transaction completed' response message
            '1101' => array(
                'VK_SERVICE',
                'VK_VERSION',
                'VK_SND_ID',
                'VK_REC_ID',
                'VK_STAMP',
                'VK_T_NO',
                'VK_AMOUNT',
                'VK_CURR',
                'VK_REC_ACC',
                'VK_REC_NAME',
                'VK_SND_ACC',
                'VK_SND_NAME',
                'VK_REF',
                'VK_MSG',
                'VK_T_DATE'
            ),
            // 'transaction not completed' response message
            '1901' => array(
                'VK_SERVICE',
                'VK_VERSION',
                'VK_SND_ID',
                'VK_REC_ID',
                'VK_STAMP',
                'VK_REF',
                'VK_MSG'
            ),
        );
    }

	/**
	 * Check whether it is automated request
	 * @return boolean whether it is automated request
	 */
	public function isAutoRequest()
	{
		return isset($_REQUEST['VK_AUTO']) && $_REQUEST['VK_AUTO']=='Y';
	}
}