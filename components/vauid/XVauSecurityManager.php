<?php
/**
 * XVauSecurityManager provides functions to encrypt and decrypt data based on VauID 2.0 protocol.
 *
 * For usage refer to {@link XVauLoginAction}
 *
 * @link http://www.ra.ee/apps/vauid/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XVauSecurityManager extends CApplicationComponent
{
	private $_key;

	/**
	 * @param string $value the key used to decrypt VAU response
	 * @throws CException if the key is empty
	 */
	public function setValidationKey($value)
	{
		if(!empty($value))
			$this->_key=$value;
		else
			throw new CException('XVauSecurityManager configuration must have "validationKey" value!');
	}

	/**
	 * Encrypts data that VAU posts to remote site after successful login.
	 * @param string the data to be encrypted
	 * @return string encrypted data
	 */
	public function encrypt($data)
	{
		return bin2hex($this->linencrypt($data));
	}

	/**
	 * Decryptes data posted by VAU after successful login.
	 * @param string $postedData the encrypted data
	 * @return string decrypted data
	 */
	public function decrypt($postedData)
	{
		return $this->lindecrypt(@$this->hex2bin($postedData));
	}

	/**
	 * @param string $data the data to be crypted
	 * @return string crypted data
	 */
	public function linencrypt($data)
	{
		$iv_size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256,MCRYPT_MODE_ECB);
		$iv=mcrypt_create_iv($iv_size,MCRYPT_RAND);
		$encrypted=mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$this->_key,$data,MCRYPT_MODE_ECB,$iv);
		return $encrypted;
	}

	/**
	 * @param string $encrypted the encrypted data
	 * @return string decrypted data
	 */
	protected function lindecrypt($encrypted)
	{
		$iv_size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256,MCRYPT_MODE_ECB);
		$iv=mcrypt_create_iv($iv_size,MCRYPT_RAND);
		$decrypted=mcrypt_decrypt(MCRYPT_RIJNDAEL_256,$this->_key,$encrypted,MCRYPT_MODE_ECB,$iv);
		return rtrim($decrypted);
	}

	/**
	 * @param string $h the hexadecimal representation of data
	 * @return the binary representation of the given data
	 */
	protected function hex2bin($h)
	{
		if(!is_string($h))
			return null;
		$r='';
		for($a=0;$a<strlen($h);$a+=2)
			$r.=chr(hexdec($h{$a}.$h{($a+1)}));
		return $r;
	}
}