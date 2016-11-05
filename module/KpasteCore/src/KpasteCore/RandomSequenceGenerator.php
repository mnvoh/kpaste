<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    RandomSequenceGenerator.php
 * @createdat    Jul 30, 2013 12:59:21 PM
 */

namespace KpasteCore;

class RandomSequenceGenerator 
{
    public static function generateRandomSequence($length, $customChars = null) 
    {
        $chars = ($customChars) ? $customChars : 'abcdefghijklmnopqrstuvwxyz0123456789';
        $randomSeq = '';
        for($i = 0; $i < $length; $i++) {
            $randomSeq .= $chars[self::devurandom() % strlen($chars)];
        }
        return $randomSeq;
    }
    
    public static function devurandom($min = 0, $max = 0x7FFFFFFF) 
    {
        $diff = $max - $min;
        if ($diff < 0 || $diff > 0x7FFFFFFF) {
            throw new RuntimeException("Bad range");
        }
        $bytes = mcrypt_create_iv(4, MCRYPT_DEV_URANDOM);
        if ($bytes === false || strlen($bytes) != 4) {
            throw new RuntimeException("Unable to get 4 bytes");
        }
        $ary = unpack("Nint", $bytes);
        $val = $ary['int'] & 0x7FFFFFFF;   // 32-bit safe                           
        $fp = (float) $val / 2147483647.0; // convert to [0,1]                          
        return round($fp * $diff) + $min;
    }
}

?>
