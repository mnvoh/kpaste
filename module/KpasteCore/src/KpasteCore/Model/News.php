<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    News.php
 * @createdat   Aug 5, 2013 4:11:08 PM
 */

namespace KpasteCore\Model;

class News 
{
    public $newsid;
    public $userid;
    public $title;
    public $news;
    public $newsdate;
    
    public function exchangeArray($data)
    {
        $this->newsid       = (!empty($data['newsid'])) ? $data['newsid'] : null;
        $this->userid       = (!empty($data['userid'])) ? $data['userid'] : null;
        $this->title        = (!empty($data['title'])) ? $data['title'] : null;
        $this->news         = (!empty($data['news'])) ? $data['news'] : null;
        $this->newsdate     = (!empty($data['newsdate'])) ? $data['newsdate'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

?>
