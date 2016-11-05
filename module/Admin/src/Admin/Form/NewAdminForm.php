<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    NewAdminForm.php
 * @createdat   Jul 30, 2013 6:50:05 PM
 */
namespace Admin\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class NewAdminForm extends Form
{
    public function __construct($name = null, $options = array())
    {
        parent::__construct( 'NewAdminForm', $options );
        
        $this->setAttribute( 'method', 'post' );
        
        $this->addElements();
        
        $this->setInputFilter( $this->createInputFilters() );
    }

    public function addElements()
    {
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
            'name'      => 'firstname',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'firstname',
            ),
        ));
        
        $this->add(array(
            'name'      => 'lastname',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'lastname',
            ),
        ));
        
        $this->add(array(
            'name'      => 'cell_number',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'cell_number',
            ),
        ));
        
        $this->add(array(
            'name'      => 'security_question',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'security_question',
            ),
        ));
        
        $this->add(array(
            'name'      => 'security_question_answer',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'security_question_answer',
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
        
        $this->add(array(
            'name'      => 'submit',
            'type'      => 'Submit',
            'attributes'=> array(
                'value'     => 'Create Admin',
                'id'        => 'createadminbutton',
            ),
        ));
    }
    
    private function createInputFilters()
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
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'firstname',
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
                        'max'       => 32,
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'lastname',
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
                        'max'       => 32,
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'cell_number',
            'required'  => false,
            'filters'   => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'Regex',
                    'options'   => array(
                        'pattern'   => '/^09[0-9]{9}$/',
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'security_question',
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