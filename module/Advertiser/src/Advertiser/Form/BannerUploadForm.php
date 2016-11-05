<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    BannerUploadForm.php
 * @createdat   Jul 11, 2013 6:50:05 PM
 */
namespace Advertiser\Form;

use Zend\Form\Element;
use Zend\Form\Form;

class BannerUploadForm extends Form
{
    public function __construct($name = null, $options = array())
    {
        parent::__construct('BannerUploadForm', $options);
        $this->addElements();
    }

    public function addElements()
    {
        $file = new Element\File('banner-file');
        $file->setAttribute('id', 'banner-file');
        $this->add($file);
    }
}