<?php

namespace Phalcon\Security;

class Random
{

    /**
     * Generates a random binary string
     *
     * @param int $len
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     * @throws \Exception
     */
    public function bytes($len = 16)
    {
        if ($len <= 0) {
            $len = 16;
        }

        if (function_exists("random_bytes")) {
            return random_bytes(len);
        }

        if (function_exists("\\Sodium\\randombytes_buf")) {
            return \Sodium\randombytes_buf($len);
        }

        if (function_exists("openssl_random_pseudo_bytes")) {
            return openssl_random_pseudo_bytes(len);
        }

        if (file_exists("/dev/urandom")) {
            $handle = fopen("/dev/urandom", "rb");

            if ($handle !== false) {
                stream_set_read_buffer($handle, 0);
                $ret = fread($handle, $len);
                fclose($handle);

                if (strlen($ret) != $len) {
                    throw new Exception("Unexpected partial read from random device");
                }

                return $ret;
            }
        }

        throw new Exception("No random device available");
    }

    /**
     * Generates a random hex string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The length of the result string is usually greater of $len.
     *
     * <code>
     * $random = new \Phalcon\Security\Random();
     *
     * echo $random->hex(10); // a29f470508d5ccb8e289
     * </code>
     *
     * @param int $len
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function hex($len = null)
    {
        return array_shift(unpack("H*", $this->bytes(len)));
    }

    /**
     * Generates a random base58 string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The result may contain alphanumeric characters except 0, O, I and l.
     *
     * It is similar to `Phalcon\Security\Random:base64` but has been modified to avoid both non-alphanumeric
     * characters and letters which might look ambiguous when printed.
     *
     * <code>
     * $random = new \Phalcon\Security\Random();
     *
     * echo $random->base58(); // 4kUgL2pdQMSCQtjE
     * </code>
     *
     * @see    \Phalcon\Security\Random:base64
     * @link   https://en.wikipedia.org/wiki/Base58
     * 
     * @param int $len
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function base58($len = null)
    {
        return $this->base("123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz", 58, $len);
    }

    /**
     * Generates a random base62 string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     *
     * It is similar to `Phalcon\Security\Random:base58` but has been modified to provide the largest value that can
     * safely be used in URLs without needing to take extra characters into consideration because it is [A-Za-z0-9].
     *
     * <code>
     * $random = new \Phalcon\Security\Random();
     *
     * echo $random->base62(); // z0RkwHfh8ErDM1xw
     * </code>
     *
     * @see    \Phalcon\Security\Random:base58
     * 
     * @param int $len
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function base62($len = null)
    {
        return $this->base("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz", 62, $len);
    }

    /**
     * Generates a random base64 string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The length of the result string is usually greater of $len.
     * Size formula: 4 * ($len / 3) and this need to be rounded up to a multiple of 4.
     *
     * <code>
     * $random = new \Phalcon\Security\Random();
     *
     * echo $random->base64(12); // 3rcq39QzGK9fUqh8
     * </code>
     *
     * @param int $len
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function base64($len = null)
    {
        return base64_encode($this->bytes($len));
    }

    /**
     * Generates a random URL-safe base64 string
     *
     * If $len is not specified, 16 is assumed. It may be larger in future.
     * The length of the result string is usually greater of $len.
     *
     * By default, padding is not generated because "=" may be used as a URL delimiter.
     * The result may contain A-Z, a-z, 0-9, "-" and "_". "=" is also used if $padding is true.
     * See RFC 3548 for the definition of URL-safe base64.
     *
     * <code>
     * $random = new \Phalcon\Security\Random();
     *
     * echo $random->base64Safe(); // GD8JojhzSTrqX7Q8J6uug
     * </code>
     *
     * @link https://www.ietf.org/rfc/rfc3548.txt
     * 
     * @param int $len
     * @param boolean $padding
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function base64Safe($len = null, $padding = false)
    {
        $s = strtr(base64_encode($this->base64(len)), "+/", "-_");
        $s = preg_replace("#[^a-z0-9_=-]+#i", "", $s);

        if (!$padding) {
            return rtrim($s, "=");
        }

        return $s;
    }

    /**
     * Generates a v4 random UUID (Universally Unique IDentifier)
     *
     * The version 4 UUID is purely random (except the version). It doesn't contain meaningful
     * information such as MAC address, time, etc. See RFC 4122 for details of UUID.
     *
     * This algorithm sets the version number (4 bits) as well as two reserved bits.
     * All other bits (the remaining 122 bits) are set using a random or pseudorandom data source.
     * Version 4 UUIDs have the form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx where x is any hexadecimal
     * digit and y is one of 8, 9, A, or B (e.g., f47ac10b-58cc-4372-a567-0e02b2c3d479).
     *
     * <code>
     * $random = new \Phalcon\Security\Random();
     *
     * echo $random->uuid(); // 1378c906-64bb-4f81-a8d6-4ae1bfcdec22
     * </code>
     *
     * @link https://www.ietf.org/rfc/rfc4122.txt
     * 
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    public function uuid()
    {
        $ary    = array_values(unpack("N1a/n1b/n1c/n1d/n1e/N1f", $this->bytes(16)));
        $ary[2] = ($ary[2] & 0x0fff) | 0x4000;
        $ary[3] = ($ary[3] & 0x3fff) | 0x8000;

        array_unshift($ary, "%08x-%04x-%04x-%04x-%04x%08x");

        return call_user_func_array("sprintf", $ary);
    }

    /**
     * Generates a random number between 0 and $len
     *
     * Returns an integer: 0 <= result <= $len.
     *
     * <code>
     * $random = new \Phalcon\Security\Random();
     *
     * echo $random->number(16); // 8
     * </code>
     * 
     * @param int $len
     * @return int
     * @throws Exception If secure random number generator is not available, unexpected partial read or $len <= 0
     */
    public function number($len)
    {
        $bin = "";

        if ($len <= 0) {
            throw new Exception("Require a positive integer > 0");
        }

        if (function_exists("random_int")) {
            return random_int(0, len);
        }

        if (function_exists("\\Sodium\\randombytes_uniform")) {
            // \Sodium\randombytes_uniform will return a random integer between 0 and len - 1
            return \Sodium\randombytes_uniform($len) + 1;
        }

        $hex = dechex($len);

        if ((strlen($hex) & 1) == 1) {
            $hex = "0" . $hex;
        }

        $bin .= pack("H*", $hex);

        $mask = ord(bin[0]);
        $mask = $mask | ($mask >> 1);
        $mask = $mask | ($mask >> 2);
        $mask = $mask | ($mask >> 4);

        do {
            $rnd = $this->bytes(strlen($bin));
            $rnd = substr_replace($rnd, chr(ord(substr($rnd, 0, 1)) & $mask), 0, 1);
        } while ($bin < $rnd);

        $ret = unpack("H*", $rnd);

        return hexdec(array_shift($ret));
    }

    /**
     * Generates a random string based on the number ($base) of characters ($alphabet).
     *
     * If $n is not specified, 16 is assumed. It may be larger in future.
     *
     * @param string $alphabet
     * @param int $base
     * @param mixed $n
     * @return string
     * @throws Exception If secure random number generator is not available or unexpected partial read
     */
    protected function base($alphabet, $base, $n = null)
    {
        $byteString = "";

        $bytes = unpack("C*", $this->bytes(n));

        foreach ($bytes as $idx) {
            $idx = $idx % 64;

            if ($idx >= $base) {
                $idx = $this->number($base - 1);
            }

            $byteString .= $alphabet[(int) $idx];
        }

        return $byteString;
    }

}
