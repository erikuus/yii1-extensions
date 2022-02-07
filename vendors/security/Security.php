<?php

require_once dirname(dirname(__FILE__)).'/random/random.php';

class Security
{
	public $cipher = 'AES-128-CBC';

	public $allowedCiphers = [
		'AES-128-CBC' => [16, 16],
		'AES-192-CBC' => [16, 24],
		'AES-256-CBC' => [16, 32],
	];

	public $kdfHash = 'sha256';

	public $macHash = 'sha256';

	public $authKeyInfo = 'AuthorizationKey';

	public $derivationIterations = 100000;

	public $passwordHashStrategy;

	public $passwordHashCost = 13;

	private $_useLibreSSL;

	/**
	 * @return bool if LibreSSL should be used
	 * Use version is 2.1.5 or higher.
	 * @since 2.0.36
	 */
	protected function shouldUseLibreSSL()
	{
		if($this->_useLibreSSL === null)
		{
			// Parse OPENSSL_VERSION_TEXT because OPENSSL_VERSION_NUMBER is no use for LibreSSL.
			// https://bugs.php.net/bug.php?id=71143
			$this->_useLibreSSL = defined('OPENSSL_VERSION_TEXT')
				&& preg_match('{^LibreSSL (\d\d?)\.(\d\d?)\.(\d\d?)$}', OPENSSL_VERSION_TEXT, $matches)
				&& (10000 * $matches[1]) + (100 * $matches[2]) + $matches[3] >= 20105;
		}

		return $this->_useLibreSSL;
	}

	public function encrypt($data, $secret, $info=null)
	{
		if(!extension_loaded('openssl'))
			throw new InvalidConfigException('Encryption requires the OpenSSL PHP extension');

		if(!isset($this->allowedCiphers[$this->cipher][0], $this->allowedCiphers[$this->cipher][1]))
			throw new InvalidConfigException($this->cipher . ' is not an allowed cipher');


		list($blockSize, $keySize) = $this->allowedCiphers[$this->cipher];

		$keySalt = $this->generateRandomKey($keySize);
		$key = $this->hkdf($this->kdfHash, $secret, $keySalt, $info, $keySize);

		$iv = $this->generateRandomKey($blockSize);

		$encrypted = openssl_encrypt($data, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);
		if($encrypted === false)
			throw new Exception('OpenSSL failure on encryption: ' . openssl_error_string());

		$authKey = $this->hkdf($this->kdfHash, $key, null, $this->authKeyInfo, $keySize);
		$hashed = $this->hashData($iv . $encrypted, $authKey);

		return $keySalt . $hashed;
	}

	public function decrypt($data, $secret, $info=null)
	{
		if(!extension_loaded('openssl'))
			throw new Exception('Encryption requires the OpenSSL PHP extension');

		if(!isset($this->allowedCiphers[$this->cipher][0], $this->allowedCiphers[$this->cipher][1]))
			throw new Exception($this->cipher . ' is not an allowed cipher');

		list($blockSize, $keySize) = $this->allowedCiphers[$this->cipher];

		$keySalt = $this->byteSubstr($data, 0, $keySize);
		$key = $this->hkdf($this->kdfHash, $secret, $keySalt, $info, $keySize);

		$authKey = $this->hkdf($this->kdfHash, $key, null, $this->authKeyInfo, $keySize);
		$data = $this->validateData($this->byteSubstr($data, $keySize, null), $authKey);
		if($data === false)
			return false;


		$iv = $this->byteSubstr($data, 0, $blockSize);
		$encrypted = $this->byteSubstr($data, $blockSize, null);

		$decrypted = openssl_decrypt($encrypted, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);
		if($decrypted === false)
			throw new Exception('OpenSSL failure on decryption: ' . openssl_error_string());

		return $decrypted;
	}

	public function hkdf($algo, $inputKey, $salt = null, $info = null, $length = 0)
	{
		if(function_exists('hash_hkdf'))
		{
			$outputKey = hash_hkdf((string)$algo, (string)$inputKey, $length, (string)$info, (string)$salt);
			if($outputKey === false)
				throw new Exception('Invalid parameters to hash_hkdf()');

			return $outputKey;
		}

		$test = @hash_hmac($algo, '', '', true);
		if(!$test)
			throw new Exception('Failed to generate HMAC with hash algorithm: ' . $algo);

		$hashLength = $this->byteLength($test);
		if(is_string($length) && preg_match('{^\d{1,16}$}', $length))
			$length = (int) $length;

		if(!is_int($length) || $length < 0 || $length > 255 * $hashLength)
			throw new Exception('Invalid length');

		$blocks = $length !== 0 ? ceil($length / $hashLength) : 1;

		if($salt === null)
			$salt = str_repeat("\0", $hashLength);

		$prKey = hash_hmac($algo, $inputKey, $salt, true);

		$hmac = '';
		$outputKey = '';
		for($i = 1; $i <= $blocks; $i++)
		{
			$hmac = hash_hmac($algo, $hmac . $info . chr($i), $prKey, true);
			$outputKey .= $hmac;
		}

		if($length !== 0)
			$outputKey = $this->byteSubstr($outputKey, 0, $length);

		return $outputKey;
	}

	public function generateRandomKey($length = 32)
	{
		if(!is_int($length))
			throw new InvalidArgumentException('First parameter ($length) must be an integer');

		if($length < 1)
			throw new InvalidArgumentException('First parameter ($length) must be greater than 0');

		return random_bytes($length);
	}

	public function hashData($data, $key, $rawHash = false)
	{
		$hash = hash_hmac($this->macHash, $data, $key, $rawHash);
		if(!$hash)
			throw new Exception('Failed to generate HMAC with hash algorithm: ' . $this->macHash);

		return $hash . $data;
	}

	public function validateData($data, $key, $rawHash = false)
	{
		$test = @hash_hmac($this->macHash, '', '', $rawHash);
		if(!$test)
			throw new Exception('Failed to generate HMAC with hash algorithm: ' . $this->macHash);

		$hashLength = $this->byteLength($test);
		if($this->byteLength($data) >= $hashLength)
		{
			$hash = $this->byteSubstr($data, 0, $hashLength);
			$pureData = $this->byteSubstr($data, $hashLength, null);

			$calculatedHash = hash_hmac($this->macHash, $pureData, $key, $rawHash);

			if($this->compareString($hash, $calculatedHash))
				return $pureData;
		}

		return false;
	}

	public function compareString($expected, $actual)
	{
		if(!is_string($expected))
			throw new Exception('Expected expected value to be a string, ' . gettype($expected) . ' given.');


		if(!is_string($actual))
			throw new Exception('Expected actual value to be a string, ' . gettype($actual) . ' given.');

		if(function_exists('hash_equals'))
			return hash_equals($expected, $actual);

		$expected .= "\0";
		$actual .= "\0";
		$expectedLength = $this->byteLength($expected);
		$actualLength = $this->byteLength($actual);
		$diff = $expectedLength - $actualLength;

		for ($i = 0; $i < $actualLength; $i++)
			$diff |= (ord($actual[$i]) ^ ord($expected[$i % $expectedLength]));

		return $diff === 0;
	}

	public function byteSubstr($string, $start, $length = null)
	{
		if($length === null)
			$length = $this->byteLength($string);

		return mb_substr($string, $start, $length, '8bit');
	}

	public function byteLength($string)
	{
		return mb_strlen((string)$string, '8bit');
	}
}