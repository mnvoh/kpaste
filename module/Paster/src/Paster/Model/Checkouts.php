<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Pastes.php
 * @createdat    Jul 11, 2013 12:25:04 PM
 */
namespace Paster\Model;

class Checkouts 
{
    public $checkoutid;
    public $userid;
    public $datetime_requested;
    public $amount;
    public $status;
    public $transaction_tracking_code;
    public $transaction_datetime;
    public $description;
    
    public function exchangeArray($array) 
    {
        $this->checkoutid = (!empty($array['checkoutid'])) ? $array['checkoutid'] : null;
        $this->userid = (!empty($array['userid'])) ? $array['userid'] : null;
        $this->datetime_requested = (!empty($array['datetime_requested'])) ? $array['datetime_requested'] : null;
        $this->amount = (!empty($array['amount'])) ? $array['amount'] : null;
        $this->status = (!empty($array['status'])) ? $array['status'] : null;
        $this->transaction_tracking_code = (!empty($array['transaction_tracking_code'])) ? $array['transaction_tracking_code'] : null;
        $this->transaction_datetime = (!empty($array['transaction_datetime'])) ? $array['transaction_datetime'] : null;
        $this->description = (!empty($array['description'])) ? $array['description'] : null;
    }
        
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

?>
