<?php


namespace PayPlug\backward;


use Tools;
use ValidateCore as Validate;

class PayPlugBackward
{
    const PASSWORD_LENGTH = 5;

    /**
     * Check if plaintext password is valid
     * Size is limited by `password_hash()` (72 chars).
     *
     * @param string $plaintextPasswd Password to validate
     * @param int $size
     *
     * @return bool Indicates whether the given string is a valid plaintext password
     *
     * @since 1.7.0
     */
    public static function isPlaintextPassword($plaintextPasswd, $size = self::PASSWORD_LENGTH)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            // The password lenght is limited by `password_hash()`
            return Tools::strlen($plaintextPasswd) >= $size && Tools::strlen($plaintextPasswd) <= 72;
        } else {
            return Validate::isPlaintextPassword($plaintextPasswd);
        }
    }
}