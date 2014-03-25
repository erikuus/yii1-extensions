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
	protected function fetchAttributes()
	{
		$info = (object)$this->makeSignedRequest('https://graph.facebook.com/me');

		$this->attributes['id']= $info->id;

		if (isset($info->name))
			$this->attributes['name'] = $info->name;

		if (isset($info->url))
			$this->attributes['url'] = $info->link;

		if (isset($info->email))
			$this->attributes['email'] = $info->email;

		$this->attributes['info'] = $info;
	}
}