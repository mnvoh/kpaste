<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Campaigns.php
 * @createdat    Jul 11, 2013 12:25:04 PM
 */
namespace Advertiser\Model;

class Campaigns 
{
    public $campaignid;
    public $userid;
    public $campaign_title;
    public $campaign_type;
    public $campaign_scope;
    public $total_credits;
    public $daily_credits;
    public $remaining_credits;
    public $campaign_url;
    public $campaign_banner;
    public $status;
    public $start_date;
    public $extension_date;
    public $finished_date;
    public $rejection_reason;
    
    public function exchangeArray($array) 
    {
        $this->campaignid           = (!empty($array['campaignid'])) ? $array['campaignid'] : null;
        $this->userid               = (!empty($array['userid'])) ? $array['userid'] : null;
        $this->campaign_title       = (!empty($array['campaign_title'])) ? $array['campaign_title'] : null;
        $this->campaign_type        = (!empty($array['campaign_type'])) ? $array['campaign_type'] : null;
        $this->campaign_scope       = (!empty($array['campaign_scope'])) ? $array['campaign_scope'] : null;
        $this->total_credits        = (!empty($array['total_credits'])) ? $array['total_credits'] : null;
        $this->daily_credits        = (!empty($array['daily_credits'])) ? $array['daily_credits'] : null;
        $this->remaining_credits    = (!empty($array['remaining_credits'])) ? $array['remaining_credits'] : null;
        $this->campaign_url         = (!empty($array['campaign_url'])) ? $array['campaign_url'] : null;
        $this->campaign_banner      = (!empty($array['campaign_banner'])) ? $array['campaign_banner'] : null;
        $this->status               = (!empty($array['status'])) ? $array['status'] : null;
        $this->start_date           = (!empty($array['start_date'])) ? $array['start_date'] : null;
        $this->extension_date       = (!empty($array['extension_date'])) ? $array['extension_date'] : null;
        $this->finished_date        = (!empty($array['finished_date'])) ? $array['finished_date'] : null;
        $this->rejection_reason     = (!empty($array['rejection_reason'])) ? $array['rejection_reason'] : null;
    }
    
    public function getArrayCopy() 
    {
        return get_object_vars($this);
    }
}

?>
