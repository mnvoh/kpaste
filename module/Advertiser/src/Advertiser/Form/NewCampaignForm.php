<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    NewCampaignForm.php
 * @createdat   Jul 11, 2013 6:50:05 PM
 */
namespace Advertiser\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;

class NewCampaignForm extends Form
{
    public function __construct($name = null, $options = array()) 
    {
        parent::__construct('NewCampaignForm', $options);

        $this->setAttribute('method', 'post');

        $this->add(array(
            'name'      => 'campaign_title',
            'type'      => 'Text',
        ));

        $this->add(array(
            'name'      => 'campaign_type',
            'type'      => 'Select',
            'options'=> array(
                'value_options'     => array(
                    'square_button_ltr'     => 'Left, Top or Right Square Button',
                    'square_button_b'       => 'Bottom Square Button',
                    'vertical_banner'       => 'Vertical Banner',
                    'leaderboard_t'         => 'Top Leaderboard',
                    'leaderboard_b'         => 'Bottom Leaderboard',
                    'iframe'                => 'Inline Frame Ads',
                ),
            ),
        ));

        $this->add(array(
            'name'      => 'campaign_scope',
            'type'      => 'Select',
            'options'   => array(
                'value_options' => array(
                    'local'         => 'Local',
                    'global'        => 'Global',
                ),
            ),
        ));

        $this->add(array(
            'name'      => 'total_credits',
            'type'      => 'Select',
            'options'   => array(
                'value_options' => array(
                    '1000'      => '1000',
                    '2000'      => '2000',
                    '3000'      => '3000',
                    '5000'      => '5000',
                    '10000'     => '10000',
                    '15000'     => '15000',
                    '20000'     => '20000',
                    '50000'     => '50000',
                ),
            ),
        ));
        
        $this->add(array(
            'name'      => 'daily_credits',
            'type'      => 'Text',
        ));
        
        $this->add(array(
            'name'      => 'campaign_url',
            'type'      => 'Url',
        ));
        
        
        $this->add(array(
            'name'      => 'csrf',
            'type'      => 'Csrf',
            'options' => array(
                'csrf_options' => array(
                    'timeout' => 300
                ),
            ),
        ));

        $this->add(array(
            'name'      => 'submit',
            'type'      => 'Submit',
            'attributes'=> array(
                'value'     => 'Create Campaign',
                'id'        => 'createcampaignbutton',
            ),
        ));

        $this->setInputFilter($this->createInputFilter());
    }

    private function createInputFilter()
    {
        $inputFilter = new InputFilter();
        $factory = new InputFactory();

        $inputFilter->add($factory->createInput(array(
            'name'      => 'campaign_title',
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
            'name'      => 'campaign_type',
            'required'  => true,
            'filters'   => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'InArray',
                    'options'   => array(
                        'haystack'  => array(
                            'square_button_ltr' , 'square_button_b' , 'vertical_banner', 
                            'leaderboard_t'     , 'leaderboard_b'   , 'iframe'
                        ),
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'campaign_scope',
            'required'  => true,
            'filters'   => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'InArray',
                    'options'   => array(
                        'haystack'  => array(
                            'local', 'global',
                        ),
                    ),
                ),
            ),
        )));

        $inputFilter->add($factory->createInput(array(
            'name'      => 'total_credits',
            'required'  => true,
            'filters'   => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'Between',
                    'options'   => array(
                        'min'       => 1000,
                        'max'       => 50000,
                    ),
                ),
            ),
        )));

        $inputFilter->add($factory->createInput(array(
            'name'      => 'daily_credits',
            'required'  => true,
            'filters'   => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'=> array(
                array(
                    'name'      => 'Between',
                    'options'   => array(
                        'min'       => 100,
                        'max'       => 50000,
                    ),
                ),
            ),
        )));
        
        $inputFilter->add($factory->createInput(array(
            'name'      => 'campaign_url',
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
                        'max'       => 2048,
                    ),
                ),
            ),
        )));

        return $inputFilter;
    }
}

?>
