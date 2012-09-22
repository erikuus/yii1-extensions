<?php
/**
 * EestiMobiilOpenIDService class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/services/EestiOpenIDService.php';

class EestiMobiilOpenIDService extends EestiOpenIDService
{
	protected $name = 'eesti_mobiil_id';
	protected $title = 'Mobiil ID';
	protected $url = 'https://openid.ee/server/xrds/mid';

	protected $requiredAttributes = array(
		'name' => array('fullname', 'namePerson'),
		'email' => array('email', 'contact/email'),
		'birthday' => array('dob', 'birthDate'),
	);
}