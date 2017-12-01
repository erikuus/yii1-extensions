<?php
/**
 * CustomFacebookOAuthService class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/services/FacebookOAuthService.php';

class CustomFacebookOAuthService extends FacebookOAuthService
{
	protected $scope = 'email user_birthday';

	protected $providerOptions = array(
		'authorize' => 'https://www.facebook.com/v2.8/dialog/oauth',
		'access_token' => 'https://graph.facebook.com/oauth/access_token',
	);

	protected $attributeNames = array(
		'name',
		'email',
		'birthday',
	);

	protected function fetchAttributes()
	{
		$info = (object)$this->makeSignedRequest('https://graph.facebook.com/me', array(
			'query' => array(
				'fields'=>implode(',', $this->attributeNames)
			)
		));

		$this->attributes['id']= $info->id;
		$this->attributes['name'] = isset($info->name) ? $info->name : null;
		$this->attributes['email'] = isset($info->email) ? $info->email : null;
		$this->attributes['birthday'] = isset($info->birthday) ? $info->birthday : null;
	}
}