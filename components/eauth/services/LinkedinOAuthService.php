<?php
/**
 * FacebookOAuthService class file.
 *
 * Register application: https://developers.facebook.com/apps/
 *
 * @author Maxim Zemskov <nodge@yandex.ru>
 * @link http://github.com/Nodge/yii-eauth/
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

require_once dirname(dirname(__FILE__)) . '/EOAuth2Service.php';

/**
 * LinkedIn provider class.
 *
 * @package application.extensions.eauth.services
 */
class LinkedinOAuthService extends EOAuth2Service
{
	protected $name='linkedin';
	protected $title='LinkedIn';
	protected $type='OAuth';
	protected $jsArguments=array('popup'=>array('width'=>900, 'height'=>550));

	protected $client_id;
	protected $client_secret;
	protected $scope;
	protected $providerOptions=array(
		'authorize'=>'https://www.linkedin.com/oauth/v2/authorization',
		'access_token'=>'https://www.linkedin.com/oauth/v2/accessToken',
	);

	protected function fetchAttributes()
	{
		$info=(object)$this->makeSignedRequest('https://api.linkedin.com/v2/me');
		
		$this->attributes['id']=$info->id;
	}

	protected function getCodeUrl($redirect_uri)
	{
		$url=parent::getCodeUrl($redirect_uri);

		if(isset($_GET['js']))
			$url.= '&display=popup';

		return $url;
	}

	protected function getTokenUrl($code)
	{
		return $this->providerOptions['access_token'];
	}

	protected function getAccessToken($code)
	{
		$params=array(
			'client_id'=>$this->client_id,
			'client_secret'=>$this->client_secret,
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'redirect_uri'=>$this->getState('redirect_uri'),
		);
		$response=$this->makeRequest($this->getTokenUrl($code), array('query'=>$params), false);
		$result=CJSON::decode($response);
		return $result;
	}

	/**
	 * Save access token to the session.
	 * @param array $token access token array.
	 */
	protected function saveAccessToken($token)
	{
		$this->setState('auth_token', $token['access_token']);
		$this->setState('expires', isset($token['expires']) ? time() + (int)$token['expires'] - 60 : 0);
		$this->access_token=$token['access_token'];
	}

	/**
	 * Returns fields required for signed request.
	 * Used in {@link makeSignedRequest}.
	 * @return array
	 */
	protected function getSignedRequestFields()
	{
		return array('oauth2_access_token'=>$this->access_token);
	}

	/**
	 * Returns the error info from json.
	 * @param stdClass $json the json response.
	 * @return array the error array with 2 keys: code and message. Should be null if no errors.
	 */
	protected function fetchJsonError($json)
	{
		if(isset($json->error))
		{
			return array(
				'code'=>$json->error->code,
				'message'=>$json->error->message,
			);
		}
		else
			return null;
	}
}
