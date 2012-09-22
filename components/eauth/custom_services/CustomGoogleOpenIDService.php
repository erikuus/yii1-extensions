<?php
/**
 * CustomGoogleOpenIDService class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/services/GoogleOpenIDService.php';

class CustomGoogleOpenIDService extends GoogleOpenIDService
{
	protected $requiredAttributes = array(
		'name' => array('firstname', 'namePerson/first'),
		'firstname' => array('firstname', 'namePerson/first'),
		'lastname' => array('lastname', 'namePerson/last'),
		'email' => array('email', 'contact/email'),
	);
}