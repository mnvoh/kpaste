<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Cipher.php
 * @createdat    Jul 11, 2013 2:59:00 PM
 */

namespace KpasteCore\Cipher;

class Cipher {
    protected $key;
    protected $data;
    protected $ivSize;
    protected $lastCipheredData;
    protected $lastDecipheredData;
    
    public function __construct($key = 'defaultKpasteCipherKeyisnotTHAT3a5y70gu355') {
        $this->key          = hash('sha256', $key, true);
        $this->ivSize       = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC); 
    }
    
    public function encrypt($data) {
        $iv                     = mcrypt_create_iv($this->ivSize, MCRYPT_RAND);
        $plainTextUTF8          = utf8_encode($data);
        $this->lastCipheredData = $iv . mcrypt_encrypt(MCRYPT_RIJNDAEL_256, 
                $this->key, $plainTextUTF8, MCRYPT_MODE_CBC, $iv);
        
        $this->lastCipheredData = base64_encode($this->lastCipheredData);
        return $this->lastCipheredData;
    }
    
    public function decrypt($data) {
        $this->lastDecipheredData   = base64_decode($data);
        $iv                         = substr($this->lastDecipheredData, 0, $this->ivSize);
        $this->lastDecipheredData   = substr($this->lastDecipheredData, $this->ivSize);
        $this->lastDecipheredData   = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, 
                $this->lastDecipheredData, MCRYPT_MODE_CBC, $iv);
        return utf8_decode( $this->lastDecipheredData );
    }
    
    public function getLastCipheredData() {
        return $this->lastCipheredData;
    }
    
    public function getLastDecipheredData() {
        return $this->lastDecipheredData;
    }
}

?>
