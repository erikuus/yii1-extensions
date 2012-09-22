<?php
/**
 * CustomTwitterOAuthService class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/services/GitHubOAuthService.php';

class CustomGitHubOAuthService extends GitHubOAuthService {

	protected function fetchAttributes() {
		$info = (object) $this->makeSignedRequest('https://api.github.com/user');

		$this->attributes['id'] = $info->id;

		if (isset($info->login))
			$this->attributes['name'] = $info->login;

		if (isset($info->html_url))
			$this->attributes['url'] = $info->html_url;

		if (isset($info->name)) // overwrite name if public profile is available
			$this->attributes['name'] = $info->name;

		if (isset($info->email))
			$this->attributes['email'] = $info->email;

		$this->attributes['info'] = $info;
	}
}