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
	 * Gets Erply company
	 * @link https://erply.com/api/getCustomers/
	 * @param string $registryCode
	 * @return array company data
	 *
	 */
	public function getCompany($registryCode)
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
	 * Gets Erply customers
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
	 * Gets Erply Customer Address Id
	 * If given address exists, return it's id.
	 * If given address does not exist, add it and return it's id
	 * @link https://erply.com/api/getAddresses/
	 * @link https://erply.com/api/saveAddress/
	 * @param integer $ownerID the customer id of address owner
	 * @param string $street
	 * @param string $city
	 * @param string $postalCode
	 * @param string $country
	 * @return integer address id
	 */
	public function getAddressId($ownerID, $street, $postalCode, $city, $state, $country)
	{
		$id=null;

		$result=$this->sendRequest('getAddresses',array(
			'ownerID'=>$ownerID,
			'typeID'=>1
		));

		if(isset($result['records']))
		{
			foreach ($result['records'] as $address)
			{
				if($address['street']==$street && $address['city']==$city && $address['postalCode']==$postalCode && $address['country']==$country)
				{
					if (!$address['state'] || $address['state']==$state) // NB! State can be empty in ERPLY
					{
						$id=$address['addressID'];
						break;
					}
				}
			}
		}

		if($id===null)
		{
			$result=$this->sendRequest('saveAddress',array(
				'ownerID'=>$ownerID,
				'typeID'=>1,
				'street'=>$street,
				'postalCode'=>$postalCode,
				'city'=>$city,
				'state'=>$state,
				'country'=>$country
			));

			if(isset($result['records'][0]['addressID']))
				$id=$result['records'][0]['addressID'];
		}

		return $id;
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
	 * Save sales document
	 * @link https://erply.com/api/saveSalesDocument/
	 * @param array $params document data to be saved
	 * @return array document response data
	 *
	 */
	public function saveSalesDocument($params)
	{
		$result=$this->sendRequest('saveSalesDocument',$params);

		if(isset($result['records'][0]))
			return $result['records'][0];
		else
			return array();
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