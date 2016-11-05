<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */

namespace User\Model;

class User
{
    public $userid;
    public $username;
    public $email;
    public $password;
    public $salt;
    public $email_verification_code;
    public $reg_date;
    public $account_status;
    public $user_type;
    public $last_login_time;
    public $last_ip_addr;
    
    //paster specific fields
    public $total_pastes;
    public $total_views;

    protected $inputFilter;
    
    public function exchangeArray($data) {
        $this->userid = (!empty($data['userid'])) ? $data['userid'] : null;
        $this->username = (!empty($data['username'])) ? $data['username'] : null;
        $this->email = (!empty($data['email'])) ? $data['email'] : null;
        $this->password = (!empty($data['password'])) ? $data['password'] : null;
        $this->salt = (!empty($data['salt'])) ? $data['salt'] : null;
        $this->email_verification_code = (!empty($data['email_verification_code'])) ? $data['email_verification_code'] : null;
        $this->reg_date = (!empty($data['reg_date'])) ? $data['reg_date'] : null;
        $this->account_status = (!empty($data['account_status'])) ? $data['account_status'] : null;
        $this->user_type = (!empty($data['user_type'])) ? $data['user_type'] : null;
        $this->last_login_time = (!empty($data['last_login_time'])) ? $data['last_login_time'] : null;
        $this->last_ip_addr = (!empty($data['last_ip_addr'])) ? $data['last_ip_addr'] : null;
        $this->total_pastes = (!empty($data['total_pastes'])) ? $data['total_pastes'] : 0;
        $this->total_views = (!empty($data['total_views'])) ? $data['total_views'] : 0;
    }
}