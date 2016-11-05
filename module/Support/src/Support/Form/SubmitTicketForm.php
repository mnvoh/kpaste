<?php
namespace Support\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class SubmitTicketForm extends Form {
    
    public function __construct($name = null, $options = array()) {
        parent::__construct('SubmitTicketForm', $options);
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'      => 'title',
            'type'      => 'Text',
        ));
                
        $this->add(array(
            'name'      => 'importance',
            'type'      => 'Select',
            'options'   => array(
                'disable_inarray_validator' => true,
            )
        ));
        
        $this->add(array(
            'name'      => 'departmentid',
            'type'      => 'Select',
            'options'   => array(
                'disable_inarray_validator' => true,
            )
        ));
        
        $this->add( array(
            'name'      => 'message',
            'type'      => 'TextArea',
        ));
        
        $this->add( array(
            'name'      => 'attachment',
            'type'      => 'File',
        ));
        
        $this->add(unserialize(CAPTCHA));
        
        $this->add(array(
            'name'      => 'csrf',
            'type'      => 'Csrf',
            'options' => array(
                'csrf_options' => array(
                    'timeout' => 600
                )
            )
        ));
        
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
            'name'      => 'title',
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
                        'min'       => 2,
                        'max'       => 127,
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'message',
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
                        'min'       => 10,
                        'max'       => 10000,
                    ),
                ),
            ),
        )));
        
        return $inputFilter;
    }
}