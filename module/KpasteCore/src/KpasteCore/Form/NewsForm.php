<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    NewsForm.php
 * @createdat   Aug 5, 2013 12:09:16 PM
 */
namespace KpasteCore\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class NewsForm extends Form 
{
    public function __construct($name = null, $options = array()) 
    {
        parent::__construct('NewsForm', $options);
    
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name'          => 'title',
            'type'          => 'Text',
        ));
        
        $this->add( array(
            'name'          => 'news',
            'type'          => 'TextArea',
        ) );
        
        $this->setInputFilter( $this->createInputFilter() );
    }
    
    private function createInputFilter()
    {
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
                        'min'       => 5,
                        'max'       => 255,
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'news',
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
                        'min'       => 32,
                        'max'       => 10240,
                    ),
                ),
            ),
        )));
        
        return $inputFilter;
    }
}

?>
