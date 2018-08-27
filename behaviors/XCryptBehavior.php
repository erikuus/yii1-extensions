<?php
/**
 * XCryptBehavior
 *
 * XCryptBehavior provides methods to encrypt and decrypt data based on mcrypt library
 *
 * It can be attached to a controller on its behaviors() method:
 * <pre>
 * public function behaviors()
 * {
 *     return CMap::mergeArray(
 *         parent::behaviors(),
 *         array(
 *             'crypter'=>array(
 *                 'class'=>'ext.behaviors.XCryptBehavior',
 *                 'key'=>'123456789',
 *             )
 *         )
 *     );
 * }
 * </pre>
 *
 * It can be used in controller as follows:
 * <pre>
 * $this->encrypt('pass');
 * </pre>
 *
 * NOTE! The Mcrypt library has been declared DEPRECATED since PHP 7.1
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XCryptBehavior extends CBehavior
{
	/**
	 * @var string the encryption key
	 */
	public $key;

	/**
	 * Decryptes data posted back by VAU after successful login.
	 * @param string $str the data to be encrypted
	 * @param boolean whether to encode crypted binary string hexadecimally
	 * @return string encrypted data
	 */
	protected function encrypt($str, $bin2hex=false)
	{
		$iv_size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv=mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$crypted=mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $str, MCRYPT_MODE_ECB, $iv);
		return $bin2hex ? bin2hex($crypted) : $crypted;
	}

	/**
	 * Decryptes data posted back by VAU after successful login.
	 * @param string $encrypted the encrypted data
	 * @return string decrypted data
	 */
	protected function decrypt($encrypted, $hex2bin=false)
	{
		if($hex2bin)
			$encrypted=$this->hex2bin($encrypted);

		$iv_size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv=mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypted=mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, $encrypted, MCRYPT_MODE_ECB, $iv);
		return rtrim($decrypted);
	}

	/**
	 * Decodes a hexadecimally encoded binary string.
	 * Note that generic hex2bin function is available since PHP 5.4.0
	 * @param hexadecimal representation of data.
	 * @return the binary representation of the given data.
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