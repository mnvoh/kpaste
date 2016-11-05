<?php
/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    Ip2CountryTable.php
 * @createdat    Jul 21, 2013 4:16:10 PM
 */
namespace KpasteCore\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Where;

class Ip2CountryTable 
{
    public $tableGateway;
    
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }
    
    public function fetchIpInfo($numericalIp)
    {
        $where = new Where();
        $where->expression("`ip_from` < $numericalIp", null);
        $where->expression("`ip_to` > $numericalIp", null);
        $result = $this->tableGateway->select($where);
        return $result->current();
    }
}

?>
