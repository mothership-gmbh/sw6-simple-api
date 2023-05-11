<?php

namespace MothershipSimpleApi\Service\Helper;

class BitwiseOperations
{
    /**
     * Basiert auf der
     *
     * @link https://stackoverflow.com/questions/14338640/xor-two-hex-strings
     * @link https://stackoverflow.com/questions/30651062/how-to-use-the-xor-on-two-strings
     *
     * @param string $a
     * @param string $b
     *
     * @return string
     */
    public static function xorHex(string $a, string $b): string
    {
        return bin2hex(pack('H*',$a) ^ pack('H*',$b));
    }
}
