<?php

/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 */

namespace User\Model;

use Zend\InputFilter\Input;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class PasswordChangeRequests {
    public $pcrid;
    public $userid;
    public $request_time;
    public $request_confirmation_code;
    public $status;
    
    protected $inputFilter;
    
    public function exchangeArray($data) {
        $this->pcrid = (!empty($data['pcrid'])) ? $data['pcrid'] : null;
        $this->userid = (!empty($data['userid'])) ? $data['userid'] : null;
        $this->request_time = (!empty($data['request_time'])) ? $data['request_time'] : null;
        $this->request_confirmation_code = (!empty($data['request_confirmation_code'])) ? $data['request_confirmation_code'] : null;
        $this->status = (!empty($data['status'])) ? $data['status'] : null;
    }
    
    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new Exception("Not Implemented!");
    }
    
    public function getInputFilter() {
        return null;
    }
}