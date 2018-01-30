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
	 * @var integer the banklink typeID for savePayment
	 */
	public $paymentIdBankLink;
	/**
	 * @var integer the creaditcard typeID for savePayment
	 */
	public $paymentIdCreditCard;

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
	 * Gets Erply employee id if user exists
	 * @link https://erply.com/api/verifyUser/
	 * @return integer employeeID
	 */
	public function getEmployeeId()
	{
		$result=$this->sendRequest('verifyUser',array(
			'clientCode'=>$this->clientCode,
			'username'=>$this->username,
			'password'=>$this->password
		));

		if(isset($result['records'][0]['employeeID']))
			return $result['records'][0]['employeeID'];
		else
			return null;
	}

	/**
	 * Save new customer or update existing
	 * @link https://erply.com/api/saveCustomer/
	 * @param array $params customer data to be saved
	 * @return integer customer id or false
	 */
	public function saveCustomer($params)
	{
		// check if customer exists
		$result=$this->sendRequest('getCustomers',array(
			'searchRegistryCode'=>$params['code'],
		));

		if(isset($result['records'][0])) // update
		{
			$params['customerID']=$result['records'][0]['customerID'];
			$this->sendRequest('saveCustomer', $params);
			return $params['customerID'];
		}
		else // create
		{
			$result=$this->sendRequest('saveCustomer',$params);
			if(isset($result['records'][0]))
				return $result['records'][0]['customerID'];
			else
				return false;
		}
	}

	/**
	 * Gets Erply company
	 * @link https://erply.com/api/getCustomers/
	 * @param string $registryCode
	 * @return array company data
	 *
	 */
	public function getCustomer($registryCode)
	{
		$result=$this->sendRequest('getCustomers',array(
			'searchRegistryCode'=>$registryCode,
			'getAddresses'=>1
		));

		if(isset($result['records'][0]))
		{
			// put reg. address as default address
			$company=$result['records'][0];
			foreach ($company['addresses'] as $address)
			{
				if($address['typeID']=='3')
				{
					$company['address']=$address['address'];
					break;
				}
			}

			return $company;
		}
		else
			return array();
	}

	/**
	 * Gets Erply customers by ids
	 * @link https://erply.com/api/getCustomers/
	 * @param array $customerIDs
	 * @return array customer data
	 */
	public function getCustomers($customerIDs)
	{
		$result=$this->sendRequest('getCustomers',array(
			'customerIDs'=>$customerIDs
		));

		if(isset($result['records']))
			return $result['records'];
		else
			return array();
	}

	/**
	 * Update Erply customer email
	 * @link https://erply.com/api/saveCustomer/
	 * @param integer $customerID the ID of a customer.
	 * @param string $email
	 */
	public function updateCustomerEmail($customerID, $email)
	{
		$this->sendRequest('saveCustomer',array(
			'customerID'=>$customerID,
			'email'=>$email
		));
	}

	/**
	 * Gets Erply country code
	 * @link https://erply.com/api/getCountries/
	 * @param integer $id the country id
	 * @return string country code
	 *
	 */
	public function getCountryCode($id)
	{
		$code='EE';

		$result=$this->sendRequest('getCountries');

		if(isset($result['records']))
		{
			foreach ($result['records'] as $country)
			{
				if($country['countryID']==$id)
				{
					$code=$country['countryCode'];
					break;
				}
			}
		}
		return $code;
	}

	/**
	 * Gets Erply Customer Address Id
	 * If given address exists, return it's id.
	 * If given address does not exist, add it and return it's id
	 * @link https://erply.com/api/getAddresses/
	 * @link https://erply.com/api/saveAddress/
	 * @param array $params the address parts (ownerID, typeID [1-postal, 3-registred], street, city, postalCode, country)
	 * @return integer address id
	 */
	public function getAddressId($params)
	{
		$id=null;

		$result=$this->sendRequest('getAddresses',array(
			'ownerID'=>$params['ownerID'],
			'typeID'=>$params['typeID']
		));

		if(isset($result['records']))
		{
			foreach ($result['records'] as $address)
			{
				if($address['street']==$params['street'] && $address['city']==$params['city'] && $address['postalCode']==$params['postalCode'] && $address['country']==$params['country'])
				{
					if (!$address['state'] || $address['state']==$params['state']) // NB! State can be empty in ERPLY
					{
						$id=$address['addressID'];
						break;
					}
				}
			}
		}

		if($id===null)
		{
			$result=$this->sendRequest('saveAddress', $params);

			if(isset($result['records'][0]['addressID']))
				$id=$result['records'][0]['addressID'];
		}

		return $id;
	}

	/**
	 * Save sales document
	 * @link https://erply.com/api/saveSalesDocument/
	 * @param array $params document data to be saved
	 * @return array document response data
	 *
	 */
	public function saveSalesDocument($params)
	{
		$params['newInErp']=1;

		$result=$this->sendRequest('saveSalesDocument',$params);

		if(isset($result['records'][0]))
			return $result['records'][0];
		else
			return array();
	}

	/**
	 * Gets Erply sales document reference number
	 * @link https://erply.com/api/getSalesDocuments/
	 * @param integer $id the document id
	 * @return string
	 */
	public function getSalesDocumentReferenceNumber($id)
	{
		$result=$this->sendRequest('getSalesDocuments',array(
			'id'=>$id
		));

		if(isset($result['records'][0]['referenceNumber']))
			return $result['records'][0]['referenceNumber'];
		else
			return null;
	}

	/**
	 * Gets Erply sales document payment status
	 * @link https://erply.com/api/getSalesDocuments/
	 * @param integer $id the document id
	 * @return string
	 */
	public function getSalesDocumentPaymentStatus($id)
	{
		$result=$this->sendRequest('getSalesDocuments',array(
			'id'=>$id
		));

		if(isset($result['records'][0]['paymentStatus']))
			return $result['records'][0]['paymentStatus'];
		else
			return null;
	}

	/**
	 * Gets Erply sales documents
	 * @link https://erply.com/api/getSalesDocuments/
	 * @param array $documentIDs
	 * @return array document data
	 */
	public function getSalesDocuments($documentIDs)
	{
		$result=$this->sendRequest('getSalesDocuments',array(
			'ids'=>$documentIDs
		));

		if(isset($result['records']))
			return $result['records'];
		else
			return array();
	}

	/**
	 * Gets Erply product
	 * @link https://erply.com/api/getProducts/
	 * @param string $code the unique product code
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
	 * @param array $productIDs
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
	 * Save payment of card type for sales document
	 * @link https://erply.com/api/savePayment/
	 * @param integer $documentID the sales document id
	 * @param numeric $paymentSum the payment sum
	 * @param string $paymentType the payment type codename [BANKLINK|CREDITCARD]
	 * @param string $paymentInfo the sales document payment info
	 * @return integer payment id
	 */
	public function savePayment($documentID, $paymentSum, $paymentType=3, $paymentInfo=null)
	{
		if(!$this->paymentIdBankLink)
			throw new CException('"paymentIdBankLink" has to be set!');

		if(!$this->paymentIdCreditCard)
			throw new CException('"paymentIdCreditCard" has to be set!');

		$result=$this->sendRequest('saveSalesDocument',array(
			'id'=>$documentID,
			'paymentStatus'=>'PAID',
			'paymentInfo'=>$paymentInfo
		));

		if(!isset($result['records'][0]))
			return null;

		if($paymentType=='BANKLINK')
			$typeID=$this->paymentIdBankLink;
		elseif($paymentType=='CREDITCARD')
			$typeID=$this->paymentIdCreditCard;
		else
			$typeID=3;

		$result=$this->sendRequest('savePayment',array(
			'typeID'=>$typeID,
			'documentID'=>$documentID,
			'sum'=>$paymentSum
		));

		if(isset($result['records'][0]))
			return $result['records'][0]['paymentID'];
		else
			return null;
	}

	/**
	 * Send API request
	 * @param string $request the name of the request
	 * @param array $params parameters (name=>value) of the request
	 * @return array response
	 */
	public function sendRequest($request, $params=array())
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