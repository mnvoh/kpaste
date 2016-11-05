<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    SystemActivities.php
 * @createdat    Aug 3, 2013 4:11:08 PM
 */

namespace KpasteCore\Model;

class SystemActivities 
{
    public $activityid;
    public $activity;
    public $userid;
    public $userip;
    public $activity_datetime;
    
    public function exchangeArray($data)
    {
        $this->activityid           = (!empty($data['activityid'])) ? $data['activityid'] : null;
        $this->activity             = (!empty($data['activity'])) ? $data['activity'] : null;
        $this->userid               = (!empty($data['userid'])) ? $data['userid'] : null;
        $this->userip               = (!empty($data['userip'])) ? $data['userip'] : null;
        $this->activity_datetime    = (!empty($data['activity_datetime'])) ? $data['activity_datetime'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

?>
