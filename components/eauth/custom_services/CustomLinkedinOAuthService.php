<?php
/**
 * CustomizedGoogleOAuthService class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/services/LinkedinOAuthService.php';

class CustomLinkedinOAuthService extends LinkedinOAuthService
{
    protected $scope = 'r_basicprofile r_emailaddress';

	protected function fetchAttributes()
	{
		$info = $this->makeSignedRequest('https://api.linkedin.com/v1/people/~:(id,first-name,last-name,public-profile-url,email-address)', array(), false); // json format not working :(
		$info = $this->parseInfo($info);

		$this->attributes['id'] = $info['id'];

		if (isset($info['first-name']) && isset($info['last-name']))
			$this->attributes['name'] = $info['first-name'].' '.$info['last-name'];

		if (isset($info['public-profile-url']))
			$this->attributes['url'] = $info['public-profile-url'];

		if (isset($info['first-name']))
			$this->attributes['firstname'] = $info['first-name'];

		if (isset($info['last-name']))
			$this->attributes['lastname'] = $info['last-name'];

		if (isset($info['email-address']))
			$this->attributes['email'] = $info['email-address'];

		$this->attributes['info'] = $info;
	}
}