<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    SettingsForm.php
 * @createdat   Sep 2, 2013 6:50:05 PM
 */
namespace Admin\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class SettingsForm extends Form
{
    public function __construct($name = null, $options = array())
    {
        parent::__construct( 'SettingsForm', $options );
        
        $this->setAttribute( 'method', 'post' );
        
        $this->addElements();
        
        $this->setInputFilter( $this->createInputFilters() );
    }

    public function addElements()
    {
        $this->add(array(
            'name'      => 'site-name',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'site-name',
            ),
        ));
        
        $this->add(array(
            'name'      => 'site-url',
            'type'      => 'URL',
            'attributes' => array(
                'id'        => 'site-url',
            ),
        ));
        
        $this->add(array(
            'name'      => 'admin-email',
            'type'      => 'Email',
            'attributes' => array(
                'id'        => 'admin-email',
            ),
        ));
        
        $this->add(array(
            'name'      => 'theme',
            'type'      => 'Select',
            'attributes' => array(
                'id'        => 'theme',
            ),
            'options'   => array(
                'disable_inarray_validator' => true,
            ),
        ));
        
        $this->add(array(
            'name'      => 'language',
            'type'      => 'Select',
            'attributes' => array(
                'id'        => 'language',
            ),
            'options'   => array(
                'disable_inarray_validator' => true,
            ),
        ));
        
        $this->add(array(
            'name'      => 'direction',
            'type'      => 'Select',
            'attributes' => array(
                'id'        => 'direction',
            ),
            'options'   => array(
                'disable_inarray_validator' => true,
            ),
        ));
        
        $this->add(array(
            'name'      => 'locale',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'locale',
            ),
        ));
        
        $this->add(array(
            'name'      => 'payment-per-thousand',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'payment-per-thousand',
            ),
        ));
        
        $this->add(array(
            'name'      => 'price-sqbtn-lrt',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'price-sqbtn-lrt',
            ),
        ));
        
        $this->add(array(
            'name'      => 'price-sqbtn-b',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'price-sqbtn-b',
            ),
        ));
        $this->add(array(
            'name'      => 'price-verbnr',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'price-verbnr',
            ),
        ));
        $this->add(array(
            'name'      => 'price-leaderboard-t',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'price-leaderboard-t',
            ),
        ));
        
        $this->add(array(
            'name'      => 'price-leaderboard-b',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'price-leaderboard-b',
            ),
        ));
        
        $this->add(array(
            'name'      => 'price-iframe',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'price-iframe',
            ),
        ));
        
        $this->add(array(
            'name'      => 'local-price-factor',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'local-price-factor',
            ),
        ));
        
        $this->add(array(
            'name'      => 'discount-factor',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'discount-factor',
            ),
        ));
        
        $this->add(array(
            'name'      => 'min-balance-for-checkout',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'min-balance-for-checkout',
            ),
        ));
        
        $this->add(array(
            'name'      => 'min-recharge-amount',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'min-recharge-amount',
            ),
        ));
        
        $this->add(array(
            'name'      => 'currency',
            'type'      => 'Select',
            'attributes' => array(
                'id'        => 'currency',
            ),
            'options'   => array(
                'disable_inarray_validator' => true,
            ),
        ));
        
        $this->add(array(
            'name'      => 'calendar',
            'type'      => 'Select',
            'attributes' => array(
                'id'        => 'calendar',
            ),
            'options'   => array(
                'disable_inarray_validator' => true,
            ),
        ));
        
        $this->add(array(
            'name'      => 'max-banner-size',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'max-banner-size',
            ),
        ));
        
        $this->add(array(
            'name'      => 'max-attachment-size',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'max-attachment-size',
            ),
        ));
        
        $this->add(array(
            'name'      => 'max-activities-age',
            'type'      => 'Text',
            'attributes' => array(
                'id'        => 'max-activities-age',
            ),
        ));
    }
    
    private function createInputFilters()
    {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();

        $inputFilter->add($factory->createInput(array(
            'name'      => 'site-name',
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
                        'max'       => 128,
                    ),
                ),
            ),
        )));

        $inputFilter->add($factory->createInput(array(
            'name'      => 'site-url',
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
                        'max'       => 256,
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'locale',
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
                        'max'       => 2,
                    ),
                ),
            ),
        )));
        
        return $inputFilter;
    }
}