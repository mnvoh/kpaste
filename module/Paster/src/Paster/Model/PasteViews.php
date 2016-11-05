<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Pastes.php
 * @createdat    Jul 11, 2013 12:25:04 PM
 */
namespace Paster\Model;

class PasteViews {
    public $pasteviewid;
    public $pasteid;
    public $view_datetime;
    public $viewer_ip;
    public $user_agent;
    
    public function exchangeArray($array) 
    {
        $this->pasteviewid      = (!empty($array['pasteviewid'])) ? $array['pasteviewid'] : null;
        $this->pasteid          = (!empty($array['pasteid'])) ? $array['pasteid'] : null;
        $this->view_datetime    = (!empty($array['view_datetime'])) ? $array['view_datetime'] : null;
        $this->viewer_ip        = (!empty($array['viewer_ip'])) ? $array['viewer_ip'] : null;
        $this->user_agent       = (!empty($array['user_agent'])) ? $array['user_agent'] : null;
    }
}

?>
