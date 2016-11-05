<?php
namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class ChangePasswordPhase2Form extends Form {
    
    public function __construct($name = null, $options = array()) {
        parent::__construct('ChangePasswordPhase2Form', $options);
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'      => 'security_question_answer',
            'type'      => 'Text',
        ));
        
        $this->add(unserialize(CAPTCHA));
        
        $this->add(array(
            'name'      => 'submit',
            'type'      => 'Submit',
            'attributes'=> array(
                'value'     => 'Verify Answer',
                'id'        => 'securityQuestionAnswerSubmit',
            ),
        ));
        
        $this->setInputFilter($this->createInputFilter());
    }
    
    private function createInputFilter() {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'security_question_answer',
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
                        'max'       => 1024,
                    ),
                ),
            ),
        )));
        
        return $inputFilter;
    }
}