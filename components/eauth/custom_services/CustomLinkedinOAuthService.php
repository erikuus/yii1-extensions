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
	protected $scope='r_liteprofile r_emailaddress';

	protected function fetchAttributes()
	{
		$info=(object)$this->makeSignedRequest('https://api.linkedin.com/v2/me');

		$this->attributes['id']=$info->id;

		if(isset($info->localizedFirstName) && isset($info->localizedLastName))
			$this->attributes['name']=$info->localizedFirstName.' '.$info->localizedLastName;

		if(isset($info->localizedFirstName))
			$this->attributes['firstname']=$info->localizedFirstName;

		if(isset($info->localizedLastName))
			$this->attributes['lastname']=$info->localizedLastName;

		$info=(object)$this->makeSignedRequest('https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))');

		if(isset($info->elements[0]->{'handle~'}->emailAddress))
			$this->attributes['email']=$info->elements[0]->{'handle~'}->emailAddress;
	}
}