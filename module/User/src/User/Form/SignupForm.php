<?php
namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class SignupForm extends Form 
{
    
    public function __construct($name = null, $options = array()) 
    {
        parent::__construct('SignupForm', $options);
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'      => 'username',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'username',
            ),
        ));
        
        $this->add(array(
            'name'      => 'email',
            'type'      => 'Email',
            'attributes' => array(
                'id'        => 'email',
            ),
        ));
        
        $this->add(array(
            'name'      => 'password',
            'type'      => 'Password',
            'attributes' => array(
                'id'        => 'password',
            ),
        ));
        
        $this->add(array(
            'name'      => 'repassword',
            'type'      => 'Password',
            'attributes' => array(
                'id'        => 'repassword',
            ),
        ));
        
        $this->add(array(
            'name'      => 'csrf',
            'type'      => 'Csrf',
            'options' => array(
                'csrf_options' => array(
                    'timeout' => 600
                )
            )
        ));
        
        $this->add(unserialize(CAPTCHA));
        
        $this->add(array(
            'name'      => 'submit',
            'type'      => 'Submit',
            'attributes'=> array(
                'value'     => 'Sign Up',
                'id'        => 'signupbutton',
            ),
        ));
        
        $this->setInputFilter( $this->createInputFilters() );
    }
    
    public function createInputFilters() 
    {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();

        $inputFilter->add($factory->createInput(array(
            'name'      => 'username',
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
                        'min'       => 5,
                        'max'       => 32,
                    ),
                ),
            ),
        )));

        $inputFilter->add($factory->createInput(array(
            'name'      => 'password',
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
            'name'      => 'repassword',
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
                array(
                    'name'      => 'identical', 
                    'options'   => array(
                        'strict'    => false,
                        'token'     => 'password',
                    ),
                ),
            ),
        )));
        
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
                array(
                    'name' => 'EmailAddress',
                ),
            ),
        )));

        return $inputFilter;
    }
}