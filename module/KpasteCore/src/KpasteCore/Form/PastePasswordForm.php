<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    PastePasswordForm.php
 * @createdat    Jul 16, 2013 12:09:16 PM
 */
namespace KpasteCore\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class PastePasswordForm extends Form 
{
    public function __construct($name = null, $options = array()) 
    {
        parent::__construct('PastePasswordForm', $options);
    
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'          => 'password',
            'type'          => 'Password',
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
                'value'     => 'Confirm',
                'id'        => 'confirmbutton',
            ),
        ));
        
        $this->setInputFilter($this->createInputFilter());
    }
    
    private function createInputFilter()
    {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();
        
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
                        'min'       => 4,
                        'max'       => 64,
                    ),
                ),
            ),
        )));
        
        return $inputFilter;
    }
}

?>
