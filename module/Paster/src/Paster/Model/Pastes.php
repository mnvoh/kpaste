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

use Zend\InputFilter\Input;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Pastes {
    public $pasteid;
    public $userid;
    public $title;
    public $paste;
    public $password_test;
    public $exposure;
    public $syntax;
    public $status;
    public $pasted_on;
    public $viewed;
    public $username;
    
    protected $inputFilter;
    
    public function exchangeArray($array) {
        $this->pasteid = (!empty($array['pasteid'])) ? $array['pasteid'] : null;
        $this->userid = (!empty($array['userid'])) ? $array['userid'] : null;
        $this->title = (!empty($array['title'])) ? $array['title'] : null;
        $this->paste = (!empty($array['paste'])) ? $array['paste'] : null;
        $this->password_test = (!empty($array['password_test'])) ? $array['password_test'] : null;
        $this->exposure = (!empty($array['exposure'])) ? $array['exposure'] : null;
        $this->syntax = (!empty($array['syntax'])) ? $array['syntax'] : null;
        $this->status = (!empty($array['status'])) ? $array['status'] : null;
        $this->pasted_on = (!empty($array['pasted_on'])) ? $array['pasted_on'] : null; 
        $this->viewed = (!empty($array['viewed'])) ? $array['viewed'] : null;
        $this->username = (!empty($array['username'])) ? $array['username'] : null; 
    }
    
    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception('Not Implemented!');
    }
    
    public function getInputFilter() {
        if(!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();
            
            $inputFilter->add($factory->createInput(array(
                'name'              => 'title',
                'required'          => true,
                'filters'           => array(
                    array('name'    => 'StripTags'),
                    array('name'    => 'StringTrim'),
                ),
                'validators'        => array(
                    array(
                        'name'          => 'StringLength',
                        'options'       => array(
                            'encoding'      => 'UTF-8',
                            'min'           => 2,
                            'max'           => 255,
                        ),
                    ),
                ),
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'              => 'paste',
                'required'          => true,
                'filters'           => array(
                    array('name'    => 'StripTags'),
                    array('name'    => 'StringTrim'),
                ),
                'validators'        => array(
                    array(
                        'name'          => 'StringLength',
                        'options'       => array(
                            'encoding'      => 'UTF-8',
                            'min'           => 2,
                            'max'           => 45000,
                        ),
                    ),
                ),
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'              => 'password',
                'required'          => false,
                'filters'           => array(
                    array('name'    => 'StripTags'),
                    array('name'    => 'StringTrim'),
                ),
                'validators'        => array(
                    array(
                        'name'          => 'StringLength',
                        'options'       => array(
                            'encoding'      => 'UTF-8',
                            'min'           => 4,
                            'max'           => 256,
                        ),
                    ),
                ),
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'              => 'exposure',
                'required'          => true,
                'filters'           => array(
                    array('name'    => 'StripTags'),
                    array('name'    => 'StringTrim'),
                ),
                'validators'        => array(
                    array(
                        'name'          => 'StringLength',
                        'options'       => array(
                            'encoding'      => 'UTF-8',
                            'min'           => 6,
                            'max'           => 7,
                        ),
                    ),
                ),
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'              => 'syntax',
                'required'          => true,
                'filters'           => array(
                    array('name'    => 'StripTags'),
                    array('name'    => 'StringTrim'),
                ),
                'validators'        => array(
                    array(
                        'name'          => 'StringLength',
                        'options'       => array(
                            'encoding'      => 'UTF-8',
                            'min'           => 1,
                            'max'           => 16,
                        ),
                    ),
                ),
            )));
        }
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

?>
