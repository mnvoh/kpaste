<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    NewPasteForm.php
 * @createdat    Jul 11, 2013 6:50:05 PM
 */
namespace Paster\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class NewPasteForm extends Form
{
    public function __construct($name = null, $options = array()) 
    {
        parent::__construct('NewPasteForm', $options);

        $this->setAttribute('method', 'post');

        $this->add(array(
            'name'      => 'title',
            'type'      => 'Text',
        ));

        $this->add(array(
            'name'      => 'paste',
            'type'      => 'TextArea',
            'attributes'=> array(
                'id'    => 'paste',
            ),
        ));

        $this->add(array(
            'name'      => 'password',
            'type'      => 'Password',
        ));

        $this->add(array(
            'name'      => 'exposure',
            'type'      => 'Select',
            'options'   => array(
                'value_options' => array(
                    'public'    => 'Public',
                    'private'   => 'Private',
                ),
            ),
        ));

        $this->add(array(
            'name'      => 'syntax',
            'type'      => 'Select',
            'attributes'=> array(
                'id'    => 'syntax',
            ),
            'options'   => array(
                'value_options' => array(
                    'plain'             => 'Plain Text',
                    array('value' => 'sep', 'label' => '-------------------------', 'disabled' => 'disabled'),
                    'as3'               => 'ActionScript3',
                    'bash'              => 'Bash/Shell',
                    'cf'                => 'ColdFusion',
                    'csharp'            => 'C#',
                    'cpp'               => 'C++',
                    'css'               => 'CSS',
                    'delphi'            => 'Delphi',
                    'diff'              => 'Diff',
                    'erl'               => 'Erlang',
                    'groovy'            => 'Groovy',
                    'xml'               => 'HTML/XML',
                    'js'                => 'JavaScript',
                    'java'              => 'Java',
                    'jfx'               => 'JavaFX',
                    'perl'              => 'Perl',
                    'php'               => 'PHP',
                    'ps'                => 'PowerShell',
                    'python'            => 'Python',
                    'ruby'              => 'Ruby',
                    'scala'             => 'Scala',
                    'sql'               => 'SQL',
                    'vb'                => 'VisualBasic',
                ),
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
                'value'     => 'Save Paste',
                'id'        => 'submitpastebutton',
            ),
        ));

        $this->setInputFilter($this->createInputFilter());
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
                        'min'       => 2,
                        'max'       => 255,
                    ),
                ),
            ),
        )));

        $inputFilter->add($factory->createInput(array(
            'name'      => 'paste',
            'required'  => true,
            'filters'   => array(
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'StringLength',
                    'options'   => array(
                        'encoding'  => 'UTF-8',
                        'min'       => 10,
                        'max'       => 45000,
                    ),
                ),
            ),
        )));

        $inputFilter->add($factory->createInput(array(
            'name'      => 'password',
            'required'  => false,
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

        $inputFilter->add($factory->createInput(array(
            'name'      => 'exposure',
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
                        'min'       => 6,
                        'max'       => 7,
                    ),
                ),
            ),
        )));

        $inputFilter->add($factory->createInput(array(
            'name'      => 'syntax',
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
                        'min'       => 1,
                        'max'       => 16,
                    ),
                ),
            ),
        )));

        return $inputFilter;
    }
}

?>
