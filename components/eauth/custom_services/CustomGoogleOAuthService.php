<?php
/**
 * CustomGoogleOAuthService class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/services/GoogleOAuthService.php';

class CustomGoogleOAuthService extends GoogleOAuthService
{
	protected $scope = 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';

	protected function fetchAttributes()
	{
		$info = (array)$this->makeSignedRequest('https://www.googleapis.com/oauth2/v1/userinfo');

		$this->attributes['id'] = $info['id'];

		if (isset($info['name']))
			$this->attributes['name'] = $info['name'];

		if (isset($info['link']))
			$this->attributes['url'] = $info['link'];

		if (isset($info['given_name']))
			$this->attributes['firstname'] = $info['given_name'];

		if (isset($info['family_name']))
			$this->attributes['lastname'] = $info['family_name'];

		if (isset($info['email']))
			$this->attributes['email'] = $info['email'];

		if (isset($info['birthday']))
			$this->attributes['birthday'] = $info['birthday'];

		$this->attributes['info'] = $info;
	}
}