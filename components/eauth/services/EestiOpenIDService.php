<?php
/**
 * EestiOpenIDService class file.
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */

require_once dirname(dirname(__FILE__)).'/EOpenIDService.php';

/**
 * Google provider class.
 * @package application.extensions.eauth.services
 */
class EestiOpenIDService extends EOpenIDService {

	protected $name = 'eesti_id_kaart';
	protected $title = 'ID kaart';
	protected $type = 'OpenID';
	protected $jsArguments = array('popup' => array('width' => 880, 'height' => 520));

	protected $url = 'https://openid.ee/server/xrds/eid'; // "eid" = ID-kaart, "mid" = Mobiil-ID

	protected $requiredAttributes = array(
		'name' => array('fullname', 'namePerson'),
	);
}