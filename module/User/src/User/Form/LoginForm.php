<?php
namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;

class LoginForm extends Form 
{
    public function __construct($name = null, $options = array()) 
    {
        parent::__construct('LoginForm', $options);
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'      => 'username',
            'type'      => 'Text',
        ));
        
        $this->add(array(
            'name'      => 'password',
            'type'      => 'Password',
        ));
        
        $this->add(array(
            'name'      => 'keepMeSignedIn',
            'type'      => 'Checkbox',
        ));
        
        $this->add(array(
            'name'      => 'submit',
            'type'      => 'Submit',
            'attributes'=> array(
                'value'     => 'Login',
                'id'        => 'loginbutton',
            ),
        ));
        
        $this->setInputFilter( $this->getInputFilters() );
    }
    
    public function getInputFilters()
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
        
        return $inputFilter;
    }
}