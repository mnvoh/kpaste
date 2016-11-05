<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */

namespace Support\Model;

class TicketMessages
{
    public $tmsgid;
    public $ticketid;
    public $userid;
    public $message;
    public $status;
    public $msgdate;
    public $attachment;
    public $attachment_name;
    public $firstname;
    public $lastname;
    public $user_type;
    
    public function exchangeArray( $data ) 
    {
        $this->tmsgid       = (!empty($data['tmsgid'])) ? $data['tmsgid'] : null;
        $this->ticketid     = (!empty($data['ticketid'])) ? $data['ticketid'] : null;
        $this->userid       = (!empty($data['userid'])) ? $data['userid'] : null;
        $this->message      = (!empty($data['message'])) ? $data['message'] : null;
        $this->status       = (!empty($data['status'])) ? $data['status'] : null;
        $this->msgdate      = (!empty($data['msgdate'])) ? $data['msgdate'] : null;
        $this->attachment   = (!empty($data['attachment'])) ? $data['attachment'] : null;
        $this->attachment_name= (!empty($data['attachment_name'])) ? $data['attachment_name'] : null;
        $this->firstname    = (!empty($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname     = (!empty($data['lastname'])) ? $data['lastname'] : null;
        $this->user_type    = (!empty($data['user_type'])) ? $data['user_type'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars( $this );
    }
}