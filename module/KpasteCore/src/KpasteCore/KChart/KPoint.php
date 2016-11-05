<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    KPoint.php
 * @createdat    Aug 29, 2013 4:55:03 PM
 */
namespace KpasteCore\KChart;

class KPoint {
    public $x;
    public $y;
    
    public function __construct($x, $y) 
    {
        $this->setPoint($x, $y);
    }
    
    public function setPoint($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
    
    public static function ArrayPointsToArray($arrayOfPoints)
    {
        $xs = array();
        $ys = array();
        $i = 0;
        foreach($arrayOfPoints as $point)
        {
            $xs[$i] = $point->x;
            $ys[$i] = $point->y;
            $i++;
        }
        return(array('xs' => $xs, 'ys' => $ys));
    }
}

?>
