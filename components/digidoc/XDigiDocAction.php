<?php

/**
 * XDigiDocAction class file
 *
 * XDigiDocAction handles requests needed to digitally sign files using digidoc service
 *
 * XDigiDocAction component is meant to be used together with {@link XDigiDoc} and {@link XDigiDocWidget}.
 * Together these classes provide a solution to digitally sign files with Estonian ID Card and Mobile ID.
 *
 * See {@link XDigiDoc} for complete example how to configure all components to work together as one solution.
 *
 * The following shows how to set up the XDigiDocAction inside Controller actions() method:
 *
 * <pre>
 * return array(
 *     'signing'=>array(
 *         'class'=>'ext.components.digidoc.XDigiDocAction',
 *         'componentName='digidoc',
 *         'tokenValidator'=>'validateToken',
 *         'successUrl'=>$this->createUrl('/digidoc/success'),
 *         'failureUrl'=>$this->createUrl('/digidoc/failure'),
 *         'mobileServiceName'=>'Testimine',
 *         'mobileServiceInfo'=>'Sign test document'
 *     ),
 * );
 * </pre>
 *
 * IMPORTANT
 * - Signing with ID card requires SSL.
 * - Service name in {@link XDigiDocAction} must be 'Testimine' if using test service https://tsp.demo.sk.ee/
 *
 * @link https://www.id.ee/index.php?id=30279
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

class XDigiDocAction extends CAction
{
	/**
	 * @var string $componentName the DigiDoc component name
	 * Defaults to 'digidoc'.
	 */
	public $componentName='digidoc';
	/**
	 * @var string $tokenValidator the owner controller method that validates request token set in {@link XDigiDocWidget}
	 * @see XDigiDocWidget
	 */
	public $tokenValidator;
	/**
	 * @var string $successUrl the location this action posts request token after signing success
	 */
	public $successUrl;
	/**
	 * @var string $failureUrl the location this action posts request token after signing failure
	 */
	public $failureUrl;
	/**
	 * @var string $mobileServiceName the name of service for mobile
	 * This will be displayed to users mobile phones screen during signing process.
	 * Must be 'Testimine' if using test service https://tsp.demo.sk.ee/
	 */
	public $mobileServiceName;
	/**
	 * @var string $mobileServiceInfo the explanatory message for mobile
	 * This will be displayed to users mobile phones screen during signing process.
	 */
	public $mobileServiceInfo;
	/**
	 * @var string $flashKey the key for flash messages saved in action
	 * Defaults to 'ext.components.digidoc.XDigiDocAction'.
	 */
	public $flashKey='ext.components.digidoc.XDigiDocAction';
	/**
	 * @var boolean $log whether to log signing progress
	 * Defaults to false.
	 */
	public $log=false;
	/**
	 * @var string $logLevel the level for log message
	 * Must be one of the following: [trace|info|profile|warning|error]
	 * Defaults to 'trace'.
	 */
	public $logLevel='trace';
	/**
	 * @var string $logCategory the category for log message
	 * Defaults to 'ext.components.digidoc.XDigiDocAction'.
	 * For example to trace signing progress into separate file
	 * use configuration as follows:
	 * 'components'=>array(
	 *     'log'=>array(
	 *         'class'=>'CLogRouter',
	 *         'routes'=>array(
	 *             array(
	 *                 'class'=>'CFileLogRoute',
	 *                 'levels'=>'trace',
	 *                 'logFile'=>'digidoc_trace.log',
	 *                 'categories'=>'ext.components.digidoc.XDigiDocAction',
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public $logCategory='ext.components.digidoc.XDigiDocAction';

	/**
	 * Runs the action.
	 */
	public function run()
	{
		// validate token
		if (!(isset($_POST['_token']) && $this->validateToken($_POST['_token'])))
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');

		// import vendor classes
		if(!Yii::getPathOfAlias('xdigidoc'))
			Yii::setPathOfAlias('xdigidoc', __DIR__);

		// note that hashcode-lib must be imported
		// in config/main.php before session is started
		Yii::import('xdigidoc.vendor.service.*');
		Yii::import('xdigidoc.vendor.helpers.*');
		Yii::import('xdigidoc.vendor.exceptions.*');

		// get DigiDoc component
		$component=Yii::app()->getComponent($this->componentName);

		// get digidoc service instance
		$dds=DigiDocService::instance($component->url);

		// handle signing requests
		switch($_POST['request_act'])
		{
			case 'ID_SIGN_CREATE_HASH':
				$this->handleIdCardSigning($dds);
				break;
			case 'ID_SIGN_COMPLETE':
				$this->completeIdCardSigning($dds);
				break;
			case 'MID_SIGN':
				$this->handleMobileSigning($dds);
				break;
			case 'MID_SIGN_COMPLETE':
				$this->completeMobileSigning($dds);
				break;
		}

		// this should be called in the end of every request where session with DDS is already started.
		DocHelper::persistHashcodeSession();
	}

	/**
	 * Handle id card signing proccess with json response
	 * @param DigiDocService $dds
	 * @return void
	 */
	protected function handleIdCardSigning($dds)
	{
		header('Content-Type: application/json');
		$response=array();
		try
		{
			// log progress
			$this->log('User started the preparation of signature with ID Card to the container.');

			// check required parameter
			if(!array_key_exists('signersCertificateHEX',$_POST))
				throw new InvalidArgumentException('There were missing parameters which are needed to sign with ID Card.');

			// let's prepare the parameters for PrepareSignature method
			$prepareSignatureReqParams['Sesscode']=SessionHelper::getDdsSessionCode();
			$prepareSignatureReqParams['SignersCertificate']=$_POST['signersCertificateHEX'];
			$prepareSignatureReqParams['SignersTokenId']='';

			array_merge($prepareSignatureReqParams,$this->getIdCardSignerParameters($_POST));
			$prepareSignatureReqParams['SigningProfile']='';

			// invoke PrepareSignature.
			$prepareSignatureResponse=$dds->PrepareSignature($prepareSignatureReqParams);

			// if we reach here then everything must be OK with the signature preparation
			$response['signature_info_digest']=$prepareSignatureResponse['SignedInfoDigest'];
			$response['signature_id']=$prepareSignatureResponse['SignatureId'];
			$response['signature_hash_type']=CertificateHelper::getHashType($response['signature_info_digest']);
			$response['is_success']=true;
		}
		catch(Exception $e)
		{
			$code=$e->getCode();
			$message=(!!$code ? $code.': ' : '').$e->getMessage();
			$this->log($message);
			$response['error_message']=$message;
		}

		echo json_encode($response);
	}

	/**
	 * Complete id card signing proccess
	 * @param DigiDocService $dds
	 * @return void
	 */
	protected function completeIdCardSigning($dds)
	{
		// check if there was any kind of error during ID Card signing.
		if(array_key_exists('error_message',$_POST))
		{
			// handle failure
			$this->handleIdCardSigningFailure($dds);

			// save error as flash message so it can be used in application
			Yii::app()->user->setFlash($this->flashKey, $_POST['error_message']);

			// redirect
			$this->postWithToken($this->failureUrl);
		}
		else
		{
			// handle success
			$this->handleIdCardSigningSuccess($dds);

			// redirect
			$this->postWithToken($this->successUrl);
		}
	}

	/**
	 * Collect information about signer
	 * @param array $requestParameters - POST request form parameters about signer
	 * @return array
	 */
	protected function getIdCardSignerParameters($requestParameters)
	{
		$keyPrefix='signer';
		$signerParameters=array();
		$keys=array('Role','City','State','PostalCode');

		foreach($keys as $key)
		{
			$fullKey=$keyPrefix.$key;
			if(array_key_exists($fullKey,$requestParameters)===true)
				$signerParameters[$key]=$requestParameters[$fullKey];
		}

		return $signerParameters;
	}

	/**
	 * @param DigiDocService $dds
	 * @throws Exception
	 */
	protected function handleIdCardSigningFailure($dds)
	{
		if(!empty($_POST['signature_id']))
		{
			// the fact that there has been an error and there is a signature ID means that there is
			// a prepared but not finalized signature in the session that needs to be removed.
			$dds->RemoveSignature(array(
				'Sesscode'=>SessionHelper::getDdsSessionCode(),
				'SignatureId'=>$_POST['signature_id']
			));

			// log progress
			$this->log('Adding a signature to the container was not completed successfully so the prepared signature was removed from the container.');
			$this->log('Error message: '.$_POST['error_message']);
		}
	}

	/**
	 * @param DigiDocService $dds
	 * @throws Exception
	 */
	protected function handleIdCardSigningSuccess($dds)
	{
		if(!array_key_exists('signature_value',$_POST) || !array_key_exists('signature_id',$_POST))
			throw new InvalidArgumentException('There were missing parameters which are needed to sign with ID Card.');

		// everything is OK. Let's finalize the signing process in DigiDocService.
		$dds->FinalizeSignature(array(
			'Sesscode'=>SessionHelper::getDdsSessionCode(),
			'SignatureId'=>$_POST['signature_id'],
			'SignatureValue'=>$_POST['signature_value']
		));

		// rewrite the local container with new content
		$datafiles=DocHelper::getDatafilesFromContainer();

		$getSignedDocResponse=$dds->GetSignedDoc(array(
			'Sesscode'=>SessionHelper::getDdsSessionCode()
		));

		$containerData=$getSignedDocResponse['SignedDocData'];
		if(strpos($containerData,'SignedDoc')===false)
			$containerData=base64_decode($containerData);

		DocHelper::createContainerWithFiles($containerData,$datafiles);

		// log progress
		$this->log('User successfully added a signature with ID Card to the container.');
	}

	/**
	 * Handle mobile signing proccess with json response
	 * @param DigiDocService $dds
	 * @return void
	 */
	protected function handleMobileSigning($dds)
	{
		header('Content-Type: application/json');
		$response=array();
		try
		{
			// check required parameter
			if(!array_key_exists('subAct',$_POST))
				throw new HttpInvalidParamException('There are missing parameters which are needed to sign with MID.');

			$subAction=$_POST['subAct'];
			if($subAction==='START_SIGNING')
			{
				// check required parameters
				if(!array_key_exists('phoneNo',$_POST) || !array_key_exists('idCode',$_POST))
					throw new HttpInvalidParamException('There were missing parameters which are needed to sign with MID.');

				$response=$this->prepareMobileSigning($dds,$response);
			}

			if($subAction==='GET_SIGNING_STATUS')
			{
				$statusResponse=$dds->GetStatusInfo(array(
					'Sesscode'=>SessionHelper::getDdsSessionCode(),
					'ReturnDocInfo'=>false,
					'WaitSignature'=>false
				));

				$statusCode=$statusResponse['StatusCode'];

				// log progress
				$this->log("User is asking about the status of mobile signing. The status is $statusCode");

				$success=$statusCode==='SIGNATURE';
				if($success)
					$response=$this->getMobileSigningSuccessResponse($dds,$response);
				elseif($statusCode!=='REQUEST_OK' && $statusCode!=='OUTSTANDING_TRANSACTION')
					$this->handleMobileSigningFailure($dds,$statusCode);
			}
		}
		catch(Exception $e)
		{
			$code=$e->getCode();
			$message=((bool)$code ? $code.': ' : '').$e->getMessage();
			$this->log($message);
			$response['error_message']=$message;
		}

		echo json_encode($response);
	}

	/**
	 * Complete mobile signing proccess
	 * @param DigiDocService $dds
	 * @return void
	 */
	protected function completeMobileSigning($dds)
	{
		// check if there was any kind of error during MID signing.
		if(array_key_exists('error_message',$_POST))
		{
			// save error as flash message so it can be used in application
			Yii::app()->user->setFlash($this->flashKey, $_POST['error_message']);

			// redirect
			$this->postWithToken($this->failureUrl);
		}
		else
			$this->postWithToken($this->successUrl);
	}

	/**
	 * @param DigiDocService $dds
	 * @param array $response
	 * @return mixed
	 * @throws Exception
	 */
	protected function prepareMobileSigning($dds,$response)
	{
		$phoneNumber=trim($_POST['phoneNo']);
		$identityCode=trim($_POST['idCode']);

		// log progress
		$this->log("User started the process of signing with MID. Mobile phone is $phoneNumber and ID code is $identityCode.");

		$mobileSignResponse=$dds->MobileSign(array(
			'Sesscode'=>SessionHelper::getDdsSessionCode(),
			'SignerIDCode'=>$identityCode,
			'SignerPhoneNo'=>$phoneNumber,
			'ServiceName'=>$this->mobileServiceName,
			'AdditionalDataToBeDisplayed'=>$this->mobileServiceInfo,
			'Language'=>'EST',
			'MessagingMode'=>'asynchClientServer',
			'ReturnDocInfo'=>false,
			'ReturnDocData'=>false
		));

		$response['challenge']=$mobileSignResponse['ChallengeID'];

		return $response;
	}

	/**
	 * @param DigiDocService $dds
	 * @param string $statusCode
	 * @throws MobileIDException
	 */
	protected function handleMobileSigningFailure($dds,$statusCode)
	{
		$messages=$dds->getMidStatusResponseErrorMessages;

		if(array_key_exists($statusCode,$messages))
			throw new MobileIDException($messages[$statusCode]);

		throw new MobileIDException("There was an error signing with Mobile ID. Status code is '$statusCode'.");
	}

	/**
	 * @param DigiDocService $dds
	 * @param array $response
	 * @return mixed
	 * @throws Exception
	 */
	protected function getMobileSigningSuccessResponse($dds,$response)
	{
		$datafiles=DocHelper::getDatafilesFromContainer();

		$signedResponse=$dds->GetSignedDoc(array(
			'Sesscode'=>SessionHelper::getDdsSessionCode()
		));

		$containerData=$signedResponse['SignedDocData'];
		if(strpos($containerData,'SignedDoc')===false)
			$containerData=base64_decode($containerData);

		// rewrite the local container with new content
		DocHelper::createContainerWithFiles($containerData,$datafiles);

		// log progress
		$this->log('User successfully added a signature with Mobile ID to the container.');

		$response['is_success']=true;

		return $response;
	}

	/**
	 * Validate request token
	 * @param string $token the request token
	 * @return boolean whether token is valid
	 */
	protected function validateToken($token)
	{
		if($this->tokenValidator)
			return $this->controller->{$this->tokenValidator}($token);
		else
			return true;
	}

	/**
	 * Post to given url sending request token as parameter
	 * @param string $url
	 * @return void
	 */
	protected function postWithToken($url)
	{
		$file=dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'postWithTokenForm.php';

		return $this->controller->renderFile($file, array(
			'url'=>$url,
			'token'=>$_POST['_token']
		));
	}

	/**
	 * Log message
	 * @param string $message
	 */
	protected function log($message)
	{
		if($this->log===true)
			Yii::log($message, $this->logLevel, $this->logCategory);
	}
}