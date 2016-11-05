<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    AdsViews.php
 * @createdat   Jul 18, 2013 12:25:04 PM
 */
namespace Advertiser\Model;

class AdsViews 
{
    public $adviewid;
    public $campaignid;
    public $viewed_at;
    public $viewer_ip;
    public $user_agent;
   
    public function exchangeArray($array) 
    {
        $this->adviewid = (!empty($array['adviewid'])) ? $array['adviewid'] : null;
        $this->campaignid = (!empty($array['campaignid'])) ? $array['campaignid'] : null;
        $this->viewed_at = (!empty($array['viewed_at'])) ? $array['viewed_at'] : null;
        $this->viewer_ip = (!empty($array['viewer_ip'])) ? $array['viewer_ip'] : null;
        $this->user_agent = (!empty($array['user_agent'])) ? $array['user_agent'] : null;
    }
        
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

?>
