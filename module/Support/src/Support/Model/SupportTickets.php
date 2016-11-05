<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */

namespace Support\Model;

class SupportTickets
{
    public $ticketid;
    public $userid;
    public $title;
    public $opened_at;
    public $status;
    public $importance;
    public $departmentid;
    public $department;
    public $username;
    public $firstname;
    public $lastname;
    
    public function exchangeArray($data) 
    {
        $this->ticketid     = (!empty($data['ticketid'])) ? $data['ticketid'] : null;
        $this->userid       = (!empty($data['userid'])) ? $data['userid'] : null;
        $this->title        = (!empty($data['title'])) ? $data['title'] : null;
        $this->opened_at    = (!empty($data['opened_at'])) ? $data['opened_at'] : null;
        $this->status       = (!empty($data['status'])) ? $data['status'] : null;
        $this->importance   = (!empty($data['importance'])) ? $data['importance'] : null;
        $this->departmentid = (!empty($data['departmentid'])) ? $data['departmentid'] : null;
        $this->department   = (!empty($data['department'])) ? $data['department'] : null;
        $this->username     = (!empty($data['username'])) ? $data['username'] : null;
        $this->firstname    = (!empty($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname     = (!empty($data['lastname'])) ? $data['lastname'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars( $this );
    }
}