<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Ip2Country.php
 * @createdat    Jul 21, 2013 4:11:08 PM
 */

namespace KpasteCore\Model;

class Ip2Country 
{
    public $ip_from;
    public $ip_to;
    public $ctry;
    public $country;
    
    public function exchangeArray($data)
    {
        $this->ip_from      = (!empty($data['ip_form'])) ? $data['ip_from'] : null;
        $this->ip_to        = (!empty($data['ip_to'])) ? $data['ip_to'] : null;
        $this->ctry         = (!empty($data['ctry'])) ? $data['ctry'] : null;
        $this->country      = (!empty($data['country'])) ? $data['country'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

?>
