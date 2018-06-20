<?php
/**
 * XCommon class.
 *
 * Handles payment request for all bank.
 *
 * NOTE! Since October 2014 all members of bank union will accept this new protcol.
 *
 * See base class XIPizza for usage example
 *
 * @link http://pangaliit.ee/images/files/Pangalingi_tehniline_spetsifikatsioon_2014_FINAL.pdf
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/ipizza/XIPizza.php';

class XCommon extends XIPizza
{
	/**
	 * @return string mac param name
	 */
	protected function getSuccessServiceId()
	{
		return 1111;
	}

	/**
	 * @return string service id param name
	 */
	protected function getFailureServiceId()
	{
		return 1911;
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
			'VK_CANCEL'=>$this->cancelUrl,
			'VK_ENCODING'=>$this->charEncoding,
			'VK_LANG'=>$this->getLanguageCode(),
			'VK_DATETIME'=>$this->datetime,
		);
	}

    /**
     * @return array param length
     */
    protected function getParamLengths()
    {
        return array(
            'VK_SERVICE' => 4,
            'VK_VERSION' => 3,
            'VK_SND_ID' => 15,
            'VK_STAMP' => 20,
            'VK_AMOUNT' => 12,
            'VK_CURR' => 3,
            'VK_ACC' => 34,
            'VK_NAME' => 70,
            'VK_REF' => 35,
            'VK_MSG' => 95,
            'VK_RETURN' => 255,
            'VK_CANCEL' => 255,
        	'VK_DATETIME' => 24,
            'VK_MAC' => 700,
            'VK_ENCODING' => 12,
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
            '1011' => array(
                'VK_SERVICE',
                'VK_VERSION',
                'VK_SND_ID',
                'VK_STAMP',
                'VK_AMOUNT',
                'VK_CURR',
                'VK_ACC',
                'VK_NAME',
                'VK_REF',
                'VK_MSG',
            	'VK_RETURN',
            	'VK_CANCEL',
            	'VK_DATETIME'
            ),
            // request to make a transaction
            '1012' => array(
                'VK_SERVICE',
                'VK_VERSION',
                'VK_SND_ID',
                'VK_STAMP',
                'VK_AMOUNT',
                'VK_CURR',
                'VK_REF',
                'VK_MSG',
            	'VK_RETURN',
            	'VK_CANCEL',
            	'VK_DATETIME'
            ),
            // 'transaction completed' response message
            '1111' => array(
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
                'VK_T_DATETIME'
            ),
            // 'transaction not completed' response message
            '1911' => array(
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
}