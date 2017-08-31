<?php
/**
 * XErply class file.
 *
 * XErply component enables to use Erply InventoryAPI
 *
 * InventoryAPI by Erply is a powerful backend for web shops, and more.
 *
 * The following shows how to use XErply component.
 *
 * Configure component:
 * <pre>
 * 'components'=>array(
 *     'erply'=> array(
 *         'class'=>'ext.components.erply.XErply',
 *         'url'=>'https://t1.erply.com/api/',
 *         'clientCode'=>2076
 *     )
 * )
 * </pre>
 *
 * Use component:
 * <pre>
 * $erply=Yii::app()->erply;
 * $erply->username='erikuus';
 * $erply->password='#######';
 * $erplyUserID=$erply->getUserId();
 * </pre>
 *
 * @link https://erply.com/api/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(__FILE__).'/vendor/EAPI.class.php';

class XErply extends CApplicationComponent
{
	/**
	 * @var integer Erply client code
	 */
	public $clientCode;
	/**
	 * @var string Erply username
	 */
	public $username;
	/**
	 * @var string Erply password
	 */
	public $password;
	/**
	 * @var string the url where API calls are sent to
	 * If empty, url is formed as follows: https://[clientCode].erply.com/api/
	 */
	public $url;

	/**
	 * Initializes the component.
	 * This method checks if required values are set
	 * and constructs soap client object
	 */
	public function init()
	{
		if(!$this->clientCode)
			throw new CException('"clientCode" has to be set!');

		if(!$this->url)
			$api->url="https://".$this->clientCode.".erply.com/api/";
	}

	/**
	 * Gets Erply user id if user exists
	 * @link https://erply.com/api/verifyUser/
	 * @return integer userID
	 */
	public function getUserId()
	{
		$result=$this->sendRequest('verifyUser',array(
			'clientCode'=>$this->clientCode,
			'username'=>$this->username,
			'password'=>$this->password
		));

		if(isset($result['records'][0]['userID']))
			return $result['records'][0]['userID'];
		else
			return null;
	}

	/**
	 * Gets Erply product
	 * @link https://erply.com/api/getProducts/
	 * @param string unique product code
	 * @return array product data
	 *
	 */
	public function getProduct($code)
	{
		$result=$this->sendRequest('getProducts',array(
			'code'=>$code
		));

		if(isset($result['records'][0]))
			return $result['records'][0];
		else
			return array();
	}

	/**
	 * Gets Erply product prices
	 * @link https://erply.com/api/getProductPrices/
	 * @param array product id's
	 * @return array product prices
	 */
	public function getProductPrices($productIDs)
	{
		$result=$this->sendRequest('getProductPrices',array(
			'productIDs'=>$productIDs
		));

		if(isset($result['records']))
			return $result['records'];
		else
			return array();
	}

	/**
	 * Send API request
	 * @param string $request the name of the request
	 * @param array $params parameters (name=>value) of the request
	 * @return array response
	 */
	public function sendRequest($request,$params)
	{
		if(!$this->username)
			throw new CException('"username" has to be set!');

		if(!$this->password)
			throw new CException('"password" has to be set!');

		$api=new EAPI();

		$api->clientCode=$this->clientCode;
		$api->username=$this->username;
		$api->password=$this->password;
		$api->url=$this->url;

		$result=$api->sendRequest($request,$params);

		return json_decode($result,true);
	}
}