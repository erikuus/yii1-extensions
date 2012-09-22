<?php
/**
 * CustomTwitterOAuthService class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/services/TwitterOAuthService.php';


class CustomTwitterOAuthService extends TwitterOAuthService
{
	protected function fetchAttributes()
	{
		$info = $this->makeSignedRequest('https://api.twitter.com/1/account/verify_credentials.json');

		$this->attributes['id']=$info->id;

		if (isset($info->name))
			$this->attributes['name']=$info->name;

		if (isset($info->id_str))
			$this->attributes['url']='http://twitter.com/account/redirect_by_id?id='.$info->id_str;

		$this->attributes['info'] = $info;
	}
}