<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    PasswordChangeForm.php
 * @createdat    Jul 12, 2013 11:41:25 AM
 */

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class PasswordChangeForm extends Form{
    public function __construct($name = null, $options = array()) {
        parent::__construct('PassworChangeForm', $options);
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'      => 'current_password',
            'type'      => 'Password',
        ));
        
        $this->add(array(
            'name'      => 'new_password',
            'type'      => 'Password',
            'attributes'=> array(
                'id'        => 'newpassword',
                'onkeyup'   => 'assessPasswordEntropy("newpassword");',
            )
        ));
        
        $this->add(array(
            'name'      => 'new_password_repeat',
            'type'      => 'Password',
        ));
        
        $this->add(unserialize(CAPTCHA));
        
        $this->add(array(
            'name'      => 'submit',
            'type'       => 'Submit',
        ));
        
        $this->setInputFilter($this->createInputFilter());
    }
    
    private function createInputFilter() {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'current_password',
            'required'  => true,
            'filters'   => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'StringLength',
                    'options'   => array(
                        'encoding'  => 'UTF-8',
                        'min'       => 7,
                        'max'       => 256,
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'new_password',
            'required'  => true,
            'filters'   => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'StringLength',
                    'options'   => array(
                        'encoding'  => 'UTF-8',
                        'min'       => 7,
                        'max'       => 256,
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'new_password_repeat',
            'required'  => true,
            'filters'   => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'StringLength',
                    'options'   => array(
                        'encoding'  => 'UTF-8',
                        'min'       => 7,
                        'max'       => 256,
                    ),
                ),
            ),
        )));
        
        return $inputFilter;
    }
}

?>
