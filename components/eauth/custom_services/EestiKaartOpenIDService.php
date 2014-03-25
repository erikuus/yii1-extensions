<?php
/**
 * EestiKaartOpenIDService class.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/services/EestiOpenIDService.php';

class EestiKaartOpenIDService extends EestiOpenIDService
{
	protected $requiredAttributes = array(
		'name' => array('fullname', 'namePerson'),
		//'email' => array('email', 'contact/email'),
		'birthday' => array('dob', 'birthDate'),
	);
}