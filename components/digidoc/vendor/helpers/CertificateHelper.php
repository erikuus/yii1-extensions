<?php
/**
 * Class CertificateHelper
 */
class CertificateHelper
{
    const SHA_1 = 'SHA-1';
    const SHA_224 = 'SHA-224';
    const SHA_256 = 'SHA-256';
    const SHA_384 = 'SHA-384';
    const SHA_512 = 'SHA-512';

    const SHA_1_BYTES = 20;
    const SHA_224_BYTES = 28;
    const SHA_256_BYTES = 32;
    const SHA_384_BYTES = 48;
    const SHA_512_BYTES = 64;

    const SHA_1_LENGTH = 40;
    const SHA_224_LENGTH = 56;
    const SHA_256_LENGTH = 64;
    const SHA_384_LENGTH = 96;
    const SHA_512_LENGTH = 128;

    public static $hashValidationRules = array(
        self::SHA_1   => array(
            'bytes'  => self::SHA_1_BYTES,
            'length' => self::SHA_1_LENGTH,
        ),
        self::SHA_224 => array(
            'bytes'  => self::SHA_224_BYTES,
            'length' => self::SHA_224_LENGTH,
        ),
        self::SHA_256 => array(
            'bytes'  => self::SHA_256_BYTES,
            'length' => self::SHA_256_LENGTH,
        ),
        self::SHA_384 => array(
            'bytes'  => self::SHA_384_BYTES,
            'length' => self::SHA_384_LENGTH,
        ),
        self::SHA_512 => array(
            'bytes'  => self::SHA_512_BYTES,
            'length' => self::SHA_512_LENGTH,
        ),
    );

    /**
     * Get certificate hash type based on the hash length
     *
     * @param string $hashValue
     *
     * @return int|null|string
     */
    public static function getHashType($hashValue)
    {
        $hashLength = strlen($hashValue);

        foreach (static::$hashValidationRules as $hashName => $hasRules) {
            if ($hasRules['length'] === $hashLength && ctype_xdigit($hashValue)) {
                return $hashName;
            }
        }

        return null;
    }
}
