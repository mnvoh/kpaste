<?php
namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class ChangePasswordPhase1Form extends Form {
    
    public function __construct($name = null, $options = array()) {
        parent::__construct('ChangePasswordPhase1Form', $options);
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'      => 'email',
            'type'      => 'Email',
        ));
        
        $this->add(unserialize(CAPTCHA));
        
        $this->add(array(
            'name'      => 'submit',
            'type'      => 'Submit',
            'attributes'=> array(
                'value'     => 'Request password change',
                'id'        => 'verifyEmailSubmit',
            ),
        ));
        
        $this->setInputFilter($this->createInputFilter());
    }
    
    private function createInputFilter() {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'email',
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