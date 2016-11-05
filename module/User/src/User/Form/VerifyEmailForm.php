<?php
namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class VerifyEmailForm extends Form {
    
    public function __construct($name = null, $options = array()) {
        parent::__construct('VerifyEmailForm', $options);
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'      => 'email_verification_code',
            'type'      => 'Text',
        ));
        
        $this->add(unserialize(CAPTCHA));
        
        $this->add(array(
            'name'      => 'submit',
            'type'      => 'Submit',
            'attributes'=> array(
                'value'     => 'Verify Email',
                'id'        => 'verifyEmailSubmit',
            ),
        ));
        
        $this->setInputFilter($this->createInputFilter());
    }
    
    private function createInputFilter() {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'email_verification_code',
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
                        'min'       => 40,
                        'max'       => 40,
                    ),
                ),
            ),
        )));
        
        return $inputFilter;
    }
}