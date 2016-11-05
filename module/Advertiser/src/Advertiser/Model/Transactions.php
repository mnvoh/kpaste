<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Transactions.php
 * @createdat   Jul 11, 2013 12:25:04 PM
 */
namespace Advertiser\Model;

class Transactions 
{
    public $transactionid;
    public $userid;
    public $amount;
    public $status;
    public $au;
    public $receipt;
    public $requested_datetime;
    public $completed_datetime;
    
    public function exchangeArray($array) 
    {
        $this->transactionid        = (!empty($array['transactionid'])) ? $array['transactionid'] : null;
        $this->userid               = (!empty($array['userid'])) ? $array['userid'] : null;
        $this->amount               = (!empty($array['amount'])) ? $array['amount'] : null;
        $this->status               = (!empty($array['status'])) ? $array['status'] : null;
        $this->au                   = (!empty($array['au'])) ? $array['au'] : null;
        $this->receipt              = (!empty($array['receipt'])) ? $array['receipt'] : null;
        $this->requested_datetime   = (!empty($array['requested_datetime'])) ? $array['requested_datetime'] : null;
        $this->completed_datetime   = (!empty($array['completed_datetime'])) ? $array['completed_datetime'] : null;
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

?>
