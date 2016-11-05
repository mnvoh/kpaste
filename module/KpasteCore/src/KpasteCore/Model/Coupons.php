<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Coupons.php
 * @createdat   Oct 16, 2013 4:11:08 PM
 */

namespace KpasteCore\Model;

class Coupons 
{
    public $couponid;
    public $discount;
    public $count;
    
    public function exchangeArray($data)
    {
        $this->couponid = (!empty($data['couponid'])) ? $data['couponid'] : null;
        $this->discount = (!empty($data['discount'])) ? $data['discount'] : null;
        $this->count    = (!empty($data['count'])) ? $data['count'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

?>
